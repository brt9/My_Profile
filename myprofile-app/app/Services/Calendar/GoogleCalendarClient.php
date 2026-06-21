<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class GoogleCalendarClient
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';

    private const API_URL = 'https://www.googleapis.com/calendar/v3';

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('services.google_calendar.client_id'),
            (string) config('services.google_calendar.client_secret'),
            (string) config('services.google_calendar.redirect_uri'),
        );
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '' && $this->redirectUri !== '';
    }

    public function authorizationUrl(string $state): string
    {
        $this->assertConfigured();

        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => $this->scope(),
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** @param list<string> $calendarIds
     * @return list<array{calendar_id: string, start: string, end: string}>
     */
    public function freeBusy(string $accessToken, array $calendarIds, string $timeMin, string $timeMax): array
    {
        $payload = $this->apiRequest($accessToken)
            ->post(self::API_URL.'/freeBusy', [
                'timeMin' => $timeMin,
                'timeMax' => $timeMax,
                'items' => collect($calendarIds)->map(fn (string $id): array => ['id' => $id])->all(),
            ])
            ->throw()
            ->json();
        $blocks = [];

        foreach (($payload['calendars'] ?? []) as $calendarId => $calendar) {
            if (! is_array($calendar)) {
                continue;
            }
            foreach (($calendar['busy'] ?? []) as $busy) {
                if (is_array($busy) && is_string($busy['start'] ?? null) && is_string($busy['end'] ?? null)) {
                    $blocks[] = ['calendar_id' => (string) $calendarId, 'start' => $busy['start'], 'end' => $busy['end']];
                }
            }
        }

        return $blocks;
    }

    /** @return array<string, mixed> */
    public function exchangeCode(string $code): array
    {
        $this->assertConfigured();

        return $this->tokenRequest([
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ]);
    }

    public function accessToken(string $refreshToken): string
    {
        $this->assertConfigured();

        $payload = $this->tokenRequest([
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
        ]);

        $accessToken = $payload['access_token'] ?? null;
        throw_unless(is_string($accessToken) && $accessToken !== '', RuntimeException::class, 'Google não retornou access token.');

        return $accessToken;
    }

    /** @return list<array<string, mixed>> */
    public function events(string $accessToken, string $calendarId, string $timeMin, string $timeMax): array
    {
        $items = [];
        $pageToken = null;

        for ($page = 0; $page < 5; $page++) {
            $query = [
                'timeMin' => $timeMin,
                'timeMax' => $timeMax,
                'singleEvents' => 'true',
                'showDeleted' => 'true',
                'orderBy' => 'startTime',
                'maxResults' => 500,
                'fields' => 'items(id,status,summary,visibility,start,end,extendedProperties),nextPageToken',
            ];

            if ($pageToken !== null) {
                $query['pageToken'] = $pageToken;
            }

            $payload = $this->apiRequest($accessToken)
                ->get(self::API_URL.'/calendars/'.rawurlencode($calendarId).'/events', $query)
                ->throw()
                ->json();

            foreach (($payload['items'] ?? []) as $item) {
                if (is_array($item)) {
                    $items[] = $item;
                }
            }

            $pageToken = is_string($payload['nextPageToken'] ?? null) ? $payload['nextPageToken'] : null;
            if ($pageToken === null) {
                break;
            }
        }

        return $items;
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createEvent(string $accessToken, string $calendarId, array $payload): array
    {
        return $this->apiRequest($accessToken)
            ->post(self::API_URL.'/calendars/'.rawurlencode($calendarId).'/events', $payload)
            ->throw()
            ->json();
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateEvent(string $accessToken, string $calendarId, string $eventId, array $payload): array
    {
        return $this->apiRequest($accessToken)
            ->put(self::API_URL.'/calendars/'.rawurlencode($calendarId).'/events/'.rawurlencode($eventId), $payload)
            ->throw()
            ->json();
    }

    public function deleteEvent(string $accessToken, string $calendarId, string $eventId): void
    {
        $this->apiRequest($accessToken)
            ->delete(self::API_URL.'/calendars/'.rawurlencode($calendarId).'/events/'.rawurlencode($eventId))
            ->throw();
    }

    public function revoke(string $refreshToken): void
    {
        Http::asForm()
            ->acceptJson()
            ->timeout(8)
            ->post(self::REVOKE_URL, ['token' => $refreshToken])
            ->throw();
    }

    /** @param array<string, string> $parameters
     * @return array<string, mixed>
     */
    private function tokenRequest(array $parameters): array
    {
        return Http::asForm()
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 250)
            ->post(self::TOKEN_URL, $parameters)
            ->throw()
            ->json();
    }

    private function apiRequest(string $accessToken): PendingRequest
    {
        return Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(10)
            ->retry(2, 250);
    }

    private function assertConfigured(): void
    {
        throw_unless($this->isConfigured(), RuntimeException::class, 'Google Calendar OAuth não configurado.');
    }

    private function scope(): string
    {
        return config('services.google_calendar.write_enabled')
            ? 'https://www.googleapis.com/auth/calendar.events'
            : 'https://www.googleapis.com/auth/calendar.events.readonly';
    }
}
