<?php

declare(strict_types=1);

namespace App\Services\Telemetry;

use App\Models\IntegrationHealth;
use Illuminate\Support\Facades\Schema;

final class IntegrationHealthMonitor
{
    public function success(string $integration, ?float $startedAt = null): void
    {
        $this->record($integration, 'available', $startedAt, true);
    }

    public function failure(string $integration, ?float $startedAt = null): void
    {
        $this->record($integration, 'unavailable', $startedAt, false);
    }

    private function record(string $integration, string $status, ?float $startedAt, bool $success): void
    {
        if (! Schema::hasTable('integration_health')) {
            return;
        }

        $now = now()->utc();
        $values = [
            'status' => $status,
            'latency_ms' => $startedAt === null ? null : max(0, (int) round((microtime(true) - $startedAt) * 1000)),
        ];
        $values[$success ? 'last_success_at' : 'last_failure_at'] = $now;

        IntegrationHealth::query()->updateOrCreate(
            ['integration' => $integration],
            $values,
        );
    }
}
