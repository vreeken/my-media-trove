<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Enums\MediaType;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MusicBrainzService
{
    private const CACHE_TTL = 86400; // 24 hours
    private const RATE_LIMIT_DELAY = 1000; // 1 second between requests (MusicBrainz requirement)

    public function __construct(
        private readonly string $baseUrl = 'https://musicbrainz.org/ws/2/',
        private readonly string $userAgent = 'MyMediaTrove/1.0.0 (contact@example.com)'
    ) {
    }

    /**
     * Search for music releases (albums).
     *
     * @return array<int, array{
     *     external_id: string,
     *     external_source: string,
     *     title: string,
     *     year: int|null,
     *     type: MediaType,
     *     poster_url: string|null,
     *     metadata: array
     * }>
     */
    public function searchReleases(string $query, int $limit = 25, int $offset = 0): array
    {
        $cacheKey = "musicbrainz:release:search:" . md5("{$query}:{$limit}:{$offset}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $limit, $offset) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Accept' => 'application/json',
                ])->get($this->baseUrl . 'release/', [
                    'query' => $query,
                    'limit' => $limit,
                    'offset' => $offset,
                    'fmt' => 'json',
                ]);

                if (!$response->successful()) {
                    Log::error('MusicBrainz API request failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return [];
                }

                $data = $response->json();

                return array_map(
                    fn (array $release) => $this->mapReleaseResult($release),
                    $data['releases'] ?? []
                );
            } catch (ConnectionException $e) {
                Log::error('MusicBrainz API connection failed', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Search for recordings (songs).
     *
     * @return array<int, array{
     *     external_id: string,
     *     external_source: string,
     *     title: string,
     *     year: int|null,
     *     type: MediaType,
     *     poster_url: string|null,
     *     metadata: array
     * }>
     */
    public function searchRecordings(string $query, int $limit = 25, int $offset = 0): array
    {
        $cacheKey = "musicbrainz:recording:search:" . md5("{$query}:{$limit}:{$offset}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $limit, $offset) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Accept' => 'application/json',
                ])->get($this->baseUrl . 'recording/', [
                    'query' => $query,
                    'limit' => $limit,
                    'offset' => $offset,
                    'fmt' => 'json',
                ]);

                if (!$response->successful()) {
                    Log::error('MusicBrainz API request failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return [];
                }

                $data = $response->json();

                return array_map(
                    fn (array $recording) => $this->mapRecordingResult($recording),
                    $data['recordings'] ?? []
                );
            } catch (ConnectionException $e) {
                Log::error('MusicBrainz API connection failed', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get detailed information about a release.
     */
    public function getReleaseById(string $mbid): ?array
    {
        $cacheKey = "musicbrainz:release:detail:{$mbid}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($mbid) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Accept' => 'application/json',
                ])->get($this->baseUrl . "release/{$mbid}", [
                    'inc' => 'artists+recordings+labels+release-groups',
                    'fmt' => 'json',
                ]);

                if (!$response->successful()) {
                    return null;
                }

                $data = $response->json();

                return $this->mapReleaseDetail($data);
            } catch (ConnectionException $e) {
                Log::error('MusicBrainz API connection failed', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Get album artwork from Cover Art Archive.
     */
    public function getCoverArt(string $mbid): ?string
    {
        $cacheKey = "musicbrainz:coverart:{$mbid}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($mbid) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->userAgent,
                ])->get("https://coverartarchive.org/release/{$mbid}");

                if (!$response->successful()) {
                    return null;
                }

                $data = $response->json();
                $images = $data['images'] ?? [];

                // Find front cover or use first image
                foreach ($images as $image) {
                    if ($image['front'] ?? false) {
                        return $image['thumbnails']['500'] ?? $image['thumbnails']['large'] ?? $image['image'];
                    }
                }

                if (!empty($images)) {
                    $first = $images[0];
                    return $first['thumbnails']['500'] ?? $first['thumbnails']['large'] ?? $first['image'];
                }

                return null;
            } catch (ConnectionException $e) {
                return null;
            }
        });
    }

    /**
     * Map MusicBrainz release result to our format.
     */
    private function mapReleaseResult(array $release): array
    {
        $artists = array_map(
            fn ($credit) => $credit['artist']['name'] ?? $credit['name'] ?? '',
            $release['artist-credit'] ?? []
        );

        return [
            'external_id' => $release['id'],
            'external_source' => 'musicbrainz',
            'title' => $release['title'],
            'year' => $this->parseDate($release['date'] ?? null),
            'type' => MediaType::Album,
            'poster_url' => null, // Will be fetched separately from Cover Art Archive
            'metadata' => [
                'artist' => implode(', ', $artists),
                'country' => $release['country'] ?? null,
                'status' => $release['status'] ?? null,
                'track_count' => $release['track-count'] ?? null,
                'barcode' => $release['barcode'] ?? null,
            ],
        ];
    }

    /**
     * Map MusicBrainz recording result to our format.
     */
    private function mapRecordingResult(array $recording): array
    {
        $artists = array_map(
            fn ($credit) => $credit['artist']['name'] ?? $credit['name'] ?? '',
            $recording['artist-credit'] ?? []
        );

        $releases = $recording['releases'] ?? [];
        $firstRelease = $releases[0] ?? null;

        return [
            'external_id' => $recording['id'],
            'external_source' => 'musicbrainz',
            'title' => $recording['title'],
            'year' => $this->parseDate($firstRelease['date'] ?? null),
            'type' => MediaType::Song,
            'poster_url' => null,
            'metadata' => [
                'artist' => implode(', ', $artists),
                'length_ms' => $recording['length'] ?? null,
                'length_formatted' => $this->formatDuration($recording['length'] ?? null),
                'album' => $firstRelease['title'] ?? null,
            ],
        ];
    }

    /**
     * Map MusicBrainz release detail to our format.
     */
    private function mapReleaseDetail(array $data): array
    {
        $artists = array_map(
            fn ($credit) => $credit['artist']['name'] ?? $credit['name'] ?? '',
            $data['artist-credit'] ?? []
        );

        $tracks = [];
        foreach ($data['media'] ?? [] as $medium) {
            foreach ($medium['tracks'] ?? [] as $track) {
                $tracks[] = [
                    'position' => $track['position'],
                    'title' => $track['title'],
                    'length_ms' => $track['length'] ?? null,
                    'length_formatted' => $this->formatDuration($track['length'] ?? null),
                ];
            }
        }

        $labels = array_map(
            fn ($info) => $info['label']['name'] ?? null,
            $data['label-info'] ?? []
        );

        return [
            'external_id' => $data['id'],
            'external_source' => 'musicbrainz',
            'title' => $data['title'],
            'year' => $this->parseDate($data['date'] ?? null),
            'type' => MediaType::Album,
            'poster_url' => null,
            'metadata' => [
                'artist' => implode(', ', $artists),
                'country' => $data['country'] ?? null,
                'status' => $data['status'] ?? null,
                'barcode' => $data['barcode'] ?? null,
                'labels' => array_filter($labels),
                'tracks' => $tracks,
                'track_count' => count($tracks),
            ],
        ];
    }

    /**
     * Parse date from MusicBrainz format to year.
     */
    private function parseDate(?string $date): ?int
    {
        if (!$date) {
            return null;
        }

        if (preg_match('/^(\d{4})/', $date, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Format duration from milliseconds to mm:ss.
     */
    private function formatDuration(?int $lengthMs): ?string
    {
        if (!$lengthMs) {
            return null;
        }

        $totalSeconds = intdiv($lengthMs, 1000);
        $minutes = intdiv($totalSeconds, 60);
        $seconds = $totalSeconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
