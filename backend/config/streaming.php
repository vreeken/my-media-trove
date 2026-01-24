<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Streaming Availability API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the MovieOfTheNight/Streaming Availability API.
    |
    */

    // RapidAPI key for the Streaming Availability API
    'api_key' => env('STREAMING_API_KEY'),

    // API base URL (RapidAPI endpoint)
    'api_host' => env('STREAMING_API_HOST', 'streaming-availability.p.rapidapi.com'),

    // Cache TTL in days - how long to keep streaming data before considering it stale
    // Default: 30 days (streaming availability changes, but not that frequently)
    'cache_ttl_days' => (int) env('STREAMING_CACHE_TTL_DAYS', 30),

    // Default country code for streaming availability lookups
    'default_country' => env('STREAMING_DEFAULT_COUNTRY', 'us'),
];
