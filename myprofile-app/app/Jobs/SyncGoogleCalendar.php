<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\GoogleCalendarConnection;
use App\Services\Calendar\CalendarSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncGoogleCalendar implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(public readonly int $connectionId) {}

    public function handle(CalendarSyncService $sync): void
    {
        $connection = GoogleCalendarConnection::query()->find($this->connectionId);
        if ($connection !== null && config('services.google_calendar.enabled')) {
            $sync->sync($connection);
        }
    }
}
