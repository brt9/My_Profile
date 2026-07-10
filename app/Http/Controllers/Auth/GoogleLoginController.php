<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\GoogleLoginClient;
use App\Services\Calendar\CalendarAutoConnector;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class GoogleLoginController extends Controller
{
    public function redirect(Request $request, GoogleLoginClient $google): RedirectResponse
    {
        abort_unless($google->isConfigured(), 503, 'Login com Google não configurado.');

        $state = Str::random(64);
        $request->session()->put('google_login_oauth_state', $state);

        return redirect()->away($google->authorizationUrl($state));
    }

    public function callback(Request $request, GoogleLoginClient $google, CalendarAutoConnector $calendar): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('google_login_oauth_state');
        $state = (string) $request->query('state');
        abort_unless($expectedState !== '' && $state !== '' && hash_equals($expectedState, $state), 419);

        if ($request->filled('error')) {
            return redirect()->route('login')->with('status', 'O login com Google foi cancelado.');
        }

        $code = $request->string('code')->toString();
        abort_if($code === '', 422);

        $tokens = $google->exchangeCode($code);
        $accessToken = $tokens['access_token'] ?? null;
        abort_unless(is_string($accessToken) && $accessToken !== '', 422);

        $profile = $google->userInfo($accessToken);
        $googleId = trim((string) ($profile['sub'] ?? ''));
        $email = mb_strtolower(trim((string) ($profile['email'] ?? '')));
        $emailVerified = filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        abort_unless($googleId !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $emailVerified, 403);

        [$user, $created] = DB::transaction(function () use ($googleId, $email, $profile): array {
            $user = User::query()->where('google_id', $googleId)->lockForUpdate()->first();
            $user ??= User::query()->where('email', $email)->lockForUpdate()->first();

            abort_if($user?->google_id !== null && ! hash_equals((string) $user->google_id, $googleId), 409);

            $created = $user === null;
            $attributes = [
                'google_id' => $googleId,
                'name' => trim((string) ($profile['name'] ?? '')) ?: Str::before($email, '@'),
                'email' => $email,
                'email_verified_at' => now(),
            ];

            if ($created) {
                $attributes['password'] = Hash::make(Str::random(64));
                $user = User::query()->create($attributes);
            } else {
                $user->update($attributes);
            }

            return [$user, $created];
        });

        if ($created) {
            event(new Registered($user));
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $calendarRedirect = $calendar->redirectIfRequired($request, $user);
        if ($calendarRedirect !== null) {
            return $calendarRedirect;
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
