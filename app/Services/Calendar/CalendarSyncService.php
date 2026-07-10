<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\GoogleCalendarConnection;
use App\Services\Telemetry\IntegrationHealthMonitor;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CalendarSyncService
{
    public function __construct(
        private readonly GoogleCalendarClient $client,
        private readonly CalendarEventProjector $projector,
        private readonly IntegrationHealthMonitor $health,
    ) {}

    public function sync(GoogleCalendarConnection $connection): int
    {
        $startedAt = microtime(true);
        $timeMin = now()->utc()->subDays(max(0, (int) config('services.google_calendar.sync_past_days', 7)));
        $timeMax = now()->utc()->addDays(max(1, (int) config('services.google_calendar.sync_future_days', 45)));

        try {
            $accessToken = $this->client->accessToken($connection->refresh_token);
            $publicIds = (array) config('services.google_calendar.public_event_ids', []);
            $projected = [];

            $calendarIds = array_filter(
                $connection->calendar_ids,
                fn (string $calendarId): bool => $calendarId !== '',
            );

            foreach ($calendarIds as $calendarId) {
                foreach ($this->client->events($accessToken, $calendarId, $timeMin->toIso8601String(), $timeMax->toIso8601String()) as $event) {
                    $safe = $this->projector->project($event, $calendarId, $publicIds);
                    if ($safe !== null) {
                        $projected[$safe['provider_event_key']] = $safe;
                    }
                }
            }

            DB::transaction(function () use ($connection, $projected, $timeMin, $timeMax): void {
                foreach ($projected as $safe) {
                    $safe['user_id'] = $connection->user_id;
                    $existing = $connection->events()
                        ->where('provider_event_key', $safe['provider_event_key'])
                        ->first();
                    if ($existing?->source === 'local') {
                        $safe['public_title'] = $existing->public_title;
                        $safe['category'] = $existing->category;
                        $safe['source'] = 'local';
                    }
                    $connection->events()->updateOrCreate(
                        ['provider_event_key' => $safe['provider_event_key']],
                        $safe,
                    );
                }

                $stale = $connection->events()
                    ->where('source', 'google')
                    ->where('starts_at', '>=', $timeMin)
                    ->where('starts_at', '<', $timeMax);
                if ($projected !== []) {
                    $stale->whereNotIn('provider_event_key', array_keys($projected));
                }
                $stale->delete();

                $connection->update([
                    'status' => 'connected',
                    'last_synced_at' => now()->utc(),
                    'last_error_code' => null,
                ]);
            });

            $this->health->success('google_calendar', $startedAt);

            return count($projected);
        } catch (Throwable $exception) {
            $status = $exception instanceof RequestException ? $exception->response->status() : null;
            $connection->update([
                'status' => in_array($status, [400, 401, 403], true) ? 'reauth_required' : 'unavailable',
                'last_error_code' => $status === null ? class_basename($exception) : 'http_'.$status,
            ]);
            $this->health->failure('google_calendar', $startedAt);

            throw $exception;
        }
    }
}
