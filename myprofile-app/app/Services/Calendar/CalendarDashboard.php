<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use App\Models\GoogleCalendarConnection;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class CalendarDashboard
{
    /** @return array<string, mixed> */
    public function forHome(): array
    {
        $timezone = (string) config('portfolio.presentation_timezone', 'America/Fortaleza');
        $now = CarbonImmutable::now($timezone);
        $weekStart = $now->startOfDay();
        $weekEnd = $weekStart->addWeek();
        $monthStart = $now->startOfMonth();
        $monthGridStart = $monthStart->startOfWeek(CarbonInterface::MONDAY);
        $monthGridEnd = $monthGridStart->addDays(42);
        $connection = GoogleCalendarConnection::query()->latest()->first();

        $events = CalendarEvent::query()
            ->where('status', '!=', 'cancelado')
            ->where('ends_at', '>', $monthGridStart->utc())
            ->where('starts_at', '<', $monthGridEnd->utc())
            ->orderBy('starts_at')
            ->get();
        $weekEvents = $events->filter(fn (CalendarEvent $event): bool => $event->ends_at->greaterThan($weekStart->utc())
            && $event->starts_at->lessThan($weekEnd->utc()));
        $manageableEvents = CalendarEvent::query()
            ->whereNotNull('user_id')
            ->where('source', 'local')
            ->where('status', '!=', 'cancelado')
            ->where('ends_at', '>', $weekStart->utc())
            ->orderBy('starts_at')
            ->limit(100)
            ->get();

        $days = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $weekEvents, $timezone): array {
            $dayStart = $weekStart->addDays($offset);

            return [
                'date' => $dayStart->toDateString(),
                'weekday' => ucfirst($dayStart->locale('pt_BR')->translatedFormat('D')),
                'label' => $dayStart->format('d/m'),
                'is_today' => $dayStart->isToday(),
                'events' => $this->weeklyEventsForDay($weekEvents, $dayStart, $timezone),
            ];
        })->all();

        $monthDays = collect(range(0, 41))->map(function (int $offset) use ($monthGridStart, $monthStart, $events, $timezone): array {
            $dayStart = $monthGridStart->addDays($offset);

            return [
                'date' => $dayStart->toDateString(),
                'day' => $dayStart->day,
                'is_today' => $dayStart->isToday(),
                'is_current_month' => $dayStart->month === $monthStart->month && $dayStart->year === $monthStart->year,
                'events' => $this->monthlyEventsForDay($events, $dayStart, $timezone),
            ];
        })->all();

        return [
            'configured' => filled(config('services.google_calendar.client_id'))
                && filled(config('services.google_calendar.client_secret')),
            'connected' => $connection !== null,
            'status' => $connection->status ?? 'not_connected',
            'last_synced_at' => $connection?->last_synced_at,
            'range_label' => $weekStart->format('d/m').'–'.$weekEnd->subDay()->format('d/m'),
            'days' => $days,
            'event_count' => $weekEvents->count(),
            'month_label' => ucfirst($monthStart->locale('pt_BR')->translatedFormat('F Y')),
            'month_days' => $monthDays,
            'manageable_events' => $manageableEvents
                ->map(fn (CalendarEvent $event): array => [
                    'id' => $event->getKey(),
                    'title' => $event->public_title,
                    'category' => $event->category,
                    'starts_at' => CarbonImmutable::instance($event->starts_at)->setTimezone($timezone)->format('Y-m-d\TH:i'),
                    'ends_at' => CarbonImmutable::instance($event->ends_at)->setTimezone($timezone)->format('Y-m-d\TH:i'),
                    'all_day' => $event->all_day,
                    'source' => $event->source,
                    'sync_status' => $event->sync_status,
                ])->values()->all(),
            'write_enabled' => (bool) config('services.google_calendar.write_enabled'),
        ];
    }

    /** @param iterable<CalendarEvent> $events
     * @return list<array<string, mixed>>
     */
    private function weeklyEventsForDay(iterable $events, CarbonImmutable $dayStart, string $timezone): array
    {
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
            $isAllDay = $event->all_day
                || ($start->isStartOfDay() && $end->isStartOfDay() && $start->diffInHours($end) >= 24);
            $startMinutes = $isAllDay ? 0 : $dayStart->diffInMinutes($visibleStart);
            $durationMinutes = $isAllDay ? 1440 : max(15, $visibleStart->diffInMinutes($visibleEnd));
            $segments[] = [
                'id' => $event->getKey(),
                'title' => $event->public_title,
                'category' => $event->category,
                'status' => $event->status,
                'all_day' => $isAllDay,
                'source' => $event->source,
                'sync_status' => $event->sync_status,
                'time' => $isAllDay ? 'Dia inteiro' : $visibleStart->format('H:i').'–'.$visibleEnd->format('H:i'),
                'offset' => round(($startMinutes / 1440) * 100, 3),
                'width' => round((min($durationMinutes, 1440 - $startMinutes) / 1440) * 100, 3),
            ];
        }

        return $segments;
    }

    /** @param iterable<CalendarEvent> $events
     * @return list<array<string, mixed>>
     */
    private function monthlyEventsForDay(iterable $events, CarbonImmutable $dayStart, string $timezone): array
    {
        $dayEnd = $dayStart->addDay();
        $items = [];

        foreach ($events as $event) {
            $start = CarbonImmutable::instance($event->starts_at)->setTimezone($timezone);
            $end = CarbonImmutable::instance($event->ends_at)->setTimezone($timezone);
            if ($end->lessThanOrEqualTo($dayStart) || $start->greaterThanOrEqualTo($dayEnd)) {
                continue;
            }

            $isAllDay = $event->all_day
                || ($start->isStartOfDay() && $end->isStartOfDay() && $start->diffInHours($end) >= 24);
            $items[] = [
                'id' => $event->getKey(),
                'title' => $event->public_title,
                'category' => $event->category,
                'time' => $isAllDay ? 'Dia inteiro' : ($start->lessThan($dayStart) ? 'Em andamento' : $start->format('H:i')),
            ];
        }

        return $items;
    }
}
