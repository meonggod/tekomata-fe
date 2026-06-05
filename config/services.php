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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    /*
    |--------------------------------------------------------------------------
    | tekomata Go API
    |--------------------------------------------------------------------------
    |
    | The single backend surface for this control panel. All product, tenant,
    | auth and metering data is read/written through this HTTP API — never a
    | local database. See app/Services/Tekomata and CLAUDE.md.
    |
    */
    'tekomata' => [
        'base_url' => env('TEKOMATA_API_URL', 'http://127.0.0.1:8080'),
        'timeout' => (int) env('TEKOMATA_API_TIMEOUT', 10),      // hard cap per request (s)
        'connect_timeout' => (int) env('TEKOMATA_API_CONNECT_TIMEOUT', 5),
        'retries' => (int) env('TEKOMATA_API_RETRIES', 2),       // transient (5xx/connection) only
        'retry_sleep_ms' => (int) env('TEKOMATA_API_RETRY_SLEEP_MS', 200),
    ],

];
