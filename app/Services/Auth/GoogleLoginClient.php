<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;

final class GoogleLoginClient
{
    public function isConfigured(): bool
    {
        return (bool) config('services.google_login.enabled')
            && filled(config('services.google_login.client_id'))
            && filled(config('services.google_login.client_secret'))
            && filled(config('services.google_login.redirect_uri'));
    }

    public function authorizationUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google_login.client_id'),
            'redirect_uri' => config('services.google_login.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** @return array<string, mixed> */
    public function exchangeCode(string $code): array
    {
        return Http::asForm()
            ->acceptJson()
            ->timeout(10)
            ->retry(1, 250)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google_login.client_id'),
                'client_secret' => config('services.google_login.client_secret'),
                'redirect_uri' => config('services.google_login.redirect_uri'),
                'grant_type' => 'authorization_code',
                'code' => $code,
            ])
            ->throw()
            ->json();
    }

    /** @return array<string, mixed> */
    public function userInfo(string $accessToken): array
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(10)
            ->retry(1, 250)
            ->get('https://openidconnect.googleapis.com/v1/userinfo')
            ->throw()
            ->json();
    }
}
