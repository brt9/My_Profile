<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Throwable;

final class CalendarEventProjector
{
    private const CATEGORIES = ['reuniao', 'tarefa', 'estudo', 'entrega', 'projeto'];

    /** @param array<string, mixed> $event
     * @param  list<string>  $publicEventIds
     * @return array<string, mixed>|null
     */
    public function project(array $event, string $calendarId, array $publicEventIds): ?array
    {
        $eventId = $event['id'] ?? null;
        if (! is_string($eventId) || $eventId === '') {
            return null;
        }

        $startValue = data_get($event, 'start.dateTime') ?? data_get($event, 'start.date');
        $endValue = data_get($event, 'end.dateTime') ?? data_get($event, 'end.date');
        if (! is_string($startValue) || ! is_string($endValue)) {
            return null;
        }

        try {
            $startsAt = CarbonImmutable::parse($startValue)->utc();
            $endsAt = CarbonImmutable::parse($endValue)->utc();
        } catch (Throwable) {
            return null;
        }

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            return null;
        }

        $visibility = (string) ($event['visibility'] ?? 'default');
        $isExplicitlyPublic = in_array($eventId, $publicEventIds, true)
            && ! in_array($visibility, ['private', 'confidential'], true);
        $title = $isExplicitlyPublic ? $this->sanitizeTitle($event['summary'] ?? null) : 'Compromisso';
        $type = $isExplicitlyPublic ? data_get($event, 'extendedProperties.private.portfolio_type') : null;
        $category = is_string($type) && in_array(Str::lower($type), self::CATEGORIES, true)
            ? Str::lower($type)
            : ($isExplicitlyPublic ? 'projeto' : 'ocupado');

        return [
            'provider_event_id' => $eventId,
            'provider_calendar_id' => $calendarId,
            'provider_event_key' => hash('sha256', $calendarId.'|'.$eventId),
            'public_title' => $title,
            'category' => $category,
            'status' => match ($event['status'] ?? null) {
                'tentative' => 'provisorio',
                'cancelled' => 'cancelado',
                default => 'confirmado',
            },
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'all_day' => isset($event['start']['date']) && ! isset($event['start']['dateTime']),
            'source' => 'google',
            'sync_status' => 'synced',
            'synced_at' => now()->utc(),
        ];
    }

    private function sanitizeTitle(mixed $title): string
    {
        if (! is_string($title)) {
            return 'Atividade';
        }

        $sanitized = Str::squish(strip_tags($title));

        return Str::limit($sanitized !== '' ? $sanitized : 'Atividade', 80, '…');
    }
}
