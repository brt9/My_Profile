<?php

namespace App\Providers;

use App\Services\Calendar\GoogleCalendarClient;
use App\Services\Duolingo\DuolingoClient;
use App\Services\Duolingo\DuolingoProvider;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GoogleCalendarClient::class, fn (): GoogleCalendarClient => GoogleCalendarClient::fromConfig());
        $this->app->singleton(DuolingoProvider::class, fn (): DuolingoProvider => DuolingoClient::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('telemetry-agent', function (Request $request): Limit {
            $tokenHash = hash('sha256', (string) $request->bearerToken());

            return Limit::perMinute(60)->by($tokenHash);
        });

        Vite::prefetch(concurrency: 3);
        Carbon::setLocale('pt_BR');
        // Dflydev\DotAccessData\Data does not provide a setLocale method; use PHP's setlocale instead
        setlocale(LC_ALL, 'pt_BR.UTF-8');
    }
}
