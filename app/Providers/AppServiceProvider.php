<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::toMailUsing(function (object $user, string $token) {
            $url = url('/reset-password/'.$token.'?'.http_build_query([
                'email' => $user->getEmailForPasswordReset(),
            ]));

            $minutes = config('auth.passwords.users.expire');

            // Sent in the user's own language (User::preferredLocale).
            return (new MailMessage)
                ->subject(__('app.mail.reset_subject', ['app' => config('app.name')]))
                ->greeting(__('app.mail.greeting'))
                ->line(__('app.mail.reset_line'))
                ->action(__('app.mail.reset_action'), $url)
                ->line(__('app.mail.reset_expires', ['minutes' => $minutes]))
                ->line(__('app.mail.reset_ignore'))
                ->salutation(config('app.name'));
        });
    }
}
