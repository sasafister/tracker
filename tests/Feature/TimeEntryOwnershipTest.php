<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_only_see_and_touch_their_own_entries(): void
    {
        $ana = User::factory()->create();
        $ivo = User::factory()->create();

        $entry = $this->actingAs($ana)
            ->postJson('/api/entries', ['description' => 'Anin'])
            ->json();

        $this->actingAs($ivo)
            ->getJson('/api/entries')
            ->assertOk()
            ->assertJsonCount(0);

        $this->patchJson("/api/entries/{$entry['id']}", ['description' => 'Ivin'])
            ->assertNotFound();

        $this->deleteJson("/api/entries/{$entry['id']}")
            ->assertNotFound();
    }

    public function test_starting_a_timer_only_stops_the_same_users_running_entry(): void
    {
        $ana = User::factory()->create();
        $ivo = User::factory()->create();

        $anas = $this->actingAs($ana)
            ->postJson('/api/entries', ['description' => 'Anin'])
            ->json();

        $this->actingAs($ivo)
            ->postJson('/api/entries', ['description' => 'Ivin'])
            ->assertCreated();

        $this->actingAs($ana)
            ->getJson('/api/entries')
            ->assertJsonPath('0.id', $anas['id'])
            ->assertJsonPath('0.ended_at', null);
    }
}
