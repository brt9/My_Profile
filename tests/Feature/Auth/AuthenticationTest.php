<?php

use App\Models\GoogleCalendarConnection;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('administrator login automatically starts google calendar connection when needed', function () {
    config()->set('app.debug', true);
    config()->set('portfolio.admin_email', 'admin@example.com');
    config()->set('services.google_calendar.enabled', true);
    config()->set('services.google_calendar.client_id', 'calendar-client');
    config()->set('services.google_calendar.client_secret', 'calendar-secret');
    config()->set('services.google_calendar.redirect_uri', 'http://127.0.0.1:8085/admin/calendar/callback');
    config()->set('services.google_calendar.write_enabled', true);
    $user = User::factory()->unverified()->create(['email' => 'admin@example.com']);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();
    parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($response->headers->get('Location'))->toStartWith('https://accounts.google.com/o/oauth2/v2/auth?')
        ->and($query['login_hint'])->toBe('admin@example.com')
        ->and(session('google_calendar_oauth_state'))->toBe($query['state']);
    $this->assertAuthenticatedAs($user);
});

test('administrator with connected calendar logs in without another google consent', function () {
    config()->set('app.debug', true);
    config()->set('portfolio.admin_email', 'admin@example.com');
    config()->set('services.google_calendar.enabled', true);
    config()->set('services.google_calendar.client_id', 'calendar-client');
    config()->set('services.google_calendar.client_secret', 'calendar-secret');
    config()->set('services.google_calendar.redirect_uri', 'http://127.0.0.1:8085/admin/calendar/callback');
    $user = User::factory()->create(['email' => 'admin@example.com']);
    GoogleCalendarConnection::query()->create([
        'user_id' => $user->id,
        'refresh_token' => 'refresh-token',
        'calendar_ids' => ['primary'],
        'status' => 'connected',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));
});
