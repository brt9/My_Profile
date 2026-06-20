<?php

declare(strict_types=1);

use App\Models\IntegrationHealth;
use App\Models\TelemetryHourlyAggregate;
use App\Models\TelemetrySnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    config()->set('telemetry.token', 'test-token');
    config()->set('telemetry.max_payload_bytes', 16384);
    Cache::forget('telemetry:latest');
    Carbon::setTestNow('2026-06-20T15:00:00Z');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function telemetryPayload(array $overrides = []): array
{
    return array_replace([
        'agent_id' => 'test-agent-01',
        'collected_at' => now()->utc()->toIso8601String(),
        'cpu_temp' => 51.26,
        'gpu_temp' => 48.5,
        'cpu_load' => 0,
        'gpu_load' => 83.84,
        'memory_usage' => 42.2,
        'disk_usage' => 71.7,
        'pump_rpm' => null,
        'coolant_temp' => null,
        'uptime_seconds' => 7200,
        'agent_version' => '1.1.0',
    ], $overrides);
}

function insertTelemetrySnapshot(string $at, array $overrides = []): void
{
    TelemetrySnapshot::query()->create(array_replace([
        'agent_id' => 'test-agent-01',
        'cpu_usage' => 20,
        'cpu_temperature' => 50,
        'gpu_usage' => 30,
        'gpu_temperature' => 48,
        'memory_usage' => 40,
        'disk_usage' => 70,
        'pump_rpm' => null,
        'coolant_temperature' => null,
        'uptime_seconds' => 3600,
        'agent_version' => '1.1.0',
        'collected_at' => Carbon::parse($at)->utc(),
        'received_at' => Carbon::parse($at)->utc()->addSecond(),
    ], $overrides));
}

test('telemetry push requires the configured bearer token', function (): void {
    $this->postJson('/api/telemetry/push', telemetryPayload())
        ->assertUnauthorized();
});

test('telemetry push validates payload size ranges and collection time', function (): void {
    config()->set('telemetry.max_payload_bytes', 100);
    $this->withToken('test-token')->postJson('/api/telemetry/push', telemetryPayload())
        ->assertStatus(413);

    config()->set('telemetry.max_payload_bytes', 16384);
    $this->withToken('test-token')->postJson('/api/telemetry/push', telemetryPayload([
        'memory_usage' => 101,
        'collected_at' => now()->subDays(3)->toIso8601String(),
    ]))->assertUnprocessable()->assertJsonValidationErrors(['memory_usage']);

    $this->withToken('test-token')->postJson('/api/telemetry/push', telemetryPayload([
        'collected_at' => now()->subDays(3)->toIso8601String(),
    ]))->assertUnprocessable()->assertJsonValidationErrors(['collected_at']);
});

test('telemetry push persists normalized metrics and updates latest envelope', function (): void {
    $this->withToken('test-token')->postJson('/api/telemetry/push', telemetryPayload())
        ->assertOk()
        ->assertJson(['ok' => true, 'duplicate' => false]);

    $this->assertDatabaseHas('telemetry_snapshots', [
        'agent_id' => 'test-agent-01',
        'cpu_usage' => 0,
        'memory_usage' => 42.2,
        'disk_usage' => 71.7,
        'uptime_seconds' => 7200,
    ]);

    $this->getJson('/api/telemetry/latest')
        ->assertOk()
        ->assertJson([
            'status' => 'available',
            'data' => [
                'cpu_temp' => 51.3,
                'cpu_load' => 0,
                'gpu_load' => 83.8,
                'memory_usage' => 42.2,
                'disk_usage' => 71.7,
                'uptime_seconds' => 7200,
                'coolant_temp' => null,
            ],
            'meta' => ['stale' => false, 'machine_status' => 'online'],
        ]);
});

test('telemetry ingestion is idempotent by agent and collection instant', function (): void {
    $payload = telemetryPayload();

    $this->withToken('test-token')->postJson('/api/telemetry/push', $payload)
        ->assertJsonPath('duplicate', false);
    $this->withToken('test-token')->postJson('/api/telemetry/push', $payload)
        ->assertJsonPath('duplicate', true);

    expect(TelemetrySnapshot::query()->count())->toBe(1);
});

test('telemetry latest returns unavailable when no snapshot exists', function (): void {
    $this->getJson('/api/telemetry/latest')
        ->assertOk()
        ->assertJson([
            'status' => 'unavailable',
            'data' => null,
            'meta' => ['stale' => true, 'machine_status' => 'offline'],
            'error' => null,
        ]);
});

test('telemetry latest derives stale and offline states from sample age', function (): void {
    insertTelemetrySnapshot('2026-06-20T14:55:00Z', ['gpu_temperature' => 49]);

    $this->getJson('/api/telemetry/latest')
        ->assertOk()
        ->assertJsonPath('status', 'stale')
        ->assertJsonPath('data.gpu_temp', 49)
        ->assertJsonPath('meta.stale', true)
        ->assertJsonPath('meta.machine_status', 'offline');
});

test('history aggregates windows without interpolating gaps', function (): void {
    insertTelemetrySnapshot('2026-06-20T14:05:10Z', ['cpu_usage' => 10]);
    insertTelemetrySnapshot('2026-06-20T14:05:50Z', ['cpu_usage' => 30]);
    insertTelemetrySnapshot('2026-06-20T14:45:00Z', ['cpu_usage' => 50]);
    insertTelemetrySnapshot('2026-06-19T14:00:00Z', ['cpu_usage' => 99]);

    $response = $this->getJson('/api/telemetry/history?metric=cpu_load&range=1h&resolution=5m')
        ->assertOk()
        ->assertJsonPath('status', 'available')
        ->assertJsonPath('meta.unit', '%')
        ->assertJsonPath('meta.timezone', 'UTC')
        ->assertJsonPath('meta.gaps_interpolated', false)
        ->assertJsonPath('data.summary.minimum', 10)
        ->assertJsonPath('data.summary.maximum', 50)
        ->assertJsonPath('data.summary.average', 30);

    $values = collect($response->json('data.points'))->pluck('value')->filter(fn ($value) => $value !== null)->values();
    expect($response->json('data.points'))->toHaveCount(13)
        ->and($values)->toHaveCount(2)
        ->and($values->first())->toEqual(20);
});

test('history rejects unsupported metrics and excessive combinations', function (): void {
    $this->getJson('/api/telemetry/history?metric=hostname&range=24h')
        ->assertUnprocessable();
});

test('history returns a bounded result with one database query', function (): void {
    $rows = [];
    $base = now()->utc();
    for ($index = 0; $index < 720; $index++) {
        $at = $base->copy()->subSeconds(($index + 1) * 5);
        $rows[] = [
            'agent_id' => 'load-test',
            'cpu_usage' => $index % 100,
            'collected_at' => $at,
            'received_at' => $at,
        ];
    }
    TelemetrySnapshot::query()->insert($rows);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $response = $this->getJson('/api/telemetry/history?metric=cpu_load&range=1h&resolution=1m')->assertOk();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($response->json('data.points'))->toHaveCount(61)
        ->and(count($queries))->toBeLessThanOrEqual(2);
});

test('maintenance aggregates completed hours and enforces retention', function (): void {
    config()->set('telemetry.raw_retention_days', 7);
    config()->set('telemetry.aggregate_retention_days', 90);

    insertTelemetrySnapshot('2026-06-20T13:10:00Z', ['cpu_usage' => 20]);
    insertTelemetrySnapshot('2026-06-20T13:40:00Z', ['cpu_usage' => 40]);
    insertTelemetrySnapshot('2026-06-10T13:00:00Z');
    TelemetryHourlyAggregate::query()->create([
        'agent_id' => 'old-agent',
        'metric' => 'cpu_load',
        'bucket_at' => now()->subDays(91),
        'minimum' => 1,
        'maximum' => 1,
        'average' => 1,
        'sample_count' => 1,
    ]);

    $this->artisan('telemetry:maintain')->assertSuccessful();

    $this->assertDatabaseHas('telemetry_hourly_aggregates', [
        'agent_id' => 'test-agent-01',
        'metric' => 'cpu_load',
        'minimum' => 20,
        'maximum' => 40,
        'average' => 30,
        'sample_count' => 2,
    ]);
    expect(TelemetrySnapshot::query()->count())->toBe(2)
        ->and(TelemetryHourlyAggregate::query()->where('agent_id', 'old-agent')->exists())->toBeFalse();
});

test('integration health endpoint exposes sanitized operational state', function (): void {
    IntegrationHealth::query()->create([
        'integration' => 'telemetry',
        'status' => 'available',
        'last_success_at' => now(),
        'latency_ms' => 12,
    ]);

    $this->getJson('/api/health/integrations')
        ->assertOk()
        ->assertJsonPath('data.0.integration', 'telemetry')
        ->assertJsonPath('data.0.status', 'available')
        ->assertJsonMissing(['message', 'token', 'agent_id']);
});
