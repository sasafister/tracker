<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_see_only_their_own_projects_and_clients(): void
    {
        $ana = User::factory()->create();
        $ivo = User::factory()->create();

        $ana->clients()->create(['name' => 'Anin klijent']);
        $ana->projects()->create(['name' => 'Anin projekt']);

        $this->actingAs($ivo)->getJson('/api/projects')->assertExactJson([]);
        $this->actingAs($ivo)->getJson('/api/clients')->assertExactJson([]);
    }

    public function test_someone_elses_project_or_client_cannot_be_touched(): void
    {
        $ana = User::factory()->create();
        $ivo = User::factory()->create();

        $client = $ana->clients()->create(['name' => 'Anin klijent']);
        $project = $ana->projects()->create(['name' => 'Anin projekt']);

        $this->actingAs($ivo);

        $this->putJson("/api/projects/{$project->id}", ['name' => 'Oteto'])->assertNotFound();
        $this->deleteJson("/api/projects/{$project->id}")->assertNotFound();
        $this->putJson("/api/clients/{$client->id}", ['name' => 'Oteto'])->assertNotFound();
        $this->deleteJson("/api/clients/{$client->id}")->assertNotFound();

        $this->assertSame('Anin projekt', $project->fresh()->name);
        $this->assertSame('Anin klijent', $client->fresh()->name);
    }

    public function test_someone_elses_project_or_client_cannot_be_used(): void
    {
        $ana = User::factory()->create();
        $ivo = User::factory()->create();

        $client = $ana->clients()->create(['name' => 'Anin klijent']);
        $project = $ana->projects()->create(['name' => 'Anin projekt']);

        $this->actingAs($ivo);

        $this->postJson('/api/entries', ['project_id' => $project->id])
            ->assertJsonValidationErrors('project_id');

        $this->postJson('/api/projects', [
            'name' => 'Ivin projekt',
            'client_id' => $client->id,
        ])->assertJsonValidationErrors('client_id');

        $this->get('/api/export/pdf?'.http_build_query([
            'from' => '2026-08-31T22:00:00Z',
            'to' => '2026-09-30T22:00:00Z',
            'timezone' => 'Europe/Zagreb',
            'client_id' => $client->id,
        ]), ['Accept' => 'application/json'])->assertJsonValidationErrors('client_id');
    }

    public function test_two_users_can_use_the_same_names(): void
    {
        $ana = User::factory()->create();
        $ana->clients()->create(['name' => 'Furniloy d.o.o.']);
        $ana->projects()->create(['name' => 'Cockpit']);

        $this->actingAs(User::factory()->create())
            ->postJson('/api/clients', ['name' => 'Furniloy d.o.o.'])
            ->assertCreated();

        $this->postJson('/api/projects', ['name' => 'Cockpit'])->assertCreated();
        $this->postJson('/api/projects', ['name' => 'Cockpit'])->assertJsonValidationErrors('name');
    }
}
