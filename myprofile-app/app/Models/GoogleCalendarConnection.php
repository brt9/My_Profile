<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class GoogleCalendarConnection extends Model
{
    protected $fillable = [
        'user_id', 'refresh_token', 'scopes', 'calendar_ids', 'status',
        'last_synced_at', 'last_error_code',
    ];

    protected $hidden = ['refresh_token'];

    protected function casts(): array
    {
        return [
            'refresh_token' => 'encrypted',
            'scopes' => 'array',
            'calendar_ids' => 'array',
            'last_synced_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'connection_id');
    }
}
