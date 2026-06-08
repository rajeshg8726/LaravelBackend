<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
   use Illuminate\Auth\Notifications\ResetPassword;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */

public function boot(): void
{
    // Point password reset links to Next.js frontend
    ResetPassword::createUrlUsing(function ($user, string $token) {
        return env('FRONTEND_URL', 'http://localhost:3000')
            . '/reset-password?token=' . $token
            . '&email=' . urlencode($user->email);
    });

    // Custom design for password reset emails (premium Obsidian theme, zero Laravel branding)
    ResetPassword::toMailUsing(function ($notifiable, string $token) {
        $url = env('FRONTEND_URL', 'http://localhost:3000')
            . '/reset-password?token=' . $token
            . '&email=' . urlencode($notifiable->email);

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Reset Your Password | RGJobs')
            ->view('emails.reset-password', [
                'url' => $url,
                'name' => $notifiable->full_name ?? 'there',
            ]);
    });
}

}
