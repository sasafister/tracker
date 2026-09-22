<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_update_their_profile_and_rate(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/settings', [
                'name' => 'Novo Ime',
                'email' => 'novo@example.com',
                'hourly_rate' => 42.5,
                'currency' => 'USD',
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Novo Ime')
            ->assertJsonPath('hourly_rate', '42.50')
            ->assertJsonPath('currency', 'USD');
    }

    public function test_only_listed_currencies_are_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/settings', [
                'name' => $user->name,
                'email' => $user->email,
                'hourly_rate' => 10,
                'currency' => 'XYZ',
            ])
            ->assertJsonValidationErrors('currency');
    }

    public function test_the_email_must_stay_unique(): void
    {
        $taken = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/settings', [
                'name' => $user->name,
                'email' => $taken->email,
                'hourly_rate' => 0,
                'currency' => 'EUR',
            ])
            ->assertJsonValidationErrors('email');
    }

    public function test_changing_the_password_needs_the_current_one(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)
            ->putJson('/api/password', [
                'current_password' => 'wrong',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertJsonValidationErrors('current_password');

        $this->putJson('/api/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertNoContent();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}
