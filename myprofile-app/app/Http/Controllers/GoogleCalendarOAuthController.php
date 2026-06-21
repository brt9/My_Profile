<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SyncGoogleCalendar;
use App\Models\CalendarEvent;
use App\Models\GoogleCalendarConnection;
use App\Services\Calendar\GoogleCalendarClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class GoogleCalendarOAuthController extends Controller
{
    public function connect(Request $request, GoogleCalendarClient $client): RedirectResponse
    {
        abort_unless(config('services.google_calendar.enabled') && $client->isConfigured(), 503);

        $state = Str::random(64);
        $request->session()->put('google_calendar_oauth_state', $state);

        return redirect()->away($client->authorizationUrl($state));
    }

    public function callback(Request $request, GoogleCalendarClient $client): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('google_calendar_oauth_state');
        $state = (string) $request->query('state');
        abort_unless($expectedState !== '' && $state !== '' && hash_equals($expectedState, $state), 419);

        if ($request->filled('error')) {
            return redirect('/#agenda')->with('calendar_status', 'A autorização do Google foi cancelada.');
        }

        $code = $request->string('code')->toString();
        abort_if($code === '', 422);

        $tokens = $client->exchangeCode($code);
        $existing = GoogleCalendarConnection::query()->whereBelongsTo($request->user())->first();
        $refreshToken = $tokens['refresh_token'] ?? $existing?->refresh_token;
        abort_unless(is_string($refreshToken) && $refreshToken !== '', 422, 'O Google não retornou refresh token. Tente conectar novamente.');

        $connection = GoogleCalendarConnection::query()->updateOrCreate(
            ['user_id' => $request->user()->getKey()],
            [
                'refresh_token' => $refreshToken,
                'scopes' => array_values(array_filter(explode(' ', (string) ($tokens['scope'] ?? '')))),
                'calendar_ids' => config('services.google_calendar.calendar_ids', ['primary']),
                'status' => 'connected',
                'last_error_code' => null,
            ],
        );

        SyncGoogleCalendar::dispatch($connection->getKey());

        return redirect('/#agenda')->with('calendar_status', 'Google Agenda conectado. A primeira sincronização foi iniciada.');
    }

    public function sync(Request $request): RedirectResponse
    {
        $connection = GoogleCalendarConnection::query()->whereBelongsTo($request->user())->firstOrFail();
        SyncGoogleCalendar::dispatch($connection->getKey());

        return redirect('/#agenda')->with('calendar_status', 'Sincronização solicitada.');
    }

    public function revoke(Request $request, GoogleCalendarClient $client): RedirectResponse
    {
        $connection = GoogleCalendarConnection::query()->whereBelongsTo($request->user())->firstOrFail();
        $client->revoke($connection->refresh_token);

        DB::transaction(function () use ($connection): void {
            CalendarEvent::query()
                ->where('connection_id', $connection->getKey())
                ->where(function ($query): void {
                    $query->whereNull('user_id')->orWhere('source', 'google');
                })
                ->delete();

            CalendarEvent::query()
                ->where('connection_id', $connection->getKey())
                ->update([
                    'connection_id' => null,
                    'provider_event_id' => null,
                    'provider_calendar_id' => null,
                    'source' => 'local',
                    'sync_status' => 'local_only',
                    'synced_at' => null,
                ]);

            $connection->delete();
        });

        return redirect('/#agenda')->with('calendar_status', 'Acesso ao Google revogado. Os compromissos criados localmente foram preservados.');
    }
}
