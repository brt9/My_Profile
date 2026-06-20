<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Telemetry\IntegrationHealthMonitor;
use App\Services\Weather\WeatherClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class WeatherController extends Controller
{
    public function show(Request $request, IntegrationHealthMonitor $health): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $startedAt = microtime(true);
        try {
            $weather = (new WeatherClient)->byCoords(
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                'Sua localização',
                'browser',
            );
            $health->success('weather', $startedAt);

            return response()->json([
                'status' => 'available',
                'data' => $weather,
                'meta' => [
                    'source' => 'open-meteo',
                    'collected_at' => $weather['updated_at'] ?? null,
                ],
                'error' => null,
            ]);
        } catch (Throwable) {
            $health->failure('weather', $startedAt);

            return response()->json([
                'status' => 'error',
                'data' => null,
                'meta' => ['source' => 'open-meteo'],
                'error' => ['message' => 'Não foi possível atualizar o clima agora.'],
            ], 503);
        }
    }
}
