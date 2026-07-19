<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Telemetry\IntegrationHealthMonitor;
use App\Services\Weather\WeatherClient;
use Illuminate\View\View;
use Throwable;

final class WeatherPageController extends Controller
{
    public function __invoke(IntegrationHealthMonitor $health): View
    {
        $portfolio = config('portfolio');
        $weatherNatal = null;
        $weatherEnabled = (bool) ($portfolio['integrations']['weather'] ?? false);

        if ($weatherEnabled && ! app()->environment('testing')) {
            $weather = new WeatherClient;
            $weatherNatal = $weather->cachedByCoords(-5.795, -35.209);

            defer(function () use ($weather, $health): void {
                $startedAt = microtime(true);

                try {
                    $weather->byCoords(-5.795, -35.209, 'Natal, RN');
                    $health->success('weather', $startedAt);
                } catch (Throwable) {
                    $health->failure('weather', $startedAt);
                }
            });
        }

        return view('weather', [
            'portfolio' => $portfolio,
            'weatherEnabled' => $weatherEnabled,
            'weatherNatal' => $weatherNatal,
            'title' => 'Laboratório de clima e geolocalização — '.$portfolio['name'],
            'metaDescription' => 'Estudo de caso sobre integração meteorológica com Open-Meteo, geolocalização consentida, cache e persistência de dados.',
        ]);
    }
}
