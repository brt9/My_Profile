<?php

use App\Jobs\SyncCalendarEventToGoogle;
use App\Models\CalendarEvent;
use App\Models\GoogleCalendarConnection;
use App\Models\User;
use App\Services\Calendar\CalendarDashboard;
use App\Services\Calendar\CalendarEventManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('administrator manages local calendar events without google', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    config()->set('portfolio.admin_email', $admin->email);
    config()->set('services.google_calendar.write_enabled', false);

    $created = $this->actingAs($admin)->postJson(route('calendar.events.store'), [
        'title' => 'Reunião local',
        'category' => 'reuniao',
        'starts_at' => '2026-06-22T09:00',
        'ends_at' => '2026-06-22T10:00',
        'all_day' => false,
    ])->assertCreated()
        ->assertJsonPath('data.title', 'Reunião local')
        ->assertJsonPath('data.source', 'local');

    $eventId = $created->json('data.id');
    $this->actingAs($admin)->putJson(route('calendar.events.update', $eventId), [
        'title' => 'Reunião atualizada',
        'category' => 'projeto',
        'starts_at' => '2026-06-22T09:30',
        'ends_at' => '2026-06-22T11:00',
        'all_day' => false,
    ])->assertOk()->assertJsonPath('data.title', 'Reunião atualizada');

    $this->actingAs($admin)
        ->deleteJson(route('calendar.events.destroy', $eventId))
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelado');

    expect(CalendarEvent::query()->findOrFail($eventId)->sync_status)->toBe('local_only');
});

test('calendar form works without javascript and redirects with confirmation', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    config()->set('portfolio.admin_email', $admin->email);
    config()->set('services.google_calendar.write_enabled', false);

    $this->actingAs($admin)->post(route('calendar.events.store'), [
        'title' => 'Cadastro sem JavaScript',
        'category' => 'tarefa',
        'starts_at' => '2026-06-23T14:00',
        'ends_at' => '2026-06-23T15:00',
        'all_day' => false,
    ])->assertRedirect('/#agenda')
        ->assertSessionHas('calendar_status', 'Compromisso salvo na agenda.');

    $this->assertDatabaseHas('calendar_events', [
        'user_id' => $admin->id,
        'public_title' => 'Cadastro sem JavaScript',
        'source' => 'local',
    ]);
});

test('connected calendar receives form events automatically', function () {
    Queue::fake();
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    config()->set('portfolio.admin_email', $admin->email);
    config()->set('services.google_calendar.enabled', true);
    config()->set('services.google_calendar.client_id', 'calendar-client');
    config()->set('services.google_calendar.client_secret', 'calendar-secret');
    config()->set('services.google_calendar.redirect_uri', 'http://localhost/admin/calendar/callback');
    config()->set('services.google_calendar.write_enabled', true);
    GoogleCalendarConnection::query()->create([
        'user_id' => $admin->id,
        'refresh_token' => 'refresh-token',
        'calendar_ids' => ['primary'],
        'status' => 'connected',
    ]);
    Http::fake(function ($request) {
        if (str_contains($request->url(), 'oauth2.googleapis.com/token')) {
            return Http::response(['access_token' => 'access-token']);
        }

        return Http::response(['id' => 'google-event-123']);
    });

    $response = $this->actingAs($admin)->postJson(route('calendar.events.store'), [
        'title' => 'Enviado automaticamente',
        'category' => 'reuniao',
        'starts_at' => '2026-06-23T09:00',
        'ends_at' => '2026-06-23T10:00',
        'all_day' => false,
    ])->assertCreated()
        ->assertJsonPath('data.sync_status', 'pending');

    $event = CalendarEvent::query()->findOrFail($response->json('data.id'));
    Queue::assertPushed(SyncCalendarEventToGoogle::class, function (SyncCalendarEventToGoogle $job) use ($event): bool {
        expect($job->eventId)->toBe($event->id);
        $job->handle(app(CalendarEventManager::class));

        return true;
    });

    $event->refresh();
    expect($event->sync_status)->toBe('synced')
        ->and($event->provider_event_id)->toBe('google-event-123')
        ->and($event->connection_id)->not->toBeNull();
    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && str_contains($request->url(), '/calendars/primary/events'));
});

test('calendar form is visible only to the authenticated portfolio administrator', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $other = User::factory()->create();
    config()->set('portfolio.admin_email', $admin->email);
    config()->set('portfolio.integrations.calendar', true);

    $this->get('/')->assertOk()->assertDontSee('data-calendar-manager', false);
    $this->actingAs($other)->get('/')->assertOk()->assertDontSee('data-calendar-manager', false);
    $this->actingAs($admin)->get('/')->assertOk()->assertSee('data-calendar-manager', false);
});

test('calendar dashboard keeps same-day appointments as separate gantt rows', function () {
    CarbonImmutable::setTestNow('2026-06-21 08:00:00 America/Fortaleza');
    $user = User::factory()->create();

    foreach ([['09:00', '10:00'], ['09:30', '11:00']] as $index => [$start, $end]) {
        CalendarEvent::query()->create([
            'user_id' => $user->id,
            'provider_event_key' => hash('sha256', "local-{$index}"),
            'public_title' => "Compromisso {$index}",
            'category' => 'reuniao',
            'starts_at' => CarbonImmutable::parse("2026-06-22 {$start}", 'America/Fortaleza')->utc(),
            'ends_at' => CarbonImmutable::parse("2026-06-22 {$end}", 'America/Fortaleza')->utc(),
        ]);
    }

    CalendarEvent::query()->create([
        'user_id' => $user->id,
        'provider_event_key' => hash('sha256', 'local-all-day'),
        'public_title' => 'Evento do dia inteiro',
        'category' => 'projeto',
        'starts_at' => CarbonImmutable::parse('2026-06-22 16:00', 'America/Fortaleza')->utc(),
        'ends_at' => CarbonImmutable::parse('2026-06-22 17:00', 'America/Fortaleza')->utc(),
        'all_day' => true,
    ]);

    $dashboard = app(CalendarDashboard::class)->forHome();
    $day = collect($dashboard['days'])->firstWhere('date', '2026-06-22');
    $allDay = collect($day['events'])->firstWhere('all_day', true);

    expect($day['events'])->toHaveCount(3)
        ->and($day['events'][0]['id'])->not->toBe($day['events'][1]['id'])
        ->and($allDay['time'])->toBe('Dia inteiro')
        ->and($allDay['offset'])->toBe(0.0)
        ->and($allDay['width'])->toBe(100.0);
});

test('single timed appointment uses its proportional gantt position and duration', function () {
    CarbonImmutable::setTestNow('2026-06-21 08:00:00 America/Fortaleza');
    config()->set('portfolio.integrations.calendar', true);
    $user = User::factory()->create();

    CalendarEvent::query()->create([
        'user_id' => $user->id,
        'provider_event_key' => hash('sha256', 'single-timed-event'),
        'public_title' => 'Compromisso com horário',
        'category' => 'reuniao',
        'starts_at' => CarbonImmutable::parse('2026-06-24 04:05', 'America/Fortaleza')->utc(),
        'ends_at' => CarbonImmutable::parse('2026-06-24 08:06', 'America/Fortaleza')->utc(),
        'all_day' => false,
    ]);

    $dashboard = app(CalendarDashboard::class)->forHome();
    $event = collect($dashboard['days'])->firstWhere('date', '2026-06-24')['events'][0];

    expect($event['all_day'])->toBeFalse()
        ->and($event['time'])->toBe('04:05–08:06')
        ->and($event['detail_time'])->toBe('04:05–08:06')
        ->and($event['duration'])->toBe('4 horas e 1 min')
        ->and($event['offset'])->toBe(17.014)
        ->and($event['width'])->toBe(16.736);

    $this->get('/')
        ->assertOk()
        ->assertSee('1 compromisso neste dia')
        ->assertSee('data-calendar-event-dialog', false)
        ->assertSee('data-calendar-event-open', false)
        ->assertSee('data-event-duration="4 horas e 1 min"', false)
        ->assertSee('aria-haspopup="dialog"', false)
        ->assertSee('--event-start: 17.014%; --event-width: 16.736%', false)
        ->assertDontSee('calendar-single-event', false);

    CarbonImmutable::setTestNow();
});

test('all-day local events are normalized to full-day boundaries', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    config()->set('portfolio.admin_email', $admin->email);
    config()->set('services.google_calendar.write_enabled', false);

    $response = $this->actingAs($admin)->postJson(route('calendar.events.store'), [
        'title' => 'Evento integral',
        'category' => 'projeto',
        'starts_at' => '2026-06-22T09:00',
        'ends_at' => '2026-06-22T18:00',
        'all_day' => true,
    ])->assertCreated();

    $event = CalendarEvent::query()->findOrFail($response->json('data.id'));

    expect(CarbonImmutable::instance($event->starts_at)->setTimezone('America/Fortaleza')->format('Y-m-d H:i'))->toBe('2026-06-22 00:00')
        ->and(CarbonImmutable::instance($event->ends_at)->setTimezone('America/Fortaleza')->format('Y-m-d H:i'))->toBe('2026-06-23 00:00')
        ->and($event->all_day)->toBeTrue();
});
