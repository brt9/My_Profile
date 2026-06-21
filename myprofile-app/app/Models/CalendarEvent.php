<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    protected $fillable = [
        'user_id', 'connection_id', 'provider_event_key', 'provider_event_id',
        'provider_calendar_id', 'public_title', 'category', 'status', 'starts_at',
        'ends_at', 'all_day', 'source', 'sync_status', 'synced_at', 'last_sync_error',
    ];

    protected $hidden = ['provider_event_id'];

    protected function casts(): array
    {
        return [
            'provider_event_id' => 'encrypted',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'all_day' => 'boolean',
            'synced_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(GoogleCalendarConnection::class, 'connection_id');
    }
}
