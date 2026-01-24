<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\MediaType;
use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use App\Services\Media\MusicBrainzService;
use App\Services\Media\OmdbService;
use App\Services\Media\StreamingAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MediaSearchController extends Controller
{
    public function __construct(
        private readonly OmdbService $omdbService,
        private readonly MusicBrainzService $musicBrainzService,
        private readonly StreamingAvailabilityService $streamingService
    ) {
    }

    /**
     * Search for movies and TV shows.
     */
    public function searchMovies(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['nullable', 'string', 'in:movie,series'],
            'year' => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $results = $this->omdbService->search(
            query: $validated['query'],
            type: $validated['type'] ?? null,
            year: $validated['year'] ?? null,
            page: $validated['page'] ?? 1
        );

        return response()->json([
            'data' => $results,
            'meta' => [
                'query' => $validated['query'],
                'source' => 'omdb',
            ],
        ]);
    }

    /**
     * Get movie/TV details by IMDb ID.
     * Checks our database first, then fetches from OMDB if not found.
     */
    public function getMovieDetails(string $imdbId): JsonResponse
    {
        // First, check if we already have this in our database
        $mediaItem = MediaItem::byExternalId('omdb', $imdbId)->first();

        if ($mediaItem) {
            // Return data from our database
            return response()->json([
                'data' => $this->formatMediaItemForResponse($mediaItem),
                'meta' => [
                    'source' => 'database',
                    'media_item_id' => $mediaItem->id,
                ],
            ]);
        }

        // Not in database, fetch from OMDB
        $details = $this->omdbService->getById($imdbId);

        if (!$details) {
            return response()->json([
                'message' => 'Media not found',
            ], 404);
        }

        // Store in our database for future requests
        $mediaItem = MediaItem::create([
            'type' => $details['type'],
            'title' => $details['title'],
            'year' => $details['year'],
            'description' => $details['description'] ?? null,
            'poster_url' => $details['poster_url'] ?? null,
            'external_id' => $details['external_id'],
            'external_source' => $details['external_source'],
            'is_custom' => false,
            'metadata' => $details['metadata'] ?? [],
        ]);

        return response()->json([
            'data' => $this->formatMediaItemForResponse($mediaItem),
            'meta' => [
                'source' => 'omdb',
                'media_item_id' => $mediaItem->id,
                'cached' => true,
            ],
        ]);
    }

    /**
     * Format a MediaItem model for API response.
     */
    private function formatMediaItemForResponse(MediaItem $mediaItem): array
    {
        return [
            'id' => $mediaItem->id,
            'external_id' => $mediaItem->external_id,
            'external_source' => $mediaItem->external_source,
            'type' => $mediaItem->type,
            'title' => $mediaItem->title,
            'year' => $mediaItem->year,
            'description' => $mediaItem->description,
            'poster_url' => $mediaItem->poster_url,
            'metadata' => $mediaItem->metadata ?? [],
        ];
    }

    /**
     * Search for music albums.
     */
    public function searchAlbums(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $results = $this->musicBrainzService->searchReleases(
            query: $validated['query'],
            limit: $validated['limit'] ?? 25,
            offset: $validated['offset'] ?? 0
        );

        // Try to get cover art for first few results
        foreach (array_slice($results, 0, 5) as $index => $result) {
            $coverArt = $this->musicBrainzService->getCoverArt($result['external_id']);
            if ($coverArt) {
                $results[$index]['poster_url'] = $coverArt;
            }
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'query' => $validated['query'],
                'source' => 'musicbrainz',
            ],
        ]);
    }

    /**
     * Search for songs.
     */
    public function searchSongs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $results = $this->musicBrainzService->searchRecordings(
            query: $validated['query'],
            limit: $validated['limit'] ?? 25,
            offset: $validated['offset'] ?? 0
        );

        return response()->json([
            'data' => $results,
            'meta' => [
                'query' => $validated['query'],
                'source' => 'musicbrainz',
            ],
        ]);
    }

    /**
     * Get album details by MusicBrainz ID.
     * Checks our database first, then fetches from MusicBrainz if not found.
     */
    public function getAlbumDetails(string $mbid): JsonResponse
    {
        // First, check if we already have this in our database
        $mediaItem = MediaItem::byExternalId('musicbrainz', $mbid)->first();

        if ($mediaItem) {
            return response()->json([
                'data' => $this->formatMediaItemForResponse($mediaItem),
                'meta' => [
                    'source' => 'database',
                    'media_item_id' => $mediaItem->id,
                ],
            ]);
        }

        // Not in database, fetch from MusicBrainz
        $details = $this->musicBrainzService->getReleaseById($mbid);

        if (!$details) {
            return response()->json([
                'message' => 'Album not found',
            ], 404);
        }

        // Get cover art
        $coverArt = $this->musicBrainzService->getCoverArt($mbid);
        if ($coverArt) {
            $details['poster_url'] = $coverArt;
        }

        // Store in our database for future requests
        $mediaItem = MediaItem::create([
            'type' => $details['type'],
            'title' => $details['title'],
            'year' => $details['year'] ?? null,
            'description' => $details['description'] ?? null,
            'poster_url' => $details['poster_url'] ?? null,
            'external_id' => $details['external_id'],
            'external_source' => $details['external_source'],
            'is_custom' => false,
            'metadata' => $details['metadata'] ?? [],
        ]);

        return response()->json([
            'data' => $this->formatMediaItemForResponse($mediaItem),
            'meta' => [
                'source' => 'musicbrainz',
                'media_item_id' => $mediaItem->id,
                'cached' => true,
            ],
        ]);
    }

    /**
     * Get streaming availability for a title.
     */
    public function getStreamingAvailability(Request $request, string $imdbId): JsonResponse
    {
        $validated = $request->validate([
            'country' => ['nullable', 'string', 'max:5'],
        ]);

        $availability = $this->streamingService->getAvailability(
            imdbId: $imdbId,
            country: $validated['country'] ?? 'us'
        );

        return response()->json([
            'data' => $availability,
            'meta' => [
                'imdb_id' => $imdbId,
                'configured' => $this->streamingService->isConfigured(),
                'cache_ttl_days' => config('streaming.cache_ttl_days'),
            ],
        ]);
    }

    /**
     * Force refresh streaming availability for a title.
     */
    public function refreshStreamingAvailability(Request $request, string $imdbId): JsonResponse
    {
        $validated = $request->validate([
            'country' => ['nullable', 'string', 'max:5'],
        ]);

        if (!$this->streamingService->isConfigured()) {
            return response()->json([
                'message' => 'Streaming service not configured',
            ], 503);
        }

        $availability = $this->streamingService->refreshAvailability(
            imdbId: $imdbId,
            country: $validated['country'] ?? 'us'
        );

        return response()->json([
            'data' => $availability,
            'meta' => [
                'imdb_id' => $imdbId,
                'refreshed' => true,
            ],
        ]);
    }

    /**
     * Unified search across all media types.
     */
    public function searchAll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:255'],
            'types' => ['nullable', 'array'],
            'types.*' => ['string', 'in:movie,tv,album,song'],
        ]);

        $types = $validated['types'] ?? ['movie', 'tv', 'album'];
        $results = [];

        // Search movies/TV
        if (in_array('movie', $types) || in_array('tv', $types)) {
            $movieType = null;
            if (in_array('movie', $types) && !in_array('tv', $types)) {
                $movieType = 'movie';
            } elseif (!in_array('movie', $types) && in_array('tv', $types)) {
                $movieType = 'series';
            }
            Log::info('Searching for movies/TV', ['query' => $validated['query'], 'type' => $movieType]);
            $movieResults = $this->omdbService->search($validated['query'], $movieType);
            $results['movies_tv'] = $movieResults;
        }

        // Search albums
        /* if (in_array('album', $types)) {
            $albumResults = $this->musicBrainzService->searchReleases($validated['query'], 10);
            $results['albums'] = $albumResults;
        } */

        // Search songs
        /* if (in_array('song', $types)) {
            $songResults = $this->musicBrainzService->searchRecordings($validated['query'], 10);
            $results['songs'] = $songResults;
        } */

        return response()->json([
            'data' => $results,
            'meta' => [
                'query' => $validated['query'],
                'searched_types' => $types,
            ],
        ]);
    }
}
