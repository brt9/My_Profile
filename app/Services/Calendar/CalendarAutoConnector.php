<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\GoogleCalendarConnection;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CalendarAutoConnector
{
    public function __construct(private readonly GoogleCalendarClient $client) {}

    public function redirectIfRequired(Request $request, User $user): ?RedirectResponse
    {
        if (! $this->shouldConnect($user)) {
            return null;
        }

        $connection = GoogleCalendarConnection::query()->whereBelongsTo($user)->first();
        if ($connection !== null && $connection->status !== 'reauth_required') {
            return null;
        }

        $state = Str::random(64);
        $request->session()->put('google_calendar_oauth_state', $state);

        return redirect()->away($this->client->authorizationUrl($state, $user->email));
    }

    private function shouldConnect(User $user): bool
    {
        if (! config('services.google_calendar.enabled') || ! $this->client->isConfigured()) {
            return false;
        }

        if (! config('app.debug') && ! $user->hasVerifiedEmail()) {
            return false;
        }

        $adminEmail = mb_strtolower(trim((string) config('portfolio.admin_email')));
        $userEmail = mb_strtolower(trim($user->email));

        return $adminEmail !== '' && hash_equals($adminEmail, $userEmail);
    }
}
