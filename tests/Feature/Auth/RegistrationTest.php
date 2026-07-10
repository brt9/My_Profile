<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    config()->set('app.debug', true);
    Notification::fake();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    Notification::assertNothingSent();
});

test('production registration sends verification and protects the dashboard', function () {
    config()->set('app.debug', false);
    config()->set('services.google_calendar.enabled', false);
    Notification::fake();

    $this->post('/register', [
        'name' => 'Production User',
        'email' => 'production@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'production@example.com')->firstOrFail();
    Notification::assertSentTo($user, VerifyEmail::class);
    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
});

test('debug mode allows unverified users into the dashboard', function () {
    config()->set('app.debug', true);
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});
