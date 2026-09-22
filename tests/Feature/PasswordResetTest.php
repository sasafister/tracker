<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reset_link_is_mailed_and_sets_a_new_password(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');

        $token = null;

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->withoutVite()
            ->get("/reset-password/{$token}?email={$user->email}")
            ->assertOk()
            ->assertSee($user->email);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
    }

    public function test_an_unknown_email_gets_the_same_answer(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_a_bad_token_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->post('/reset-password', [
            'token' => 'not-a-token',
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_the_mail_is_in_croatian_and_links_to_the_reset_page(): void
    {
        app()->setLocale('hr');

        $user = User::factory()->create();

        $mail = (new ResetPassword('abc123'))->toMail($user);

        $this->assertSame('Postavi novu lozinku', $mail->actionText);
        $this->assertStringContainsString('/reset-password/abc123?email=', $mail->actionUrl);
    }

    public function test_the_mail_goes_out_in_the_users_language(): void
    {
        Notification::fake();

        $user = User::factory()->create(['locale' => 'de']);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            fn ($notification, $channels, $notifiable, $locale) => $locale === 'de',
        );

        app()->setLocale('de');

        $mail = (new ResetPassword('abc123'))->toMail($user);

        $this->assertSame('Neues Passwort festlegen', $mail->actionText);
    }
}
