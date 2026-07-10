<?php

use App\Services\GitHub\GitHubClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('github dashboard maps public profile repositories and activity', function () {
    Cache::flush();
    $today = now()->utc()->toDateString();
    $contributions = <<<HTML
        <table>
            <tr>
                <td data-date="{$today}" id="contribution-day-test" data-level="2" class="ContributionCalendar-day"></td>
                <tool-tip for="contribution-day-test">3 contributions on this day.</tool-tip>
            </tr>
        </table>
        HTML;
    Http::fake([
        'api.github.com/users/brt9' => Http::response([
            'login' => 'brt9',
            'name' => 'Pedro Felipe',
            'avatar_url' => 'https://example.com/avatar.png',
            'bio' => 'Developer',
            'html_url' => 'https://github.com/brt9',
            'followers' => 5,
            'public_repos' => 2,
            'created_at' => '2019-04-29T00:23:58Z',
        ]),
        'api.github.com/users/brt9/repos*' => Http::response([
            [
                'name' => 'My_Profile',
                'description' => 'Portfolio',
                'language' => 'PHP',
                'stargazers_count' => 1,
                'forks_count' => 0,
                'updated_at' => now()->toIso8601String(),
                'html_url' => 'https://github.com/brt9/My_Profile',
                'fork' => false,
            ],
        ]),
        'api.github.com/users/brt9/events/public*' => Http::response([
            ['type' => 'PushEvent', 'created_at' => now()->toIso8601String()],
        ]),
        'github.com/users/brt9/contributions*' => Http::response($contributions, 200, ['Content-Type' => 'text/html']),
    ]);

    $dashboard = (new GitHubClient('brt9'))->dashboard();

    expect($dashboard['profile']['public_repositories'])->toBe(2)
        ->and($dashboard['repositories'][0]['name'])->toBe('My_Profile')
        ->and($dashboard['activity']['recent_pushes'])->toBe(1)
        ->and($dashboard['activity']['main_language'])->toBe('PHP')
        ->and($dashboard['activity']['calendar']['source'])->toBe('github_profile')
        ->and($dashboard['activity']['calendar']['total'])->toBe(3)
        ->and($dashboard['activity']['calendar']['active_days'])->toBe(1)
        ->and(collect($dashboard['activity']['calendar']['weeks'])->pluck('year')->filter()->values()->all())
        ->toBe([(string) now()->utc()->year]);
});

test('github contribution calendar falls back to public events', function () {
    Cache::flush();
    Http::fake([
        'api.github.com/users/brt9' => Http::response([
            'login' => 'brt9',
            'name' => 'Pedro Felipe',
            'avatar_url' => 'https://example.com/avatar.png',
            'bio' => null,
            'html_url' => 'https://github.com/brt9',
            'followers' => 5,
            'public_repos' => 2,
            'created_at' => '2019-04-29T00:23:58Z',
        ]),
        'api.github.com/users/brt9/repos*' => Http::response([]),
        'api.github.com/users/brt9/events/public*' => Http::response([
            ['type' => 'PushEvent', 'created_at' => now()->utc()->toIso8601String()],
        ]),
        'github.com/users/brt9/contributions*' => Http::response([], 503),
    ]);

    $calendar = (new GitHubClient('brt9'))->dashboard()['activity']['calendar'];

    expect($calendar['source'])->toBe('public_events')
        ->and($calendar['total'])->toBe(1)
        ->and(count($calendar['weeks']))->toBeGreaterThanOrEqual(52);
});

test('github contribution endpoint loads a complete selected year', function () {
    Cache::flush();
    config()->set('services.github.username', 'brt9');
    $contributions = <<<'HTML'
        <table>
            <tr>
                <td data-date="2025-01-01" id="contribution-day-2025-start" data-level="3" class="ContributionCalendar-day"></td>
                <tool-tip for="contribution-day-2025-start">7 contributions on this day.</tool-tip>
                <td data-date="2025-12-31" id="contribution-day-2025-end" data-level="0" class="ContributionCalendar-day"></td>
                <tool-tip for="contribution-day-2025-end">No contributions on this day.</tool-tip>
            </tr>
        </table>
        HTML;
    Http::fake([
        'github.com/users/brt9/contributions*' => Http::response($contributions, 200, ['Content-Type' => 'text/html']),
    ]);

    $this->getJson('/api/github/contributions?year=2025')
        ->assertOk()
        ->assertJsonPath('status', 'available')
        ->assertJsonPath('data.year', 2025)
        ->assertJsonPath('data.total', 7)
        ->assertJsonPath('data.from', '2025-01-01')
        ->assertJsonPath('data.to', '2025-12-31');

    $this->getJson('/api/github/contributions?year='.(now()->utc()->year + 1))->assertUnprocessable();
});
