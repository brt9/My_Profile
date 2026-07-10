<?php

declare(strict_types=1);

namespace App\Services\Telemetry;

use App\Models\TelemetrySnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class TelemetryIngestor
{
    private const CACHE_KEY = 'telemetry:latest';

    public function __construct(private readonly IntegrationHealthMonitor $health) {}

    /** @param array<string, mixed> $data */
    public function ingest(array $data): bool
    {
        $collectedAt = Carbon::parse((string) $data['collected_at'])->utc();
        $receivedAt = now()->utc();
        $row = [
            'agent_id' => (string) $data['agent_id'],
            'cpu_usage' => $this->number($data['cpu_load'] ?? null),
            'cpu_temperature' => $this->number($data['cpu_temp'] ?? null),
            'gpu_usage' => $this->number($data['gpu_load'] ?? null),
            'gpu_temperature' => $this->number($data['gpu_temp'] ?? null),
            'memory_usage' => $this->number($data['memory_usage'] ?? null),
            'disk_usage' => $this->number($data['disk_usage'] ?? null),
            'pump_rpm' => $this->number($data['pump_rpm'] ?? null),
            'coolant_temperature' => $this->number($data['coolant_temp'] ?? null),
            'uptime_seconds' => isset($data['uptime_seconds']) ? (int) $data['uptime_seconds'] : null,
            'agent_version' => $data['agent_version'] ?? null,
            'collected_at' => $collectedAt,
            'received_at' => $receivedAt,
        ];

        $inserted = TelemetrySnapshot::query()->insertOrIgnore($row) === 1;
        $cached = Cache::get(self::CACHE_KEY);

        if (! is_array($cached) || Carbon::parse($cached['collected_at'])->lte($collectedAt)) {
            Cache::put(
                self::CACHE_KEY,
                $this->snapshotPayload($row),
                (int) config('telemetry.ttl', 180),
            );
        }

        $this->health->success('telemetry');

        return $inserted;
    }

    /** @return array<string, mixed> */
    public function latestPayload(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $snapshot = TelemetrySnapshot::query()->latest('collected_at')->first();
        if (! $snapshot) {
            return [];
        }

        $payload = $this->snapshotPayload($snapshot->getAttributes());
        Cache::put(self::CACHE_KEY, $payload, (int) config('telemetry.ttl', 180));

        return $payload;
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function snapshotPayload(array $attributes): array
    {
        $collectedAt = $attributes['collected_at'] instanceof \DateTimeInterface
            ? Carbon::instance($attributes['collected_at'])
            : Carbon::parse((string) $attributes['collected_at'], 'UTC');

        return [
            'cpu_load' => $this->number($attributes['cpu_usage'] ?? null),
            'cpu_temp' => $this->number($attributes['cpu_temperature'] ?? null),
            'gpu_load' => $this->number($attributes['gpu_usage'] ?? null),
            'gpu_temp' => $this->number($attributes['gpu_temperature'] ?? null),
            'memory_usage' => $this->number($attributes['memory_usage'] ?? null),
            'disk_usage' => $this->number($attributes['disk_usage'] ?? null),
            'pump_rpm' => $this->number($attributes['pump_rpm'] ?? null),
            'coolant_temp' => $this->number($attributes['coolant_temperature'] ?? null),
            'uptime_seconds' => isset($attributes['uptime_seconds']) ? (int) $attributes['uptime_seconds'] : null,
            'agent_version' => $attributes['agent_version'] ?? null,
            'agent_id' => $attributes['agent_id'],
            'collected_at' => $collectedAt->utc()->toIso8601String(),
        ];
    }

    private function number(mixed $value): ?float
    {
        return $value === null ? null : round((float) $value, 1);
    }
}
