<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CalendarEvent;
use App\Services\Calendar\CalendarEventManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncCalendarEventToGoogle implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 120;

    public function __construct(public readonly int $eventId) {}

    public function uniqueId(): string
    {
        return (string) $this->eventId;
    }

    public function handle(CalendarEventManager $manager): void
    {
        $event = CalendarEvent::query()->find($this->eventId);
        if ($event === null || $event->status === 'cancelado') {
            return;
        }

        $manager->syncExistingToGoogle($event);
    }
}
