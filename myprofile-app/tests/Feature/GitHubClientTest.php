<?php

use App\Services\GitHub\GitHubClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('github dashboard maps public profile repositories and activity', function () {
    Cache::flush();
    Http::fake([
        'api.github.com/users/brt9' => Http::response([
            'login' => 'brt9',
            'name' => 'Pedro Felipe',
            'avatar_url' => 'https://example.com/avatar.png',
            'bio' => 'Developer',
            'html_url' => 'https://github.com/brt9',
            'followers' => 5,
            'public_repos' => 2,
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
    ]);

    $dashboard = (new GitHubClient('brt9'))->dashboard();

    expect($dashboard['profile']['public_repositories'])->toBe(2)
        ->and($dashboard['repositories'][0]['name'])->toBe('My_Profile')
        ->and($dashboard['activity']['recent_pushes'])->toBe(1)
        ->and($dashboard['activity']['main_language'])->toBe('PHP');
});
