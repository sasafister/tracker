<?php

namespace Tests\Feature;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HourlyRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_entry_without_a_project_takes_the_users_default_rate(): void
    {
        $user = User::factory()->create(['hourly_rate' => 30]);

        $this->actingAs($user)
            ->postJson('/api/entries', ['description' => 'Mail'])
            ->assertCreated()
            ->assertJsonPath('hourly_rate', '30.00')
            ->assertJsonPath('billable', true)
            ->assertJsonPath('user_id', $user->id);
    }

    public function test_a_project_rate_wins_and_an_empty_one_falls_back_to_the_default(): void
    {
        $user = User::factory()->create(['hourly_rate' => 30]);

        $this->actingAs($user)
            ->postJson('/api/projects', [
                'name' => 'Furniloy',
                'hourly_rate' => 50,
            ])
            ->assertCreated()
            ->assertJsonPath('hourly_rate', '50.00');

        $this->postJson('/api/projects', ['name' => 'Tennis'])
            ->assertCreated()
            ->assertJsonPath('hourly_rate', null);

        [$furniloy, $tennis] = $user->projects()->orderBy('name')->pluck('id');

        $this->postJson('/api/entries', ['project_id' => $furniloy])
            ->assertJsonPath('hourly_rate', '50.00');

        $this->postJson('/api/entries', ['project_id' => $tennis])
            ->assertJsonPath('hourly_rate', '30.00');
    }

    public function test_an_entry_keeps_its_rate_when_the_rate_changes_later(): void
    {
        $user = User::factory()->create(['hourly_rate' => 30]);

        $entry = $this->actingAs($user)
            ->postJson('/api/entries', ['description' => 'Rad'])
            ->json();

        $this->putJson('/api/settings', [
            'name' => $user->name,
            'email' => $user->email,
            'hourly_rate' => 45,
            'currency' => 'EUR',
        ])->assertOk();

        $this->assertSame('30.00', TimeEntry::find($entry['id'])->hourly_rate);
    }

    public function test_moving_an_entry_to_another_project_takes_that_projects_rate(): void
    {
        $user = User::factory()->create(['hourly_rate' => 30]);
        $project = $user->projects()->create([
            'name' => 'Tennis',
            'hourly_rate' => 60,
        ]);

        $entry = $this->actingAs($user)
            ->postJson('/api/entries', ['description' => 'Rad'])
            ->json();

        $this->patchJson("/api/entries/{$entry['id']}", [
            'project_id' => $project->id,
            'billable' => false,
        ])
            ->assertOk()
            ->assertJsonPath('hourly_rate', '60.00')
            ->assertJsonPath('billable', false);
    }

    public function test_clearing_a_project_rate_falls_back_to_the_default(): void
    {
        $user = User::factory()->create(['hourly_rate' => 30]);
        $project = $user->projects()->create([
            'name' => 'Tennis',
            'hourly_rate' => 60,
        ]);

        $this->actingAs($user)
            ->putJson("/api/projects/{$project->id}", [
                'name' => 'Tennis',
                'hourly_rate' => null,
            ])
            ->assertOk()
            ->assertJsonPath('hourly_rate', null);

        $this->assertSame('30.00', $user->hourlyRateFor($project->id));
    }
}
