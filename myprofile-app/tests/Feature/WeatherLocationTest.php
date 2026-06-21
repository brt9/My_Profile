<?php

use App\Models\WeatherSnapshot;
use App\Services\Weather\WeatherClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('visitor can explicitly request weather for browser coordinates', function () {
    Cache::flush();
    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            'current' => [
                'temperature_2m' => 29.4,
                'apparent_temperature' => 31.1,
                'weather_code' => 1,
                'wind_speed_10m' => 12,
                'relative_humidity_2m' => 70,
                'time' => '2026-06-20T12:00',
            ],
        ]),
    ]);

    $this->postJson('/api/weather/location', [
        'latitude' => -5.79,
        'longitude' => -35.20,
    ])->assertOk()
        ->assertJsonPath('status', 'available')
        ->assertJsonPath('data.source', 'browser')
        ->assertJsonPath('data.origin', 'Localização autorizada no navegador');
});

test('visitor weather is loaded automatically from the public ip city', function () {
    Cache::flush();
    Http::fake([
        'ipapi.co/*' => Http::response([
            'latitude' => -23.55,
            'longitude' => -46.63,
            'city' => 'São Paulo',
            'region_code' => 'SP',
        ]),
        'api.open-meteo.com/*' => Http::response([
            'current' => [
                'temperature_2m' => 21.2,
                'apparent_temperature' => 20.4,
                'weather_code' => 2,
                'wind_speed_10m' => 9,
                'relative_humidity_2m' => 74,
                'time' => '2026-06-20T12:00',
            ],
        ]),
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
        ->getJson('/api/weather/visitor')
        ->assertOk()
        ->assertJsonPath('status', 'available')
        ->assertJsonPath('data.label', 'São Paulo, SP')
        ->assertJsonPath('data.source', 'ip')
        ->assertJsonMissingPath('data.latitude')
        ->assertJsonMissingPath('data.longitude');
});

test('home keeps natal above the visitor weather panel', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder([
            'Localização principal',
            'Natal, RN',
            'Localização do visitante',
            'Sua cidade',
        ]);
});

test('weather location rejects invalid coordinates', function () {
    $this->postJson('/api/weather/location', [
        'latitude' => 200,
        'longitude' => -35.20,
    ])->assertUnprocessable();
});

test('weather location returns a safe error envelope when provider fails', function () {
    Cache::flush();
    Http::fake(fn () => throw new ConnectionException('provider down'));

    $this->postJson('/api/weather/location', [
        'latitude' => -5.79,
        'longitude' => -35.20,
    ])->assertServiceUnavailable()
        ->assertJsonPath('status', 'error')
        ->assertJsonMissing(['latitude' => -5.79]);
});

test('private visitor ip uses explicit natal fallback without geolocation lookup', function () {
    Cache::flush();
    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            'current' => ['temperature_2m' => 28, 'weather_code' => 0],
        ]),
    ]);

    $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '127.0.0.1']);
    $weather = (new WeatherClient)->byRequest($request);

    expect($weather['source'])->toBe('fallback')
        ->and($weather['label'])->toBe('Natal, RN');

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'ipapi.co') || str_contains($request->url(), 'ipwho.is'));
});

test('natal weather is persisted and restored when cache is unavailable', function () {
    Cache::flush();
    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            'current' => [
                'temperature_2m' => 28.4,
                'apparent_temperature' => 30.1,
                'weather_code' => 2,
                'wind_speed_10m' => 13,
                'relative_humidity_2m' => 76,
                'time' => '2026-06-21T10:30',
            ],
        ]),
    ]);

    $client = new WeatherClient;
    $fresh = $client->byCoords(-5.795, -35.209, 'Natal, RN');
    Cache::flush();
    $stored = $client->cachedByCoords(-5.795, -35.209);

    expect(WeatherSnapshot::query()->count())->toBe(1)
        ->and($fresh['is_stale'])->toBeFalse()
        ->and($stored['is_stale'])->toBeTrue()
        ->and($stored['temp'])->toBe(28.4)
        ->and($stored['captured_at'])->not->toBeNull();
});
