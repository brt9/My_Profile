<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $integration
 * @property string $status
 * @property CarbonImmutable|null $last_success_at
 * @property CarbonImmutable|null $last_failure_at
 * @property int|null $latency_ms
 */
final class IntegrationHealth extends Model
{
    protected $table = 'integration_health';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_success_at' => 'immutable_datetime',
            'last_failure_at' => 'immutable_datetime',
            'latency_ms' => 'integer',
        ];
    }
}
