<?php

use App\Http\Controllers\GitHubContributionController;
use App\Http\Controllers\IntegrationHealthController;
use App\Http\Controllers\TelemetryController;
use App\Http\Controllers\VisitorMapController;
use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::prefix('telemetry')->as('telemetry.')->group(function (): void {
    Route::post('push', [TelemetryController::class, 'store'])
        ->middleware('throttle:telemetry-agent')
        ->name('push'); // POST /api/telemetry/push

    Route::get('', [TelemetryController::class, 'show'])
        ->name('show'); // GET /api/telemetry

    Route::get('latest', [TelemetryController::class, 'show'])
        ->name('latest');

    Route::get('history', [TelemetryController::class, 'history'])
        ->middleware('throttle:60,1')
        ->name('history');
});

Route::get('health/integrations', IntegrationHealthController::class)
    ->middleware('throttle:30,1')
    ->name('health.integrations');

Route::get('github/contributions', GitHubContributionController::class)
    ->middleware('throttle:30,1')
    ->name('github.contributions');

Route::post('weather/location', [WeatherController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('weather.location');

Route::get('weather/visitor', [WeatherController::class, 'visitor'])
    ->middleware('throttle:30,1')
    ->name('weather.visitor');

Route::prefix('visitors/map')->as('visitors.map.')->group(function (): void {
    Route::get('', [VisitorMapController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('index');
    Route::post('', [VisitorMapController::class, 'store'])
        ->middleware('throttle:12,1')
        ->name('store');
    Route::delete('', [VisitorMapController::class, 'destroy'])
        ->middleware('throttle:12,1')
        ->name('destroy');
});
