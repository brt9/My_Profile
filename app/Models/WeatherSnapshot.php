<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $label
 * @property float|null $temperature
 * @property float|null $feels_like
 * @property int|null $humidity
 * @property int|null $wind_kmh
 * @property int|null $weather_code
 * @property string|null $condition
 * @property string|null $emoji
 * @property CarbonImmutable|null $observed_at
 * @property CarbonImmutable $captured_at
 */
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
