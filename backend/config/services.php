<?php

declare(strict_types=1);

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Google OAuth
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    // OMDb API for Movies/TV
    'omdb' => [
        'api_key' => env('OMDB_API_KEY'),
        'base_url' => 'https://www.omdbapi.com/',
    ],

    // MusicBrainz API for Music
    'musicbrainz' => [
        'base_url' => 'https://musicbrainz.org/ws/2/',
        'user_agent' => env('MUSICBRAINZ_USER_AGENT', 'MyMediaTrove/1.0.0 (contact@example.com)'),
    ],

    // Streaming Availability API (placeholder)
    'streaming' => [
        'api_key' => env('STREAMING_API_KEY'),
        'base_url' => env('STREAMING_API_URL', 'https://api.movieofthenight.com/'),
    ],
];
