<?php

declare(strict_types=1);

namespace App\Services\GitHub;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class GitHubClient
{
    private const BASE_URL = 'https://api.github.com';

    public function __construct(
        private readonly string $username,
        private readonly ?string $token = null,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('services.github.username', 'brt9'),
            config('services.github.token'),
        );
    }

    /**
     * @return array{profile: array<string, mixed>, repositories: array<int, array<string, mixed>>, activity: array<string, mixed>}
     */
    public function dashboard(): array
    {
        return Cache::remember($this->cacheKey(), now()->addMinutes(30), function (): array {
            $profile = $this->request()->get(self::BASE_URL."/users/{$this->username}")->throw()->json();
            $repositories = $this->request()->get(self::BASE_URL."/users/{$this->username}/repos", [
                'sort' => 'updated',
                'direction' => 'desc',
                'per_page' => 8,
            ])->throw()->json();
            $events = $this->request()->get(self::BASE_URL."/users/{$this->username}/events/public", [
                'per_page' => 30,
            ])->throw()->json();

            $repositories = collect($repositories)
                ->reject(fn (array $repository): bool => (bool) ($repository['fork'] ?? false))
                ->take(4)
                ->map(fn (array $repository): array => [
                    'name' => $repository['name'],
                    'description' => $repository['description'],
                    'language' => $repository['language'],
                    'stars' => (int) $repository['stargazers_count'],
                    'forks' => (int) $repository['forks_count'],
                    'updated_at' => $repository['updated_at'],
                    'url' => $repository['html_url'],
                ])->values()->all();

            $pushEvents = collect($events)->where('type', 'PushEvent')->values();
            $languages = collect($repositories)->pluck('language')->filter()->countBy()->sortDesc();

            return [
                'profile' => [
                    'login' => $profile['login'],
                    'name' => $profile['name'],
                    'avatar' => $profile['avatar_url'],
                    'bio' => $profile['bio'],
                    'url' => $profile['html_url'],
                    'followers' => (int) $profile['followers'],
                    'public_repositories' => (int) $profile['public_repos'],
                ],
                'repositories' => $repositories,
                'activity' => [
                    'recent_events' => count($events),
                    'recent_pushes' => $pushEvents->count(),
                    'last_push_at' => Arr::get($pushEvents->first(), 'created_at'),
                    'main_language' => $languages->keys()->first(),
                ],
            ];
        });
    }

    /**
     * Retorna somente o snapshot local, sem bloquear a página com uma chamada externa.
     *
     * @return array{profile: array<string, mixed>, repositories: array<int, array<string, mixed>>, activity: array<string, mixed>}|null
     */
    public function cachedDashboard(): ?array
    {
        $dashboard = Cache::get($this->cacheKey());

        return is_array($dashboard) ? $dashboard : null;
    }

    private function cacheKey(): string
    {
        return "github:dashboard:{$this->username}";
    }

    private function request(): PendingRequest
    {
        $request = Http::acceptJson()
            ->withHeaders([
                'User-Agent' => 'Pedro-Felipe-Portfolio',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->timeout(8)
            ->retry(1, 250);

        return filled($this->token) ? $request->withToken($this->token) : $request;
    }
}
