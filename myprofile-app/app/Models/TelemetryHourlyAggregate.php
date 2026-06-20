<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TelemetryHourlyAggregate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'minimum' => 'float',
            'maximum' => 'float',
            'average' => 'float',
            'sample_count' => 'integer',
            'bucket_at' => 'immutable_datetime',
        ];
    }
}
