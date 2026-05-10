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
}

}
