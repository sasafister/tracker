<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_rates_descriptions_and_company_details_are_unreadable_in_the_database(): void
    {
        $user = User::factory()->create([
            'hourly_rate' => 42,
            'company_name' => 'Moja tvrtka d.o.o.',
            'company_tax_id' => '12345678903',
        ]);
        $project = $user->projects()->create(['name' => 'Furniloy']);

        $this->actingAs($user)
            ->putJson("/api/projects/{$project->id}", [
                'name' => 'Furniloy',
                'hourly_rate' => 55,
            ])
            ->assertOk();

        $this->postJson('/api/entries', [
            'description' => 'Tajni opis',
            'project_id' => $project->id,
        ])->assertCreated();

        $rawUser = DB::table('users')->where('id', $user->id)->first();
        $rawRate = DB::table('projects')->first();
        $rawEntry = DB::table('time_entries')->first();

        foreach ([
            $rawUser->hourly_rate,
            $rawUser->company_name,
            $rawUser->company_tax_id,
            $rawRate->hourly_rate,
            $rawEntry->description,
            $rawEntry->hourly_rate,
        ] as $stored) {
            $this->assertStringStartsWith('eyJ', $stored);
        }

        $this->assertStringNotContainsString('Tajni', $rawEntry->description);
        $this->assertStringNotContainsString('55', $rawRate->hourly_rate);
    }

    public function test_the_owner_still_reads_everything_in_plain_text(): void
    {
        $user = User::factory()->create(['hourly_rate' => 42]);
        $project = $user->projects()->create([
            'name' => 'Furniloy',
            'hourly_rate' => 55,
        ]);

        $this->actingAs($user)
            ->postJson('/api/entries', [
                'description' => 'Tajni opis',
                'project_id' => $project->id,
            ])
            ->assertJsonPath('description', 'Tajni opis')
            ->assertJsonPath('hourly_rate', '55.00');

        $this->getJson('/api/projects')->assertJsonPath('0.hourly_rate', '55.00');
        $this->getJson('/api/settings')->assertJsonPath('hourly_rate', '42.00');
        $this->getJson('/api/entries')->assertJsonPath('0.description', 'Tajni opis');
    }

    public function test_a_new_user_starts_at_a_zero_rate(): void
    {
        $this->post('/register', [
            'name' => 'Nova',
            'email' => 'nova@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $this->getJson('/api/settings')->assertJsonPath('hourly_rate', '0.00');
    }
}
