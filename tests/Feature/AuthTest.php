<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_is_signed_in(): void
    {
        $this->post('/register', [
            'name' => 'Saša',
            'email' => 'sasa@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect('/');

        $this->assertAuthenticated();
    }

    public function test_a_user_can_log_in_and_out(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'not-it',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_api_answers_guests_with_401(): void
    {
        $this->getJson('/api/entries')->assertUnauthorized();
    }
}
