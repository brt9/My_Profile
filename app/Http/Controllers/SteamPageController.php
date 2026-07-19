<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Steam\SteamClient;
use App\Services\Telemetry\IntegrationHealthMonitor;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Throwable;

final class SteamPageController extends Controller
{
    public function __invoke(IntegrationHealthMonitor $health): View
    {
        $portfolio = config('portfolio');
        $steamEnabled = filled(config('services.steam.key')) && filled(config('services.steam.id'));
        $recentGames = [];
        $steamSummary = ['game_count' => 0, 'total_minutes' => 0, 'top' => []];
        $featuredAchievements = [];
        $currentGame = null;
        $featuredGameId = null;

        if ($steamEnabled && ! app()->environment('testing')) {
            $steam = SteamClient::fromConfig();
            $currentGame = $steam->cachedCurrentGame();
            $recentGames = $steam->cachedRecentGames();
            $steamSummary = $steam->cachedLibrarySummary();
            $featuredGameId = Arr::get($currentGame, 'appid')
                ?? Arr::get($recentGames, '0.appid')
                ?? Arr::get($steamSummary, 'top.0.appid');
            $featuredAchievements = $featuredGameId
                ? $steam->cachedAchievements((int) $featuredGameId)
                : [];

            defer(function () use ($steam, $health): void {
                $startedAt = microtime(true);

                try {
                    $current = $steam->currentGame();
                    $recent = $steam->recentGames(6);
                    $library = $steam->librarySummary(6);
                    $featuredId = Arr::get($current, 'appid')
                        ?? Arr::get($recent, '0.appid')
                        ?? Arr::get($library, 'top.0.appid');

                    if ($featuredId) {
                        $steam->achievements((int) $featuredId, 8);
                    }

                    $health->success('steam', $startedAt);
                } catch (Throwable) {
                    $health->failure('steam', $startedAt);
                }
            });
        }

        return view('steam', [
            'portfolio' => $portfolio,
            'steamEnabled' => $steamEnabled,
            'steamProfile' => $steamEnabled
                ? 'https://steamcommunity.com/profiles/'.config('services.steam.id')
                : null,
            'currentGame' => $currentGame,
            'recentGames' => $recentGames,
            'steamSummary' => $steamSummary,
            'featuredGameId' => $featuredGameId,
            'featuredAchievements' => $featuredAchievements,
            'title' => 'Laboratório Steam Web API — '.$portfolio['name'],
            'metaDescription' => 'Estudo de caso sobre integração Laravel com a Steam Web API, cache, normalização e tolerância a falhas.',
        ]);
    }
}
