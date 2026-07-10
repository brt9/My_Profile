<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Jobs\SyncCalendarEventToGoogle;
use App\Models\CalendarEvent;
use App\Models\GoogleCalendarConnection;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class CalendarEventManager
{
    public function __construct(private readonly GoogleCalendarClient $google) {}

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): CalendarEvent
    {
        $event = CalendarEvent::query()->create($this->localAttributes($user, $data) + [
            'provider_event_key' => hash('sha256', 'local|'.Str::uuid()->toString()),
            'source' => 'local',
            'sync_status' => 'local_only',
        ]);

        return $this->scheduleGoogleSync($event);
    }

    /** @param array<string, mixed> $data */
    public function update(CalendarEvent $event, array $data): CalendarEvent
    {
        $event->update($this->eventAttributes($data));

        return $this->scheduleGoogleSync($event->fresh());
    }

    public function cancel(CalendarEvent $event): CalendarEvent
    {
        $event->update(['status' => 'cancelado']);

        if ($this->shouldWriteGoogle($event) && filled($event->provider_event_id)) {
            try {
                $connection = $event->connection;
                $this->google->deleteEvent(
                    $this->google->accessToken($connection->refresh_token),
                    (string) $event->provider_calendar_id,
                    (string) $event->provider_event_id,
                );
                $event->update(['sync_status' => 'synced', 'synced_at' => now()->utc(), 'last_sync_error' => null]);
            } catch (Throwable $exception) {
                $this->markSyncError($event, $exception);
            }
        }

        return $event->fresh();
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function localAttributes(User $user, array $data): array
    {
        return ['user_id' => $user->getKey()] + $this->eventAttributes($data);
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function eventAttributes(array $data): array
    {
        $timezone = (string) config('portfolio.presentation_timezone', 'America/Fortaleza');
        $allDay = (bool) ($data['all_day'] ?? false);
        $start = CarbonImmutable::parse((string) $data['starts_at'], $timezone);
        $end = CarbonImmutable::parse((string) $data['ends_at'], $timezone);

        if ($allDay) {
            $start = $start->startOfDay();
            $end = $end->startOfDay();
            if ($end->lessThanOrEqualTo($start)) {
                $end = $start->addDay();
            }
        }

        return [
            'public_title' => Str::limit(strip_tags(trim((string) $data['title'])), 100, '…'),
            'category' => (string) $data['category'],
            'status' => 'confirmado',
            'starts_at' => $start->utc(),
            'ends_at' => $end->utc(),
            'all_day' => $allDay,
        ];
    }

    public function syncExistingToGoogle(CalendarEvent $event): CalendarEvent
    {
        if (! config('services.google_calendar.write_enabled')) {
            return $event;
        }

        $connection = GoogleCalendarConnection::query()
            ->where('user_id', $event->user_id)
            ->where('status', 'connected')
            ->first();

        if ($connection === null) {
            return $event;
        }

        $calendarId = (string) (collect($connection->calendar_ids)->first() ?: 'primary');

        try {
            $token = $this->google->accessToken($connection->refresh_token);
            $payload = $this->googlePayload($event);
            $provider = filled($event->provider_event_id)
                ? $this->google->updateEvent($token, $calendarId, (string) $event->provider_event_id, $payload)
                : $this->google->createEvent($token, $calendarId, $payload);
            $providerId = (string) ($provider['id'] ?? $event->provider_event_id);

            $event->update([
                'connection_id' => $connection->getKey(),
                'provider_event_id' => $providerId,
                'provider_event_key' => hash('sha256', $calendarId.'|'.$providerId),
                'provider_calendar_id' => $calendarId,
                'sync_status' => 'synced',
                'synced_at' => now()->utc(),
                'last_sync_error' => null,
            ]);
        } catch (Throwable $exception) {
            $this->markSyncError($event, $exception);
        }

        return $event->fresh();
    }

    private function scheduleGoogleSync(CalendarEvent $event): CalendarEvent
    {
        if (! config('services.google_calendar.write_enabled')) {
            return $event;
        }

        $hasConnection = GoogleCalendarConnection::query()
            ->where('user_id', $event->user_id)
            ->where('status', 'connected')
            ->exists();
        if (! $hasConnection) {
            return $event;
        }

        $event->update(['sync_status' => 'pending', 'last_sync_error' => null]);
        SyncCalendarEventToGoogle::dispatch($event->getKey())->afterResponse();

        return $event->fresh();
    }

    private function shouldWriteGoogle(CalendarEvent $event): bool
    {
        return (bool) config('services.google_calendar.write_enabled')
            && $event->connection !== null;
    }

    /** @return array<string, mixed> */
    private function googlePayload(CalendarEvent $event): array
    {
        $timezone = (string) config('portfolio.presentation_timezone', 'America/Fortaleza');
        $start = CarbonImmutable::instance($event->starts_at)->setTimezone($timezone);
        $end = CarbonImmutable::instance($event->ends_at)->setTimezone($timezone);

        return [
            'summary' => $event->public_title,
            'start' => $event->all_day
                ? ['date' => $start->toDateString()]
                : ['dateTime' => $start->toIso8601String(), 'timeZone' => $timezone],
            'end' => $event->all_day
                ? ['date' => $end->toDateString()]
                : ['dateTime' => $end->toIso8601String(), 'timeZone' => $timezone],
            'extendedProperties' => ['private' => ['portfolio_type' => $event->category]],
        ];
    }

    private function markSyncError(CalendarEvent $event, Throwable $exception): void
    {
        $event->update([
            'sync_status' => 'error',
            'last_sync_error' => Str::limit(class_basename($exception), 120, ''),
        ]);
        Log::warning('Calendar event Google sync failed', [
            'event_id' => $event->getKey(),
            'exception' => class_basename($exception),
        ]);
    }
}
