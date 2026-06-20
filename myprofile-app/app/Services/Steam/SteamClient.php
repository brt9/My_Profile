<?php

declare(strict_types=1);

namespace App\Services\Steam;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class SteamClient
{
    private const BASE = 'https://api.steampowered.com';

    public function __construct(
        private readonly string $key,
        private readonly string $steamId,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('services.steam.key'),
            (string) config('services.steam.id'),
        );
    }

    /** Header padrão grande da loja */
    private function headerImage(int $appId): string
    {
        return "https://cdn.cloudflare.steamstatic.com/steam/apps/{$appId}/header.jpg";
    }

    /** Capsule grande (bom para fallback) */
    private function capsuleImage(int $appId): string
    {
        return "https://cdn.cloudflare.steamstatic.com/steam/apps/{$appId}/capsule_616x353.jpg";
    }

    /**
     * Jogos jogados recentemente
     *
     * @return array<int, array{appid:int,name:string,playtime:int,image:string,capsule:string}>
     */
    public function recentGames(int $limit = 6): array
    {
        return Cache::remember($this->recentCacheKey(), 900, function () use ($limit) {
            $res = $this->request()->get(self::BASE.'/IPlayerService/GetRecentlyPlayedGames/v1', [
                'key' => $this->key,
                'steamid' => $this->steamId,
                'format' => 'json',
            ])->throw()->json('response.games') ?? [];

            $mapped = array_map(function (array $g): array {
                $appid = (int) $g['appid'];

                return [
                    'appid' => $appid,
                    'name' => (string) $g['name'],
                    'playtime' => (int) ($g['playtime_2weeks'] ?? $g['playtime_forever'] ?? 0),
                    'image' => $this->headerImage($appid),
                    'capsule' => $this->capsuleImage($appid),
                ];
            }, $res);

            return array_slice($mapped, 0, $limit);
        });
    }

    /**
     * Resumo da biblioteca (top jogados)
     *
     * @return array{game_count:int,total_minutes:int,top:array<int, array{appid:int,name:string,playtime:int,image:string,capsule:string}>}
     */
    public function librarySummary(int $top = 6): array
    {
        return Cache::remember($this->libraryCacheKey(), 900, function () use ($top) {
            $res = $this->request()->get(self::BASE.'/IPlayerService/GetOwnedGames/v1', [
                'key' => $this->key,
                'steamid' => $this->steamId,
                'include_appinfo' => 1,
                'include_played_free_games' => 1,
            ])->throw()->json('response') ?? [];

            $games = $res['games'] ?? [];
            $total = 0;

            $mapped = array_map(function (array $g) use (&$total): array {
                $appid = (int) $g['appid'];
                $minutes = (int) ($g['playtime_forever'] ?? 0);
                $total += $minutes;

                return [
                    'appid' => $appid,
                    'name' => (string) ($g['name'] ?? 'Jogo'),
                    'playtime' => $minutes,
                    'image' => $this->headerImage($appid),
                    'capsule' => $this->capsuleImage($appid),
                ];
            }, $games);

            usort($mapped, fn ($a, $b) => $b['playtime'] <=> $a['playtime']);

            return [
                'game_count' => (int) ($res['game_count'] ?? 0),
                'total_minutes' => $total,
                'top' => array_slice($mapped, 0, $top),
            ];
        });
    }

    /**
     * Jogo atual (se estiver in-game e o perfil permitir)
     *
     * @return array{appid:int,name:string,image:string}|null
     */
    public function currentGame(): ?array
    {
        // cache curtinho pra não bater sempre
        return Cache::remember($this->currentGameCacheKey(), 30, function () {
            $player = $this->request()->get(self::BASE.'/ISteamUser/GetPlayerSummaries/v2', [
                'key' => $this->key,
                'steamids' => $this->steamId,
            ])->throw()->json('response.players.0');

            if (! $player || empty($player['gameid'])) {
                return null;
            }

            $appid = (int) $player['gameid'];

            return [
                'appid' => $appid,
                'name' => (string) ($player['gameextrainfo'] ?? 'Jogando agora'),
                'image' => $this->headerImage($appid),
            ];
        });
    }

    /**
     * Conquistas (recentes) do app destacado
     *
     * @return array<int, array{name:string,achieved:bool,icon:string,unlock_time:int}>
     */
    public function achievements(int $appId, int $limit = 8): array
    {
        $key = $this->achievementsCacheKey($appId);

        return Cache::remember($key, 900, function () use ($appId, $limit) {
            // PT-BR primeiro, com fallback
            $lang = 'brazilian';

            $player = $this->request()->get(self::BASE.'/ISteamUserStats/GetPlayerAchievements/v1', [
                'key' => $this->key,
                'steamid' => $this->steamId,
                'appid' => $appId,
                'l' => $lang,
            ])->throw()->json('playerstats.achievements') ?? [];

            $schema = $this->request()->get(self::BASE.'/ISteamUserStats/GetSchemaForGame/v2', [
                'key' => $this->key,
                'appid' => $appId,
                'l' => $lang,
            ])->throw()->json('game.availableGameStats.achievements') ?? [];

            // Fallback automático se o jogo não tiver PT-BR
            if (! $schema && $lang === 'brazilian') {
                $player = $this->request()->get(self::BASE.'/ISteamUserStats/GetPlayerAchievements/v1', [
                    'key' => $this->key,
                    'steamid' => $this->steamId,
                    'appid' => $appId,
                    'l' => 'portuguese',
                ])->throw()->json('playerstats.achievements') ?? [];

                $schema = $this->request()->get(self::BASE.'/ISteamUserStats/GetSchemaForGame/v2', [
                    'key' => $this->key,
                    'appid' => $appId,
                    'l' => 'portuguese',
                ])->throw()->json('game.availableGameStats.achievements') ?? [];
            }

            // Se ainda não houver, cai para inglês
            if (! $schema) {
                $schema = $this->request()->get(self::BASE.'/ISteamUserStats/GetSchemaForGame/v2', [
                    'key' => $this->key,
                    'appid' => $appId,
                    'l' => 'english',
                ])->throw()->json('game.availableGameStats.achievements') ?? [];
            }

            $info = [];
            foreach ($schema as $s) {
                $name = (string) $s['name'];
                $info[$name] = [
                    'display' => (string) ($s['displayName'] ?? $name),
                    'icon' => (string) ($s['icon'] ?? ''),
                    'icongray' => (string) ($s['icongray'] ?? ''),
                ];
            }

            $merged = [];
            foreach ($player as $a) {
                $api = (string) $a['apiname'];
                $achieved = (bool) ($a['achieved'] ?? false);
                $unlock = (int) ($a['unlocktime'] ?? 0);
                $meta = $info[$api] ?? ['display' => $api, 'icon' => '', 'icongray' => ''];

                $merged[] = [
                    'name' => $meta['display'],                    // <<< Nome traduzido
                    'achieved' => $achieved,
                    'icon' => $achieved ? $meta['icon'] : ($meta['icongray'] ?: $meta['icon']),
                    'unlock_time' => $unlock,
                ];
            }

            $merged = array_values(array_filter($merged, fn ($x) => $x['achieved']));
            usort($merged, fn ($a, $b) => $b['unlock_time'] <=> $a['unlock_time']);

            return array_slice($merged, 0, $limit);
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function cachedRecentGames(): array
    {
        $games = Cache::get($this->recentCacheKey(), []);

        return is_array($games) ? $games : [];
    }

    /** @return array{game_count:int,total_minutes:int,top:array<int, array<string, mixed>>} */
    public function cachedLibrarySummary(): array
    {
        $summary = Cache::get($this->libraryCacheKey());

        return is_array($summary)
            ? $summary
            : ['game_count' => 0, 'total_minutes' => 0, 'top' => []];
    }

    /** @return array<string, mixed>|null */
    public function cachedCurrentGame(): ?array
    {
        $game = Cache::get($this->currentGameCacheKey());

        return is_array($game) ? $game : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function cachedAchievements(int $appId): array
    {
        $achievements = Cache::get($this->achievementsCacheKey($appId), []);

        return is_array($achievements) ? $achievements : [];
    }

    private function recentCacheKey(): string
    {
        return "steam:recent:{$this->steamId}";
    }

    private function libraryCacheKey(): string
    {
        return "steam:owned:{$this->steamId}";
    }

    private function currentGameCacheKey(): string
    {
        return "steam:now:{$this->steamId}";
    }

    private function achievementsCacheKey(int $appId): string
    {
        return "steam:achievements:{$this->steamId}:{$appId}";
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout(8)
            ->retry(1, 250)
            ->withHeaders(['User-Agent' => 'Pedro-Felipe-Portfolio']);
    }
}
