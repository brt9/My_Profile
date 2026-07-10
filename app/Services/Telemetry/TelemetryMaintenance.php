<?php

declare(strict_types=1);

namespace App\Services\Telemetry;

use App\Models\TelemetryHourlyAggregate;
use App\Models\TelemetrySnapshot;
use Illuminate\Support\Carbon;

final class TelemetryMaintenance
{
    /** @return array{aggregates:int,raw_deleted:int,aggregates_deleted:int} */
    public function run(): array
    {
        $metrics = (array) config('telemetry.metrics');
        $rawCutoff = now()->utc()->subDays((int) config('telemetry.raw_retention_days', 7));
        $completedHour = now()->utc()->startOfHour();
        $groups = [];

        TelemetrySnapshot::query()
            ->where('collected_at', '>=', $rawCutoff)
            ->where('collected_at', '<', $completedHour)
            ->orderBy('id')
            ->chunkById(1000, function ($snapshots) use (&$groups, $metrics): void {
                foreach ($snapshots as $snapshot) {
                    $bucket = Carbon::parse($snapshot->collected_at)->utc()->startOfHour();
                    foreach ($metrics as $key => $definition) {
                        $value = $snapshot->{$definition['column']};
                        if ($value === null) {
                            continue;
                        }

                        $groupKey = "{$snapshot->agent_id}|{$key}|{$bucket->timestamp}";
                        $groups[$groupKey] ??= [
                            'agent_id' => $snapshot->agent_id,
                            'metric' => $key,
                            'bucket_at' => $bucket,
                            'minimum' => (float) $value,
                            'maximum' => (float) $value,
                            'sum' => 0.0,
                            'sample_count' => 0,
                        ];
                        $groups[$groupKey]['minimum'] = min($groups[$groupKey]['minimum'], (float) $value);
                        $groups[$groupKey]['maximum'] = max($groups[$groupKey]['maximum'], (float) $value);
                        $groups[$groupKey]['sum'] += (float) $value;
                        $groups[$groupKey]['sample_count']++;
                    }
                }
            });

        $now = now()->utc();
        $rows = collect($groups)->map(function (array $group) use ($now): array {
            $group['average'] = round($group['sum'] / $group['sample_count'], 2);
            unset($group['sum']);

            return [...$group, 'created_at' => $now, 'updated_at' => $now];
        })->values();

        $rows->chunk(500)->each(function ($chunk): void {
            TelemetryHourlyAggregate::query()->upsert(
                $chunk->all(),
                ['agent_id', 'metric', 'bucket_at'],
                ['minimum', 'maximum', 'average', 'sample_count', 'updated_at'],
            );
        });

        $rawDeleted = TelemetrySnapshot::query()->where('collected_at', '<', $rawCutoff)->delete();
        $aggregateDeleted = TelemetryHourlyAggregate::query()
            ->where('bucket_at', '<', now()->utc()->subDays((int) config('telemetry.aggregate_retention_days', 90)))
            ->delete();

        return [
            'aggregates' => $rows->count(),
            'raw_deleted' => $rawDeleted,
            'aggregates_deleted' => $aggregateDeleted,
        ];
    }
}
