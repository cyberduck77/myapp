<?php

namespace App\Providers;

use App\Auth\JwtGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Auth::extend('jwt', function ($app, $name, $config) {
            $provider = Auth::createUserProvider($config['provider'] ?? null);
            $jwtConfig = array_merge(config('jwt', []), $config);
            $guard = new JwtGuard($provider, $app['request'], $jwtConfig);
            $app->refresh('request', $guard, 'setRequest');
            return $guard;
        });
    }
}
