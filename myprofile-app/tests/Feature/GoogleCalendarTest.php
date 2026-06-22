<?php

use App\Jobs\SyncGoogleCalendar;
use App\Models\CalendarPublicEvent;
use App\Models\GoogleCalendarConnection;
use App\Models\User;
use App\Services\Calendar\CalendarDashboard;
use App\Services\Calendar\CalendarEventProjector;
use App\Services\Calendar\CalendarSyncService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config()->set('services.google_calendar.enabled', true);
    config()->set('services.google_calendar.client_id', 'calendar-client');
    config()->set('services.google_calendar.client_secret', 'calendar-secret');
    config()->set('services.google_calendar.redirect_uri', 'http://localhost/admin/calendar/callback');
    config()->set('services.google_calendar.calendar_ids', ['primary']);
    config()->set('services.google_calendar.public_event_ids', ['public-event']);
    config()->set('services.google_calendar.show_event_titles', false);
    config()->set('portfolio.integrations.calendar', true);
});

test('calendar projection publishes sanitized google titles when explicitly enabled', function () {
    config()->set('services.google_calendar.show_event_titles', true);
    $projector = app(CalendarEventProjector::class);
    $base = [
        'start' => ['dateTime' => '2026-06-22T12:00:00Z'],
        'end' => ['dateTime' => '2026-06-22T13:00:00Z'],
        'summary' => '  <b>Reunião com cliente</b>  ',
    ];

    $visible = $projector->project($base + ['id' => 'regular-event'], 'primary', []);
    $private = $projector->project($base + ['id' => 'private-event', 'visibility' => 'private'], 'primary', []);

    expect($visible['public_title'])->toBe('Reunião com cliente')
        ->and($visible['category'])->toBe('projeto')
        ->and($private['public_title'])->toBe('Reunião com cliente');
});

test('calendar projection is private by default and only publishes allowlisted titles', function () {
    $projector = app(CalendarEventProjector::class);
    $base = [
        'start' => ['dateTime' => '2026-06-22T12:00:00Z'],
        'end' => ['dateTime' => '2026-06-22T13:00:00Z'],
        'summary' => '<b>Segredo interno</b>',
    ];

    $private = $projector->project($base + [
        'id' => 'public-event',
        'visibility' => 'private',
        'extendedProperties' => ['private' => ['portfolio_type' => 'reuniao']],
    ], 'primary', ['public-event']);
    $notAllowlisted = $projector->project($base + ['id' => 'other-event'], 'primary', ['public-event']);
    $public = $projector->project($base + [
        'id' => 'public-event',
        'extendedProperties' => ['private' => ['portfolio_type' => 'estudo']],
    ], 'primary', ['public-event']);

    expect($private['public_title'])->toBe('Compromisso')
        ->and($private['category'])->toBe('ocupado')
        ->and($notAllowlisted['public_title'])->toBe('Compromisso')
        ->and($public['public_title'])->toBe('Segredo interno')
        ->and($public['category'])->toBe('estudo')
        ->and($public)->not->toHaveKeys(['description', 'attendees', 'location', 'hangoutLink']);
});

test('only configured administrator can start calendar oauth', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $other = User::factory()->create(['email' => 'other@example.com']);
    config()->set('portfolio.admin_email', $admin->email);

    $this->actingAs($other)->get(route('calendar.connect'))->assertForbidden();

    $response = $this->actingAs($admin)->get(route('calendar.connect'));
    $response->assertRedirectContains('https://accounts.google.com/o/oauth2/v2/auth');
    expect(session('google_calendar_oauth_state'))->toBeString()->not->toBeEmpty();
});

test('calendar oauth requests write scope when google writes are enabled', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    config()->set('portfolio.admin_email', $admin->email);
    config()->set('services.google_calendar.public_event_ids', []);
    config()->set('services.google_calendar.write_enabled', true);

    $location = $this->actingAs($admin)->get(route('calendar.connect'))->headers->get('Location');

    expect(urldecode((string) $location))->toContain('https://www.googleapis.com/auth/calendar.events')
        ->not->toContain('calendar.events.readonly');
});

test('calendar oauth falls back to read only scope when google writes are disabled', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    config()->set('portfolio.admin_email', $admin->email);
    config()->set('services.google_calendar.write_enabled', false);

    $location = $this->actingAs($admin)->get(route('calendar.connect'))->headers->get('Location');

    expect(urldecode((string) $location))->toContain('https://www.googleapis.com/auth/calendar.events.readonly');
});

test('default calendar sync keeps overlapping appointments separate without storing titles', function () {
    config()->set('services.google_calendar.public_event_ids', []);
    $user = User::factory()->create();
    $connection = GoogleCalendarConnection::query()->create([
        'user_id' => $user->id,
        'refresh_token' => 'refresh-token',
        'calendar_ids' => ['primary'],
        'status' => 'connected',
    ]);
    Http::fake(function ($request) {
        if (str_contains($request->url(), 'oauth2.googleapis.com/token')) {
            return Http::response(['access_token' => 'access-token']);
        }

        $day = CarbonImmutable::now()->addDay()->startOfDay();

        return Http::response(['items' => [
            [
                'id' => 'all-day-event',
                'summary' => 'Título privado do dia inteiro',
                'start' => ['date' => $day->toDateString()],
                'end' => ['date' => $day->addDay()->toDateString()],
            ],
            [
                'id' => 'timed-event',
                'summary' => 'Título privado com horário',
                'start' => ['dateTime' => $day->addHours(14)->toIso8601String()],
                'end' => ['dateTime' => $day->addHours(15)->toIso8601String()],
            ],
        ]]);
    });

    expect(app(CalendarSyncService::class)->sync($connection))->toBe(2)
        ->and(CalendarPublicEvent::query()->pluck('public_title')->all())->toBe(['Compromisso', 'Compromisso']);
    Http::assertSent(fn ($request): bool => str_contains($request->url(), '/events'));
    Http::assertNotSent(fn ($request): bool => str_ends_with($request->url(), '/calendar/v3/freeBusy'));
});

test('oauth callback encrypts refresh token and queues first synchronization', function () {
    Queue::fake();
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    config()->set('portfolio.admin_email', $admin->email);
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'temporary-access',
            'refresh_token' => 'long-lived-refresh',
            'scope' => 'https://www.googleapis.com/auth/calendar.readonly',
        ]),
    ]);

    $this->actingAs($admin)
        ->withSession(['google_calendar_oauth_state' => 'expected-state'])
        ->get(route('calendar.callback', ['state' => 'expected-state', 'code' => 'oauth-code']))
        ->assertRedirect('/#agenda');

    $connection = GoogleCalendarConnection::query()->sole();
    expect($connection->refresh_token)->toBe('long-lived-refresh')
        ->and(DB::table('google_calendar_connections')->value('refresh_token'))->not->toBe('long-lived-refresh');
    Queue::assertPushed(SyncGoogleCalendar::class, fn ($job): bool => $job->connectionId === $connection->id);
});

test('calendar sync persists only safe projection and retains data when token is revoked', function () {
    $user = User::factory()->create();
    $connection = GoogleCalendarConnection::query()->create([
        'user_id' => $user->id,
        'refresh_token' => 'refresh-token',
        'calendar_ids' => ['primary'],
        'status' => 'connected',
    ]);
    $revoked = false;
    Http::fake(function ($request) use (&$revoked) {
        if (str_contains($request->url(), 'oauth2.googleapis.com/token')) {
            return $revoked
                ? Http::response(['error' => 'invalid_grant'], 400)
                : Http::response(['access_token' => 'access-token']);
        }

        return Http::response([
            'items' => [
                [
                    'id' => 'private-event', 'summary' => 'Reunião sigilosa', 'visibility' => 'private',
                    'start' => ['dateTime' => now()->addDay()->toIso8601String()],
                    'end' => ['dateTime' => now()->addDay()->addHour()->toIso8601String()],
                ],
                [
                    'id' => 'public-event', 'summary' => 'Estudo de Laravel',
                    'extendedProperties' => ['private' => ['portfolio_type' => 'estudo']],
                    'start' => ['dateTime' => now()->addDays(2)->toIso8601String()],
                    'end' => ['dateTime' => now()->addDays(2)->addHour()->toIso8601String()],
                ],
            ],
        ]);
    });

    expect(app(CalendarSyncService::class)->sync($connection))->toBe(2)
        ->and(CalendarPublicEvent::query()->pluck('public_title')->all())
        ->toContain('Compromisso', 'Estudo de Laravel')
        ->not->toContain('Reunião sigilosa');

    $revoked = true;
    expect(fn () => app(CalendarSyncService::class)->sync($connection->fresh()))->toThrow(RequestException::class);
    expect($connection->fresh()->status)->toBe('reauth_required')
        ->and(CalendarPublicEvent::query()->count())->toBe(2);
});

test('calendar feature flag removes its interface', function () {
    config()->set('portfolio.integrations.calendar', false);

    $this->get('/')->assertOk()->assertDontSee('Google Agenda');
});

test('administrator can revoke calendar access and remove local projection', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    config()->set('portfolio.admin_email', $admin->email);
    $connection = GoogleCalendarConnection::query()->create([
        'user_id' => $admin->id,
        'refresh_token' => 'refresh-token',
        'calendar_ids' => ['primary'],
        'status' => 'connected',
    ]);
    CalendarPublicEvent::query()->create([
        'connection_id' => $connection->id,
        'provider_event_key' => hash('sha256', 'event'),
        'public_title' => 'Compromisso',
        'category' => 'ocupado',
        'status' => 'confirmado',
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
        'synced_at' => now(),
    ]);
    Http::fake(['oauth2.googleapis.com/revoke' => Http::response([], 200)]);

    $this->actingAs($admin)->delete(route('calendar.revoke'))->assertRedirect('/#agenda');

    expect(GoogleCalendarConnection::query()->count())->toBe(0)
        ->and(CalendarPublicEvent::query()->count())->toBe(0);
});

test('calendar dashboard presents a rolling seven day window', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-20 12:00:00', 'America/Fortaleza'));
    config()->set('portfolio.presentation_timezone', 'America/Fortaleza');
    $user = User::factory()->create();
    $connection = GoogleCalendarConnection::query()->create([
        'user_id' => $user->id,
        'refresh_token' => 'refresh-token',
        'calendar_ids' => ['primary'],
        'status' => 'connected',
    ]);
    CalendarPublicEvent::query()->create([
        'connection_id' => $connection->id,
        'provider_event_key' => hash('sha256', 'next-monday'),
        'public_title' => 'Compromisso',
        'category' => 'ocupado',
        'status' => 'confirmado',
        'starts_at' => CarbonImmutable::parse('2026-06-22 00:00:00', 'America/Fortaleza')->utc(),
        'ends_at' => CarbonImmutable::parse('2026-06-23 00:00:00', 'America/Fortaleza')->utc(),
        'all_day' => false,
        'synced_at' => now(),
    ]);

    $dashboard = app(CalendarDashboard::class)->forHome();

    expect($dashboard['range_label'])->toBe('20/06–26/06')
        ->and($dashboard['event_count'])->toBe(1)
        ->and(count($dashboard['days']))->toBe(7)
        ->and(count($dashboard['month_days']))->toBe(42)
        ->and($dashboard['month_label'])->toBe('Junho 2026')
        ->and(collect($dashboard['days'])->firstWhere('date', '2026-06-22')['events'][0]['title'])->toBe('Compromisso')
        ->and(collect($dashboard['month_days'])->firstWhere('date', '2026-06-22')['events'][0]['title'])->toBe('Compromisso')
        ->and(collect($dashboard['days'])->firstWhere('date', '2026-06-22')['events'][0]['time'])->toBe('Dia inteiro');

    CarbonImmutable::setTestNow();
});

test('calendar interface offers weekly gantt and monthly calendar without category filters', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-21 10:00:00', 'America/Fortaleza'));
    config()->set('portfolio.presentation_timezone', 'America/Fortaleza');
    $user = User::factory()->create();
    $connection = GoogleCalendarConnection::query()->create([
        'user_id' => $user->id,
        'refresh_token' => 'refresh-token',
        'calendar_ids' => ['primary'],
        'status' => 'connected',
        'last_synced_at' => now(),
    ]);

    foreach ([['09:00', '10:00'], ['14:30', '15:30']] as $index => [$start, $end]) {
        CalendarPublicEvent::query()->create([
            'connection_id' => $connection->id,
            'provider_event_key' => hash('sha256', 'appointment-'.$index),
            'public_title' => 'Compromisso',
            'category' => 'ocupado',
            'status' => 'confirmado',
            'starts_at' => CarbonImmutable::parse("2026-06-22 {$start}", 'America/Fortaleza')->utc(),
            'ends_at' => CarbonImmutable::parse("2026-06-22 {$end}", 'America/Fortaleza')->utc(),
            'synced_at' => now(),
        ]);
    }

    $this->get('/')
        ->assertOk()
        ->assertSee('Próximos 7 dias')
        ->assertSee('09:00–10:00')
        ->assertSee('14:30–15:30')
        ->assertSee('Disponível')
        ->assertSee('calendar-table', false)
        ->assertSee('calendar-gantt', false)
        ->assertSee('calendar-month-grid', false)
        ->assertSee('data-calendar-view-button="month"', false)
        ->assertDontSee('Filtrar agenda por categoria')
        ->assertDontSee('calendar-manager', false);

    CarbonImmutable::setTestNow();
});
