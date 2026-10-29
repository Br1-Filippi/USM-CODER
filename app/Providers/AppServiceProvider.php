<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Gate::define('teacher-admin', function ($user) {
        return in_array($user->tipo, ['admin', 'profesor']);
        });
        Gate::define('student-admin', function ($user) {
        return in_array($user->tipo, ['admin', 'alumno']);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Por correo y no solo por IP: detrás del proxy todo el laboratorio llega con la misma IP.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower($request->input('email')) . '|' . $request->ip())
            ->response(fn () => back()->withErrors(['email' => 'Demasiados intentos, espera un minuto.'])));

        if (app()->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        if (app()->environment('testing')) {
            \Illuminate\Support\Facades\URL::forceScheme('http');
        }
    }
}
