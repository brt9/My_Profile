<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GitHub\GitHubClient;
use App\Services\Steam\SteamClient;
use App\Services\Telemetry\IntegrationHealthMonitor;
use App\Services\Weather\WeatherClient;
use Illuminate\Support\Arr;
use Throwable;

final class HomeController extends Controller
{
    public function __invoke(IntegrationHealthMonitor $health)
    {
        $portfolio = config('portfolio');
        $github = null;
        $externalCallsEnabled = ! app()->environment('testing')
            || (bool) ($portfolio['integrations']['allow_in_tests'] ?? false);

        if ($externalCallsEnabled && ($portfolio['integrations']['github'] ?? true)) {
            $githubClient = GitHubClient::fromConfig();
            $github = $githubClient->cachedDashboard();

            defer(function () use ($githubClient, $health): void {
                $startedAt = microtime(true);
                try {
                    $githubClient->dashboard();
                    $health->success('github', $startedAt);
                } catch (Throwable) {
                    $health->failure('github', $startedAt);
                }
            });
        }

        $recent = [];
        $summary = ['game_count' => 0, 'total_minutes' => 0, 'top' => []];
        $achievements = [];
        $currentGame = null;
        $featuredGameId = null;
        $steamEnabled = filled(config('services.steam.key')) && filled(config('services.steam.id'));

        if ($steamEnabled && $externalCallsEnabled) {
            $steam = SteamClient::fromConfig();
            $currentGame = $steam->cachedCurrentGame();
            $recent = $steam->cachedRecentGames();
            $summary = $steam->cachedLibrarySummary();

            $featuredGameId = Arr::get($currentGame, 'appid')
                ?? Arr::get($recent, '0.appid')
                ?? Arr::get($summary, 'top.0.appid');

            $achievements = $featuredGameId
                ? $steam->cachedAchievements((int) $featuredGameId)
                : [];

            defer(function () use ($steam, $health): void {
                $startedAt = microtime(true);
                try {
                    $current = $steam->currentGame();
                    $recentGames = $steam->recentGames(6);
                    $library = $steam->librarySummary(6);
                    $featuredId = Arr::get($current, 'appid')
                        ?? Arr::get($recentGames, '0.appid')
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

        $weatherNatal = null;
        $weatherVisitor = null;

        if (($portfolio['integrations']['weather'] ?? false) && $externalCallsEnabled) {
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

        return view('home', [
            'portfolio' => $portfolio,
            'github' => $github,
            'steamEnabled' => $steamEnabled,
            'steamProfile' => $steamEnabled
                ? 'https://steamcommunity.com/profiles/'.config('services.steam.id')
                : null,
            'currentGame' => $currentGame,
            'recentGames' => $recent,
            'steamSummary' => $summary,
            'featuredGameId' => $featuredGameId,
            'featuredAchievements' => $achievements,
            'weatherNatal' => $weatherNatal,
            'weatherVisitor' => $weatherVisitor,
        ]);
    }
}
