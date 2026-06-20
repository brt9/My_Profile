<?php

use App\Services\Steam\SteamClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('steam client maps games library and translated achievements', function () {
    Cache::flush();
    Http::fake([
        'api.steampowered.com/IPlayerService/GetRecentlyPlayedGames/v1*' => Http::response([
            'response' => ['games' => [
                ['appid' => 10, 'name' => 'Jogo recente', 'playtime_2weeks' => 120],
            ]],
        ]),
        'api.steampowered.com/IPlayerService/GetOwnedGames/v1*' => Http::response([
            'response' => [
                'game_count' => 1,
                'games' => [['appid' => 10, 'name' => 'Jogo recente', 'playtime_forever' => 600]],
            ],
        ]),
        'api.steampowered.com/ISteamUserStats/GetPlayerAchievements/v1*' => Http::response([
            'playerstats' => ['achievements' => [
                ['apiname' => 'FIRST_WIN', 'achieved' => 1, 'unlocktime' => 1700000000],
            ]],
        ]),
        'api.steampowered.com/ISteamUserStats/GetSchemaForGame/v2*' => Http::response([
            'game' => ['availableGameStats' => ['achievements' => [
                ['name' => 'FIRST_WIN', 'displayName' => 'Primeira vitória', 'icon' => 'https://example.com/icon.png'],
            ]]],
        ]),
    ]);

    $steam = new SteamClient('key', 'steam-id');

    expect($steam->recentGames()[0]['playtime'])->toBe(120)
        ->and($steam->librarySummary()['total_minutes'])->toBe(600)
        ->and($steam->achievements(10)[0]['name'])->toBe('Primeira vitória');
});
