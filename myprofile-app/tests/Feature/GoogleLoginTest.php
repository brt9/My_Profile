<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config()->set('services.google_login.enabled', true);
    config()->set('services.google_login.client_id', 'google-client');
    config()->set('services.google_login.client_secret', 'google-secret');
    config()->set('services.google_login.redirect_uri', 'http://127.0.0.1:8085/auth/google/callback');
});

test('guest can start google login with identity scopes and state', function () {
    $response = $this->get(route('google.login'))->assertRedirect();
    $location = $response->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    expect($location)->toStartWith('https://accounts.google.com/o/oauth2/v2/auth?')
        ->and($query['scope'])->toBe('openid email profile')
        ->and($query['redirect_uri'])->toBe('http://127.0.0.1:8085/auth/google/callback')
        ->and(session('google_login_oauth_state'))->toBe($query['state']);
});

test('login screen advertises google when oauth is configured', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->where('googleLoginEnabled', true));
});

test('verified google identity creates and authenticates a user', function () {
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-token']),
        'openidconnect.googleapis.com/v1/userinfo' => Http::response([
            'sub' => 'google-user-123',
            'email' => 'pedro@example.com',
            'email_verified' => true,
            'name' => 'Pedro Felipe',
        ]),
    ]);

    $this->withSession(['google_login_oauth_state' => 'safe-state'])
        ->get(route('google.callback', ['state' => 'safe-state', 'code' => 'oauth-code']))
        ->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'pedro@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->google_id)->toBe('google-user-123')
        ->and($user->email_verified_at)->not->toBeNull();
});

test('google login safely links an existing account by verified email', function () {
    $existing = User::factory()->create(['email' => 'pedro@example.com', 'google_id' => null]);
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-token']),
        'openidconnect.googleapis.com/v1/userinfo' => Http::response([
            'sub' => 'google-user-456',
            'email' => 'pedro@example.com',
            'email_verified' => true,
            'name' => 'Pedro Atualizado',
        ]),
    ]);

    $this->withSession(['google_login_oauth_state' => 'safe-state'])
        ->get(route('google.callback', ['state' => 'safe-state', 'code' => 'oauth-code']))
        ->assertRedirect(route('dashboard'));

    expect(User::query()->count())->toBe(1)
        ->and($existing->fresh()->google_id)->toBe('google-user-456');
});

test('google callback rejects invalid state before exchanging credentials', function () {
    Http::fake();

    $this->withSession(['google_login_oauth_state' => 'expected-state'])
        ->get(route('google.callback', ['state' => 'wrong-state', 'code' => 'oauth-code']))
        ->assertStatus(419);

    Http::assertNothingSent();
});
