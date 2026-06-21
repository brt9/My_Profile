<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use App\Models\GoogleCalendarConnection;
use Carbon\CarbonImmutable;

final class CalendarDashboard
{
    /** @return array<string, mixed> */
    public function forHome(): array
    {
        $timezone = (string) config('portfolio.presentation_timezone', 'America/Fortaleza');
        $now = CarbonImmutable::now($timezone);
        $weekStart = $now->startOfDay();
        $weekEnd = $weekStart->addWeek();
        $connection = GoogleCalendarConnection::query()->latest()->first();
        $events = CalendarEvent::query()
            ->where('status', '!=', 'cancelado')
            ->where('ends_at', '>', $weekStart->utc())
            ->where('starts_at', '<', $weekEnd->utc())
            ->orderBy('starts_at')
            ->get();
        $manageableEvents = CalendarEvent::query()
            ->whereNotNull('user_id')
            ->where('source', 'local')
            ->where('status', '!=', 'cancelado')
            ->where('ends_at', '>', $weekStart->utc())
            ->orderBy('starts_at')
            ->limit(100)
            ->get();

        $days = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $events, $timezone): array {
            $dayStart = $weekStart->addDays($offset);
            $dayEnd = $dayStart->addDay();
            $segments = [];

            foreach ($events as $event) {
                $start = CarbonImmutable::instance($event->starts_at)->setTimezone($timezone);
                $end = CarbonImmutable::instance($event->ends_at)->setTimezone($timezone);
                if ($end->lessThanOrEqualTo($dayStart) || $start->greaterThanOrEqualTo($dayEnd)) {
                    continue;
                }

                $visibleStart = $start->greaterThan($dayStart) ? $start : $dayStart;
                $visibleEnd = $end->lessThan($dayEnd) ? $end : $dayEnd;
                $startMinutes = $dayStart->diffInMinutes($visibleStart);
                $durationMinutes = max(15, $visibleStart->diffInMinutes($visibleEnd));
                $isAllDay = $event->all_day
                    || ($start->isStartOfDay() && $end->isStartOfDay() && $start->diffInHours($end) >= 24);
                $segments[] = [
                    'id' => $event->getKey(),
                    'title' => $event->public_title,
                    'category' => $event->category,
                    'status' => $event->status,
                    'all_day' => $isAllDay,
                    'source' => $event->source,
                    'sync_status' => $event->sync_status,
                    'time' => $isAllDay ? 'Dia inteiro' : $start->format('H:i').'–'.$end->format('H:i'),
                    'offset' => round(($startMinutes / 1440) * 100, 3),
                    'width' => round((min($durationMinutes, 1440 - $startMinutes) / 1440) * 100, 3),
                ];
            }

            return [
                'date' => $dayStart->toDateString(),
                'weekday' => ucfirst($dayStart->locale('pt_BR')->translatedFormat('D')),
                'label' => $dayStart->format('d/m'),
                'is_today' => $dayStart->isToday(),
                'events' => $segments,
            ];
        })->all();

        return [
            'configured' => filled(config('services.google_calendar.client_id'))
                && filled(config('services.google_calendar.client_secret')),
            'connected' => $connection !== null,
            'status' => $connection?->status ?? 'not_connected',
            'last_synced_at' => $connection?->last_synced_at,
            'range_label' => $weekStart->format('d/m').'–'.$weekEnd->subDay()->format('d/m'),
            'days' => $days,
            'event_count' => $events->count(),
            'manageable_events' => $manageableEvents
                ->map(fn (CalendarEvent $event): array => [
                    'id' => $event->getKey(),
                    'title' => $event->public_title,
                    'category' => $event->category,
                    'starts_at' => CarbonImmutable::instance($event->starts_at)->setTimezone($timezone)->format('Y-m-d\\TH:i'),
                    'ends_at' => CarbonImmutable::instance($event->ends_at)->setTimezone($timezone)->format('Y-m-d\\TH:i'),
                    'all_day' => $event->all_day,
                    'source' => $event->source,
                    'sync_status' => $event->sync_status,
                ])->values()->all(),
            'write_enabled' => (bool) config('services.google_calendar.write_enabled'),
        ];
    }
}
