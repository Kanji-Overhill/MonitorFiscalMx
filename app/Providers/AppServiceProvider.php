<?php

namespace App\Providers;

use App\Models\Organization;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('rfc-lookup', function (Request $request) {
            /** @var Organization|null $organization */
            $organization = $request->attributes->get('organization');

            $key = $organization ? "org:{$organization->id}" : $request->ip();

            return Limit::perMinute(30)->by($key);
        });
    }
}
