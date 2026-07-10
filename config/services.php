<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'steam' => [
        'key' => env('STEAM_API_KEY'),
        'id' => env('STEAM_ID'),
    ],

    'github' => [
        'username' => env('GITHUB_USERNAME', 'brt9'),
        'token' => env('GITHUB_TOKEN'),
    ],

    'google_calendar' => [
        'enabled' => env('GOOGLE_CALENDAR_ENABLED', false),
        'client_id' => env('GOOGLE_CALENDAR_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_CALENDAR_REDIRECT_URI'),
        'calendar_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('GOOGLE_CALENDAR_IDS', 'primary'))))),
        'public_event_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('GOOGLE_CALENDAR_PUBLIC_EVENT_IDS', ''))))),
        'show_event_titles' => env('GOOGLE_CALENDAR_SHOW_EVENT_TITLES', false),
        'write_enabled' => env('GOOGLE_CALENDAR_WRITE_ENABLED', false),
        'sync_past_days' => (int) env('GOOGLE_CALENDAR_SYNC_PAST_DAYS', 7),
        'sync_future_days' => (int) env('GOOGLE_CALENDAR_SYNC_FUTURE_DAYS', 45),
    ],

    'google_login' => [
        'enabled' => env('GOOGLE_LOGIN_ENABLED', true),
        'client_id' => env('GOOGLE_LOGIN_CLIENT_ID', env('GOOGLE_CALENDAR_CLIENT_ID')),
        'client_secret' => env('GOOGLE_LOGIN_CLIENT_SECRET', env('GOOGLE_CALENDAR_CLIENT_SECRET')),
        'redirect_uri' => env('GOOGLE_LOGIN_REDIRECT_URI', rtrim((string) env('APP_URL'), '/').'/auth/google/callback'),
    ],

    'duolingo' => [
        'enabled' => env('DUOLINGO_ENABLED', false),
        'username' => env('DUOLINGO_USERNAME'),
        'endpoint' => env('DUOLINGO_ENDPOINT', 'https://www.duolingo.com/2017-06-30/users'),
        'cache_hours' => (int) env('DUOLINGO_CACHE_HOURS', 6),
    ],
];
