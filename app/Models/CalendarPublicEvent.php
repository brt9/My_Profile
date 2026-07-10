<?php

declare(strict_types=1);

namespace App\Models;

/** @deprecated Use CalendarEvent. */
final class CalendarPublicEvent extends CalendarEvent
{
    protected $table = 'calendar_events';
}
