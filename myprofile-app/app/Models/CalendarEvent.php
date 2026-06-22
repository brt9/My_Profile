<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $connection_id
 * @property string|null $provider_event_id
 * @property string|null $provider_calendar_id
 * @property string $public_title
 * @property string $category
 * @property string $status
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property bool $all_day
 * @property string $source
 * @property string $sync_status
 * @property CarbonImmutable|null $synced_at
 * @property-read User|null $user
 * @property-read GoogleCalendarConnection|null $connection
 */
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<GoogleCalendarConnection, $this> */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(GoogleCalendarConnection::class, 'connection_id');
    }
}
