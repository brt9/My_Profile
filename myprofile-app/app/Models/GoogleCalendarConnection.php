<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $refresh_token
 * @property list<string>|null $scopes
 * @property list<string> $calendar_ids
 * @property string $status
 * @property CarbonImmutable|null $last_synced_at
 * @property-read User $user
 * @property-read Collection<int, CalendarEvent> $events
 */
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<CalendarEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'connection_id');
    }
}
