<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $username
 * @property string $language
 * @property string $language_name
 * @property int $course_xp
 * @property int $total_xp
 * @property int $streak
 * @property CarbonImmutable $snapshot_date
 * @property CarbonImmutable $collected_at
 */
final class DuolingoSnapshot extends Model
{
    protected $fillable = [
        'username', 'language', 'language_name', 'course_xp', 'total_xp',
        'streak', 'snapshot_date', 'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'course_xp' => 'integer',
            'total_xp' => 'integer',
            'streak' => 'integer',
            'snapshot_date' => 'immutable_date',
            'collected_at' => 'immutable_datetime',
        ];
    }
}
