<?php

namespace Tests\Feature;

use App\Http\Controllers\EntryExportController;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_client_is_created_with_its_details_encrypted(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/clients', [
                'name' => 'Furniloy d.o.o.',
                'address' => "Ilica 1\n10000 Zagreb",
                'tax_id' => '123 4567 8903',
            ])
            ->assertCreated()
            ->assertJsonPath('tax_id', '12345678903')
            ->assertJsonPath('address', "Ilica 1\n10000 Zagreb");

        $raw = DB::table('clients')->first();

        $this->assertSame('Furniloy d.o.o.', $raw->name);
        $this->assertStringStartsWith('eyJ', $raw->address);
        $this->assertStringStartsWith('eyJ', $raw->tax_id);
    }

    public function test_client_names_are_unique_and_the_oib_is_checked(): void
    {
        $user = User::factory()->create();
        $user->clients()->create(['name' => 'Furniloy d.o.o.']);

        $this->actingAs($user)
            ->postJson('/api/clients', [
                'name' => 'Furniloy d.o.o.',
                'tax_id' => '12345678901',
            ])
            ->assertJsonValidationErrors(['name', 'tax_id']);
    }

    public function test_a_project_belongs_to_a_client_and_survives_its_deletion(): void
    {
        $client = $user = User::factory()->create();
        $user->clients()->create(['name' => 'Furniloy d.o.o.']);

        $this->actingAs($user)
            ->postJson('/api/projects', [
                'name' => 'Cockpit',
                'client_id' => $client->id,
            ])
            ->assertCreated()
            ->assertJsonPath('client_id', $client->id);

        $this->deleteJson("/api/clients/{$client->id}")->assertNoContent();

        $this->getJson('/api/projects')
            ->assertJsonPath('0.name', 'Cockpit')
            ->assertJsonPath('0.client_id', null);
    }

    public function test_the_report_can_cover_one_client_only(): void
    {
        $user = User::factory()->create(['hourly_rate' => 40]);
        $furniloy = $user->clients()->create([
            'name' => 'Furniloy d.o.o.',
            'address' => 'Ilica 1',
        ]);
        $other = $user->clients()->create(['name' => 'Drugi klijent']);

        $cockpit = $user->projects()->create(['name' => 'Cockpit', 'client_id' => $furniloy->id]);
        $side = $user->projects()->create(['name' => 'Sporedni', 'client_id' => $other->id]);

        foreach ([[$cockpit, 'Za Furniloy'], [$side, 'Za drugog']] as [$project, $description]) {
            $user->timeEntries()->create([
                'description' => $description,
                'project_id' => $project->id,
                'started_at' => '2026-09-01 06:00:00',
                'ended_at' => '2026-09-01 07:00:00',
            ]);
        }

        $request = Request::create('/api/export/pdf', 'GET', [
            'from' => '2026-08-31T22:00:00Z',
            'to' => '2026-09-30T22:00:00Z',
            'timezone' => 'Europe/Zagreb',
            'client_id' => $furniloy->id,
        ]);
        $request->setUserResolver(fn () => $user);

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data) {
                $html = view($view, $data)->render();

                $this->assertSame(1, $data['summary']['entries']);
                $this->assertStringContainsString('Za Furniloy', $html);
                $this->assertStringNotContainsString('Za drugog', $html);
                $this->assertStringContainsString('Furniloy d.o.o.', $html);
                $this->assertStringContainsString('Ilica 1', $html);

                return true;
            })
            ->andReturnSelf();

        Pdf::shouldReceive('setPaper')->andReturnSelf();
        Pdf::shouldReceive('setOption')->andReturnSelf();
        Pdf::shouldReceive('stream')
            ->with('timer-'.str($user->name)->slug().'-furniloy-doo-2026-09.pdf')
            ->andReturn(response('pdf'));

        (new EntryExportController)->pdf($request);
    }
}
