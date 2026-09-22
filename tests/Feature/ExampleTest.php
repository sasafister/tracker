<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_get_the_landing_page(): void
    {
        $this->withoutVite();

        $this->get('/', ['Accept-Language' => 'hr'])
            ->assertOk()
            ->assertSee('Samo vrijeme. Ništa više.')
            ->assertSee('action="/login"', false);
    }

    public function test_the_dashboard_renders_for_a_signed_in_user(): void
    {
        $this->withoutVite();

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk();
    }
}
