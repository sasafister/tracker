<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_get_their_browsers_language(): void
    {
        $this->get('/', ['Accept-Language' => 'de-DE,de;q=0.9'])
            ->assertSee('Nur Zeit. Sonst nichts.')
            ->assertSee('<html lang="de"', false);
    }

    public function test_guests_can_switch_the_language(): void
    {
        $this->get('/locale/sl')->assertRedirect();

        $this->get('/login')->assertSee('Dobrodošel nazaj');
    }

    public function test_an_unknown_language_is_refused(): void
    {
        $this->get('/locale/xx')->assertNotFound();
    }

    public function test_a_user_saves_their_language_and_gets_messages_in_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/settings', [
                'name' => $user->name,
                'email' => $user->email,
                'hourly_rate' => 10,
                'currency' => 'EUR',
                'locale' => 'en',
            ])
            ->assertOk()
            ->assertJsonPath('locale', 'en');

        $this->putJson('/api/settings', [
            'name' => '',
            'email' => $user->email,
            'hourly_rate' => 10,
            'currency' => 'EUR',
        ])->assertJsonPath('errors.name.0', 'The name field is required.');

        $user->update(['locale' => 'hr']);

        $this->putJson('/api/settings', [
            'name' => '',
            'email' => $user->email,
            'hourly_rate' => 10,
            'currency' => 'EUR',
        ])->assertJsonPath('errors.name.0', 'Polje ime je obavezno.');
    }

    public function test_the_dashboard_page_carries_the_users_language(): void
    {
        $this->actingAs(User::factory()->create(['locale' => 'sl']))
            ->get('/')
            ->assertSee('<html lang="sl"', false);
    }

    public function test_a_failed_sign_in_on_the_landing_page_comes_back_to_it_with_the_error(): void
    {
        $user = User::factory()->create();

        $this->from('/')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong',
            ], ['Accept-Language' => 'en'])
            ->assertRedirect('/');

        $this->get('/', ['Accept-Language' => 'en'])
            ->assertSee('Wrong email or password.')
            ->assertSee($user->email);
    }
}
