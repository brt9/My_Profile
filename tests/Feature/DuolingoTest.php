<?php

use App\Models\DuolingoSnapshot;
use App\Services\Duolingo\DuolingoClient;
use App\Services\Duolingo\DuolingoProvider;
use App\Services\Duolingo\DuolingoSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    config()->set('services.duolingo.enabled', true);
    config()->set('services.duolingo.username', 'pedro-publico');
    config()->set('portfolio.integrations.duolingo', true);
});

test('duolingo client normalizes public profile and tolerates missing optional fields', function () {
    Http::fake([
        'duolingo.test/users*' => Http::response([
            'users' => [[
                'username' => 'pedro-publico',
                'totalXp' => 1234,
                'streak' => 8,
                'courses' => [
                    ['learningLanguage' => 'en', 'title' => 'Inglês', 'xp' => 900],
                    ['title' => 'Sem código'],
                ],
            ]],
        ]),
    ]);

    $profile = (new DuolingoClient('pedro-publico', 'https://duolingo.test/users'))->profile();

    expect($profile['total_xp'])->toBe(1234)
        ->and($profile['streak'])->toBe(8)
        ->and($profile['courses'])->toHaveCount(1)
        ->and($profile['courses'][0])->toMatchArray(['language' => 'en', 'language_name' => 'Inglês', 'xp' => 900]);
});

test('duolingo sync stores at most one snapshot per course and day', function () {
    $provider = new class implements DuolingoProvider
    {
        public int $xp = 100;

        public function profile(): array
        {
            return [
                'username' => 'pedro-publico', 'total_xp' => $this->xp, 'streak' => 3,
                'courses' => [['language' => 'en', 'language_name' => 'Inglês', 'xp' => $this->xp]],
            ];
        }
    };
    app()->instance(DuolingoProvider::class, $provider);

    expect(app(DuolingoSyncService::class)->sync())->toBe(1);
    $provider->xp = 150;
    expect(app(DuolingoSyncService::class)->sync())->toBe(1)
        ->and(DuolingoSnapshot::query()->count())->toBe(1)
        ->and(DuolingoSnapshot::query()->value('course_xp'))->toBe(150);
});

test('duolingo home uses snapshots without calling provider', function () {
    DuolingoSnapshot::query()->create([
        'username' => 'pedro-publico', 'language' => 'en', 'language_name' => 'Inglês',
        'course_xp' => 321, 'total_xp' => 654, 'streak' => 4,
        'snapshot_date' => now()->toDateString(), 'collected_at' => now(),
    ]);
    Http::fake();

    $this->get('/')
        ->assertOk()
        ->assertSee('Progresso no Duolingo')
        ->assertSee('321')
        ->assertSee('brand-mark-duolingo', false)
        ->assertSee('duolingo-dashboard', false)
        ->assertSee('Fonte pública não oficial');
    Http::assertNothingSent();
});

test('duolingo chart uses date and xp axes without textual history cards', function () {
    foreach ([
        ['date' => now()->subDay()->toDateString(), 'xp' => 2800],
        ['date' => now()->toDateString(), 'xp' => 2984],
    ] as $point) {
        DuolingoSnapshot::query()->create([
            'username' => 'pedro-publico',
            'language' => 'en',
            'language_name' => 'Inglês',
            'course_xp' => $point['xp'],
            'total_xp' => $point['xp'],
            'streak' => 4,
            'snapshot_date' => $point['date'],
            'collected_at' => now(),
        ]);
    }

    $this->get('/')
        ->assertOk()
        ->assertSee('duolingo-chart-axis', false)
        ->assertSee('duolingo-chart-date', false)
        ->assertSee('+184 XP')
        ->assertSee('2.984')
        ->assertDontSee('duolingo-chart-records', false)
        ->assertDontSee('Consultar histórico textual');
});

test('duolingo feature flag disables its interface', function () {
    config()->set('portfolio.integrations.duolingo', false);

    $this->get('/')->assertOk()->assertDontSee('Progresso no Duolingo');
});

test('duolingo circuit breaker opens after repeated provider failures', function () {
    Http::fake(['duolingo.test/users*' => Http::response(['error' => 'unavailable'], 503)]);
    $client = new DuolingoClient('pedro-publico', 'https://duolingo.test/users');

    foreach (range(1, 3) as $_) {
        try {
            $client->profile();
        } catch (Throwable) {
            // As três falhas alimentam o circuit breaker.
        }
    }
    $requestsBeforeOpenCall = count(Http::recorded());

    expect(fn () => $client->profile())->toThrow(RuntimeException::class, 'temporariamente suspensa')
        ->and(count(Http::recorded()))->toBe($requestsBeforeOpenCall);
});
