<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TelemetrySnapshot extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cpu_usage' => 'float',
            'cpu_temperature' => 'float',
            'gpu_usage' => 'float',
            'gpu_temperature' => 'float',
            'memory_usage' => 'float',
            'disk_usage' => 'float',
            'pump_rpm' => 'float',
            'coolant_temperature' => 'float',
            'uptime_seconds' => 'integer',
            'collected_at' => 'immutable_datetime',
            'received_at' => 'immutable_datetime',
        ];
    }
}
