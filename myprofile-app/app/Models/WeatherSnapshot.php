<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class WeatherSnapshot extends Model
{
    protected $fillable = [
        'location_key', 'label', 'latitude', 'longitude', 'temperature',
        'feels_like', 'humidity', 'wind_kmh', 'weather_code', 'condition',
        'emoji', 'observed_at', 'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'temperature' => 'float',
            'feels_like' => 'float',
            'observed_at' => 'immutable_datetime',
            'captured_at' => 'immutable_datetime',
        ];
    }
}
