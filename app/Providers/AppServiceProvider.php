<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit; // Importer Limit
use Illuminate\Http\Request;             // Importer Request
use Illuminate\Support\Facades\RateLimiter; // Importer RateLimiter
use Laravel\Sanctum\Sanctum;
use App\Models\PersonalAccessToken;

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
        $this->configureRateLimiting();
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class); // AJOUTER CETTE LIGNE


    }
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Vous pouvez définir d'autres limiteurs ici
        // RateLimiter::for('uploads', function (Request $request) {
        //     return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        // });
    }
}
