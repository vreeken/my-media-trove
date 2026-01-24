<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\StreamingAvailabilityCache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for checking streaming availability of media.
 *
 * Uses the MovieOfTheNight Streaming Availability API via RapidAPI.
 * @see https://www.movieofthenight.com/about/api
 */
class StreamingAvailabilityService
{
    private readonly string $apiKey;
    private readonly string $apiHost;
    private readonly int $cacheTtlDays;
    private readonly string $defaultCountry;

    public function __construct()
    {
        $this->apiKey = config('streaming.api_key', '');
        $this->apiHost = config('streaming.api_host', 'streaming-availability.p.rapidapi.com');
        $this->cacheTtlDays = config('streaming.cache_ttl_days', 30);
        $this->defaultCountry = config('streaming.default_country', 'us');
    }

    /**
     * Check if the service is configured and available.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get streaming availability for a movie or TV show.
     *
     * @param string $imdbId The IMDb ID of the title (e.g., tt0068646)
     * @param string $country Country code (e.g., 'us', 'gb', 'ca')
     * @param bool $forceRefresh Force a fresh API call, ignoring cache
     * @return array Streaming options with cache metadata
     */
    public function getAvailability(string $imdbId, string $country = 'us', bool $forceRefresh = false): array
    {
        $country = strtolower($country ?: $this->defaultCountry);

        if (!$this->isConfigured()) {
            Log::info('Streaming availability service not configured');
            return $this->getMockResponse($imdbId);
        }

        // Check cache unless force refresh
        if (!$forceRefresh) {
            $cached = $this->getCachedData($imdbId, $country);
            if ($cached && $cached->isFresh($this->cacheTtlDays)) {
                return $this->formatCachedResponse($cached);
            }
        }

        // Fetch fresh data from API
        try {
            $apiData = $this->fetchFromApi($imdbId, $country);

            if ($apiData) {
                // Store in cache
                $cached = $this->storeInCache($imdbId, $country, $apiData);
                return $this->formatCachedResponse($cached);
            }

            // If API call failed but we have stale cache, return it
            $staleCache = $this->getCachedData($imdbId, $country);
            if ($staleCache) {
                $response = $this->formatCachedResponse($staleCache);
                $response['stale'] = true;
                $response['stale_message'] = 'API unavailable, showing cached data';
                return $response;
            }

            return [
                'configured' => true,
                'error' => true,
                'message' => 'Unable to fetch streaming availability',
            ];
        } catch (ConnectionException $e) {
            Log::error('Streaming API connection failed', ['error' => $e->getMessage()]);

            // Return stale cache if available
            $staleCache = $this->getCachedData($imdbId, $country);
            if ($staleCache) {
                $response = $this->formatCachedResponse($staleCache);
                $response['stale'] = true;
                $response['stale_message'] = 'API unavailable, showing cached data';
                return $response;
            }

            return [
                'configured' => true,
                'error' => true,
                'message' => 'Connection error while fetching streaming data',
            ];
        }
    }

    /**
     * Force refresh streaming data for a title.
     */
    public function refreshAvailability(string $imdbId, string $country = 'us'): array
    {
        return $this->getAvailability($imdbId, $country, true);
    }

    /**
     * Invalidate cached data for a title.
     */
    public function invalidateCache(string $imdbId, ?string $country = null): void
    {
        $query = StreamingAvailabilityCache::where('external_id', $imdbId)
            ->where('external_source', 'imdb');

        if ($country) {
            $query->where('country_code', strtolower($country));
        }

        $query->delete();
    }

    /**
     * Get cached data for a title.
     */
    private function getCachedData(string $imdbId, string $country): ?StreamingAvailabilityCache
    {
        return StreamingAvailabilityCache::where('external_id', $imdbId)
            ->where('external_source', 'imdb')
            ->where('country_code', $country)
            ->first();
    }

    /**
     * Store data in cache.
     */
    private function storeInCache(string $imdbId, string $country, array $data): StreamingAvailabilityCache
    {
        return StreamingAvailabilityCache::updateOrCreate(
            [
                'external_id' => $imdbId,
                'external_source' => 'imdb',
                'country_code' => $country,
            ],
            [
                'streaming_data' => $data,
                'fetched_at' => now(),
            ]
        );
    }

    /**
     * Format cached response with metadata.
     */
    private function formatCachedResponse(StreamingAvailabilityCache $cached): array
    {
        $data = $cached->streaming_data;

        return [
            'configured' => true,
            'cached' => true,
            'fetched_at' => $cached->fetched_at->toISOString(),
            'age' => $cached->age,
            'age_days' => $cached->age_days,
            'is_stale' => !$cached->isFresh($this->cacheTtlDays),
            'country' => $cached->country_code,
            'title' => $data['title'] ?? null,
            'year' => $data['year'] ?? null,
            'overview' => $data['overview'] ?? null,
            'poster_url' => $data['poster_url'] ?? null,
            'streaming' => $data['streaming'] ?? [],
            'rent' => $data['rent'] ?? [],
            'buy' => $data['buy'] ?? [],
            'free' => $data['free'] ?? [],
        ];
    }

    /**
     * Fetch streaming data from the MovieOfTheNight API.
     */
    private function fetchFromApi(string $imdbId, string $country): ?array
    {
        try {
            // The Streaming Availability API uses getShow endpoint with IMDb ID
            $response = Http::withHeaders([
                'X-RapidAPI-Key' => $this->apiKey,
                'X-RapidAPI-Host' => $this->apiHost,
            ])->get("https://{$this->apiHost}/shows/{$imdbId}", [
                'output_language' => 'en',
            ]);

            if (!$response->successful()) {
                Log::error('Streaming API request failed', [
                    'status' => $response->status(),
                    'imdb_id' => $imdbId,
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $this->mapApiResponse($response->json(), $country);
        } catch (\Exception $e) {
            Log::error('Streaming API error', [
                'error' => $e->getMessage(),
                'imdb_id' => $imdbId,
            ]);
            return null;
        }
    }

    /**
     * Map the API response to our standardized format.
     */
    private function mapApiResponse(array $data, string $country): array
    {
        $streamingOptions = $data['streamingOptions'][$country] ?? [];

        $streaming = [];
        $rent = [];
        $buy = [];
        $free = [];

        foreach ($streamingOptions as $option) {
            $mapped = [
                'service' => $option['service']['id'] ?? null,
                'service_name' => $option['service']['name'] ?? null,
                'service_logo' => $option['service']['imageSet']['darkThemeImage'] ?? null,
                'service_color' => $option['service']['themeColorCode'] ?? null,
                'link' => $option['link'] ?? null,
                'video_link' => $option['videoLink'] ?? null,
                'quality' => $option['quality'] ?? null,
                'expires_soon' => $option['expiresSoon'] ?? false,
                'expires_on' => isset($option['expiresOn']) ? date('Y-m-d', $option['expiresOn']) : null,
                'available_since' => isset($option['availableSince']) ? date('Y-m-d', $option['availableSince']) : null,
            ];

            // Handle addon (like Paramount+ with Showtime on Hulu)
            if (isset($option['addon'])) {
                $mapped['addon'] = [
                    'id' => $option['addon']['id'] ?? null,
                    'name' => $option['addon']['name'] ?? null,
                ];
            }

            // Handle price for rent/buy
            if (isset($option['price'])) {
                $mapped['price'] = $option['price']['amount'] ?? null;
                $mapped['currency'] = $option['price']['currency'] ?? 'USD';
                $mapped['price_formatted'] = $option['price']['formatted'] ?? null;
            }

            // Categorize by type
            $type = $option['type'] ?? 'subscription';
            switch ($type) {
                case 'subscription':
                case 'addon':
                    $streaming[] = $mapped;
                    break;
                case 'rent':
                    $rent[] = $mapped;
                    break;
                case 'buy':
                    $buy[] = $mapped;
                    break;
                case 'free':
                    $free[] = $mapped;
                    break;
            }
        }

        // Get poster URL
        $posterUrl = null;
        if (isset($data['imageSet']['verticalPoster']['w480'])) {
            $posterUrl = $data['imageSet']['verticalPoster']['w480'];
        }

        return [
            'title' => $data['title'] ?? null,
            'original_title' => $data['originalTitle'] ?? null,
            'year' => $data['releaseYear'] ?? null,
            'overview' => $data['overview'] ?? null,
            'poster_url' => $posterUrl,
            'genres' => array_map(fn($g) => $g['name'] ?? $g, $data['genres'] ?? []),
            'directors' => $data['directors'] ?? [],
            'cast' => $data['cast'] ?? [],
            'rating' => $data['rating'] ?? null,
            'runtime' => $data['runtime'] ?? null,
            'streaming' => $streaming,
            'rent' => $rent,
            'buy' => $buy,
            'free' => $free,
        ];
    }

    /**
     * Get mock response for development when API is not configured.
     */
    private function getMockResponse(string $imdbId): array
    {
        return [
            'configured' => false,
            'message' => 'Streaming availability service not configured. Set STREAMING_API_KEY in .env',
            'mock_data' => [
                'streaming' => [
                    [
                        'service' => 'netflix',
                        'service_name' => 'Netflix',
                        'link' => '#',
                        'quality' => 'hd',
                    ],
                    [
                        'service' => 'prime',
                        'service_name' => 'Prime Video',
                        'link' => '#',
                        'quality' => 'uhd',
                    ],
                ],
                'rent' => [
                    [
                        'service' => 'amazon',
                        'service_name' => 'Amazon',
                        'link' => '#',
                        'price' => '3.99',
                        'currency' => 'USD',
                        'quality' => 'hd',
                    ],
                ],
                'buy' => [
                    [
                        'service' => 'amazon',
                        'service_name' => 'Amazon',
                        'link' => '#',
                        'price' => '14.99',
                        'currency' => 'USD',
                        'quality' => '4k',
                    ],
                ],
                'free' => [
                    [
                        'service' => 'tubi',
                        'service_name' => 'Tubi',
                        'link' => '#',
                        'quality' => 'hd',
                    ],
                ],
            ],
        ];
    }
}
