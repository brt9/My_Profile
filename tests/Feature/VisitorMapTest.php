<?php

use App\Models\VisitorMapEntry;

test('home presents privacy choices and the anonymous visitor map', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('data-cookie-consent', false)
        ->assertSee('Preferências de privacidade')
        ->assertSee('Escolha como deseja navegar.')
        ->assertSee('Aceitar apenas os necessários')
        ->assertSee('Aceitar todos os cookies')
        ->assertSee('data-visitor-map', false)
        ->assertSee('De onde este portfólio é acessado.');
});

test('visitor map starts with a safe empty aggregate', function () {
    $this->getJson('/api/visitors/map')
        ->assertOk()
        ->assertJsonPath('status', 'available')
        ->assertJsonPath('data.total_visitors', 0)
        ->assertJsonPath('data.regions', 0)
        ->assertJsonPath('meta.stores_exact_coordinates', false);
});

test('location is recorded only after consent and rounded before persistence', function () {
    $this->postJson('/api/visitors/map', [
        'latitude' => -5.79448,
        'longitude' => -35.211,
    ])->assertForbidden();

    $response = $this
        ->withCredentials()
        ->withUnencryptedCookie('portfolio_cookie_consent', 'location-v1')
        ->postJson('/api/visitors/map', [
            'latitude' => -5.79448,
            'longitude' => -35.211,
        ]);

    $response
        ->assertCreated()
        ->assertCookie('visitor_map_id')
        ->assertJsonPath('status', 'recorded')
        ->assertJsonPath('data.latitude', -5.8)
        ->assertJsonPath('data.longitude', -35.2)
        ->assertJsonMissingPath('data.visitor_key');

    $entry = VisitorMapEntry::query()->sole();

    expect($entry->latitude)->toBe(-5.8)
        ->and($entry->longitude)->toBe(-35.2)
        ->and($entry->visitor_key)->toHaveLength(64);
});

test('public map groups visitors by approximate region', function () {
    VisitorMapEntry::query()->create([
        'visitor_key' => str_repeat('a', 64),
        'latitude' => -5.8,
        'longitude' => -35.2,
    ]);
    VisitorMapEntry::query()->create([
        'visitor_key' => str_repeat('b', 64),
        'latitude' => -5.8,
        'longitude' => -35.2,
    ]);

    $this->getJson('/api/visitors/map')
        ->assertOk()
        ->assertJsonPath('data.total_visitors', 2)
        ->assertJsonPath('data.regions', 1)
        ->assertJsonPath('data.points.0.visitors', 2)
        ->assertJsonPath('data.points.0.latitude', -5.8)
        ->assertJsonMissingPath('data.points.0.visitor_key');
});

test('same anonymous browser updates one map entry instead of inflating the count', function () {
    $token = str_repeat('C', 64);
    $cookies = [
        'portfolio_cookie_consent' => 'location-v1',
        'visitor_map_id' => $token,
    ];

    $this->withCredentials()
        ->withUnencryptedCookies($cookies)
        ->postJson('/api/visitors/map', ['latitude' => -5.8, 'longitude' => -35.2])
        ->assertCreated();

    $this->withCredentials()
        ->withUnencryptedCookies($cookies)
        ->postJson('/api/visitors/map', ['latitude' => -23.6, 'longitude' => -46.6])
        ->assertCreated();

    $entry = VisitorMapEntry::query()->sole();

    expect(VisitorMapEntry::query()->count())->toBe(1)
        ->and($entry->latitude)->toBe(-23.6)
        ->and($entry->longitude)->toBe(-46.6);
});

test('visitor can revoke the anonymous map entry', function () {
    $token = str_repeat('Z', 64);
    VisitorMapEntry::query()->create([
        'visitor_key' => hash_hmac('sha256', $token, (string) config('app.key')),
        'latitude' => -5.8,
        'longitude' => -35.2,
    ]);

    $this->withCredentials()
        ->withUnencryptedCookie('visitor_map_id', $token)
        ->deleteJson('/api/visitors/map')
        ->assertOk()
        ->assertCookieExpired('visitor_map_id')
        ->assertJsonPath('status', 'removed');

    expect(VisitorMapEntry::query()->count())->toBe(0);
});
