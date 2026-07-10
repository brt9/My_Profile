<?php

use App\Models\GoogleCalendarConnection;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('administrator dashboard exposes calendar status and route workspace', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    config()->set('portfolio.admin_email', $admin->email);
    config()->set('services.google_calendar.client_id', 'calendar-client');
    config()->set('services.google_calendar.client_secret', 'calendar-secret');
    config()->set('services.google_calendar.write_enabled', true);
    GoogleCalendarConnection::query()->create([
        'user_id' => $admin->id,
        'refresh_token' => 'refresh-token',
        'calendar_ids' => ['primary'],
        'status' => 'connected',
    ]);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('canManageCalendar', true)
            ->where('googleCalendarConfigured', true)
            ->where('googleCalendarConnected', true)
            ->where('googleCalendarWriteEnabled', true));
});

test('linkedin csv administration route no longer exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/linkedin')->assertNotFound();
});
