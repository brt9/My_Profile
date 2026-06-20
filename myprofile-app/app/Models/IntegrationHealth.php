<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
