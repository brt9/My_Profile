<?php

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Calendar\CalendarDashboard;
use Carbon\CarbonImmutable;

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
