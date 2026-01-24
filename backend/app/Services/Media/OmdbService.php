<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Enums\MediaType;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OmdbService
{
    private const CACHE_TTL = 86400; // 24 hours

    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $baseUrl = 'https://www.omdbapi.com/'
    ) {
    }

    /**
     * Search for movies and TV shows.
     *
     * @return array<int, array{
     *     external_id: string,
     *     external_source: string,
     *     title: string,
     *     year: int|null,
     *     type: MediaType,
     *     poster_url: string|null
     * }>
     */
    public function search(string $query, ?string $type = null, ?int $year = null, int $page = 1): array
    {
        if (empty($this->apiKey)) {
            Log::warning('OMDb API key not configured');
            return [];
        }

        $cacheKey = "omdb:search:" . md5("{$query}:{$type}:{$year}:{$page}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $type, $year, $page) {
            try {
                $params = [
                    'apikey' => $this->apiKey,
                    's' => $query,
                    'page' => $page,
                ];

                if ($type) {
                    $params['type'] = $type; // movie, series, episode
                }

                if ($year) {
                    $params['y'] = $year;
                }

                $response = Http::get($this->baseUrl, $params);

                if (!$response->successful()) {
                    Log::error('OMDb API request failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return [];
                }

                $data = $response->json();

                if (($data['Response'] ?? 'False') === 'False') {
                    return [];
                }

                return array_map(
                    fn (array $item) => $this->mapSearchResult($item),
                    $data['Search'] ?? []
                );
            } catch (ConnectionException $e) {
                Log::error('OMDb API connection failed', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get detailed information about a specific title.
     */
    public function getById(string $imdbId): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('OMDb API key not configured');
            return null;
        }

        $cacheKey = "omdb:detail:{$imdbId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($imdbId) {
            try {
                $response = Http::get($this->baseUrl, [
                    'apikey' => $this->apiKey,
                    'i' => $imdbId,
                    'plot' => 'full',
                ]);

                if (!$response->successful()) {
                    return null;
                }

                $data = $response->json();

                if (($data['Response'] ?? 'False') === 'False') {
                    return null;
                }

                return $this->mapDetailResult($data);
            } catch (ConnectionException $e) {
                Log::error('OMDb API connection failed', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Map OMDb search result to our format.
     */
    private function mapSearchResult(array $item): array
    {
        return [
            'external_id' => $item['imdbID'],
            'external_source' => 'omdb',
            'title' => $item['Title'],
            'year' => $this->parseYear($item['Year'] ?? null),
            'type' => $this->mapType($item['Type'] ?? 'movie'),
            'poster_url' => $this->normalizePoster($item['Poster'] ?? null),
        ];
    }

    /**
     * Map OMDb detail result to our format.
     */
    private function mapDetailResult(array $data): array
    {
        return [
            'external_id' => $data['imdbID'],
            'external_source' => 'omdb',
            'title' => $data['Title'],
            'year' => $this->parseYear($data['Year'] ?? null),
            'type' => $this->mapType($data['Type'] ?? 'movie'),
            'poster_url' => $this->normalizePoster($data['Poster'] ?? null),
            'description' => $data['Plot'] !== 'N/A' ? $data['Plot'] : null,
            'metadata' => [
                'rated' => $data['Rated'] !== 'N/A' ? $data['Rated'] : null,
                'released' => $data['Released'] !== 'N/A' ? $data['Released'] : null,
                'runtime' => $data['Runtime'] !== 'N/A' ? $data['Runtime'] : null,
                'genre' => $data['Genre'] !== 'N/A' ? $data['Genre'] : null,
                'director' => $data['Director'] !== 'N/A' ? $data['Director'] : null,
                'writer' => $data['Writer'] !== 'N/A' ? $data['Writer'] : null,
                'actors' => $data['Actors'] !== 'N/A' ? $data['Actors'] : null,
                'language' => $data['Language'] !== 'N/A' ? $data['Language'] : null,
                'country' => $data['Country'] !== 'N/A' ? $data['Country'] : null,
                'awards' => $data['Awards'] !== 'N/A' ? $data['Awards'] : null,
                'imdb_rating' => $data['imdbRating'] !== 'N/A' ? (float) $data['imdbRating'] : null,
                'imdb_votes' => $data['imdbVotes'] !== 'N/A' ? $data['imdbVotes'] : null,
                'box_office' => $data['BoxOffice'] ?? null,
                'total_seasons' => isset($data['totalSeasons']) ? (int) $data['totalSeasons'] : null,
            ],
        ];
    }

    /**
     * Map OMDb type to our MediaType enum.
     */
    private function mapType(string $type): MediaType
    {
        return match (strtolower($type)) {
            'movie' => MediaType::Movie,
            'series' => MediaType::TvShow,
            'episode' => MediaType::TvEpisode,
            default => MediaType::Movie,
        };
    }

    /**
     * Parse year from OMDb format (can be "2020" or "2020–2024" for series).
     */
    private function parseYear(?string $year): ?int
    {
        if (!$year || $year === 'N/A') {
            return null;
        }

        // Extract first year from range like "2020–2024"
        if (preg_match('/^(\d{4})/', $year, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Normalize poster URL (OMDb returns "N/A" for missing posters).
     */
    private function normalizePoster(?string $poster): ?string
    {
        if (!$poster || $poster === 'N/A') {
            return null;
        }

        return $poster;
    }
}
