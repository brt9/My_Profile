<?php

use App\Http\Controllers\IntegrationHealthController;
use App\Http\Controllers\TelemetryController;
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

Route::post('weather/location', [WeatherController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('weather.location');
