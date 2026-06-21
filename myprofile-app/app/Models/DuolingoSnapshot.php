<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
