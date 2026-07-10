<?php

declare(strict_types=1);

namespace App\Services\Telemetry;

use App\Models\TelemetrySnapshot;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class TelemetryHistory
{
    /** @return array<string, mixed> */
    public function get(string $metric, string $range, ?string $resolution = null): array
    {
        $metrics = (array) config('telemetry.metrics');
        $ranges = (array) config('telemetry.ranges');
        $resolutions = (array) config('telemetry.resolutions');

        if (! isset($metrics[$metric], $ranges[$range])) {
            throw new InvalidArgumentException('Métrica ou intervalo inválido.');
        }

        $resolution ??= $this->defaultResolution($range);
        if (! isset($resolutions[$resolution])) {
            throw new InvalidArgumentException('Resolução inválida.');
        }

        $rangeSeconds = (int) $ranges[$range];
        $resolutionSeconds = (int) $resolutions[$resolution];
        if ((int) ceil($rangeSeconds / $resolutionSeconds) > 1500) {
            throw new InvalidArgumentException('A resolução produz pontos demais.');
        }

        $definition = $metrics[$metric];
        $column = (string) $definition['column'];
        $from = now()->utc()->subSeconds($rangeSeconds);
        $rows = TelemetrySnapshot::query()
            ->select(['collected_at', $column])
            ->where('collected_at', '>=', $from)
            ->whereNotNull($column)
            ->orderBy('collected_at')
            ->limit(25000)
            ->get();

        $buckets = [];
        $allValues = [];
        foreach ($rows as $row) {
            $timestamp = Carbon::parse($row->collected_at)->utc()->timestamp;
            $bucket = intdiv($timestamp, $resolutionSeconds) * $resolutionSeconds;
            $value = (float) $row->{$column};
            $allValues[] = $value;
            $buckets[$bucket] ??= ['sum' => 0.0, 'count' => 0];
            $buckets[$bucket]['sum'] += $value;
            $buckets[$bucket]['count']++;
        }

        $precision = (int) $definition['precision'];
        $points = [];
        $firstBucket = intdiv($from->timestamp, $resolutionSeconds) * $resolutionSeconds;
        $lastBucket = intdiv(now()->utc()->timestamp, $resolutionSeconds) * $resolutionSeconds;
        for ($timestamp = $firstBucket; $timestamp <= $lastBucket; $timestamp += $resolutionSeconds) {
            $bucket = $buckets[$timestamp] ?? null;
            $points[] = [
                'at' => Carbon::createFromTimestampUTC($timestamp)->toIso8601String(),
                'value' => $bucket === null ? null : round($bucket['sum'] / $bucket['count'], $precision),
                'samples' => $bucket['count'] ?? 0,
            ];
        }

        return [
            'status' => $allValues === [] ? 'unavailable' : 'available',
            'data' => [
                'points' => $points,
                'summary' => $allValues === [] ? null : [
                    'minimum' => round(min($allValues), $precision),
                    'maximum' => round(max($allValues), $precision),
                    'average' => round(array_sum($allValues) / count($allValues), $precision),
                    'samples' => count($allValues),
                ],
            ],
            'meta' => [
                'metric' => $metric,
                'label' => $definition['label'],
                'unit' => $definition['unit'],
                'range' => $range,
                'resolution' => $resolution,
                'from' => $from->toIso8601String(),
                'to' => now()->utc()->toIso8601String(),
                'timezone' => 'UTC',
                'gaps_interpolated' => false,
            ],
            'error' => null,
        ];
    }

    private function defaultResolution(string $range): string
    {
        return match ($range) {
            '1h' => '1m',
            '6h', '12h' => '5m',
            default => '15m',
        };
    }
}
