<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\MediaType;
use App\Http\Controllers\Controller;
use App\Http\Requests\MediaItem\StoreMediaItemRequest;
use App\Http\Requests\MediaItem\UpdateMediaItemRequest;
use App\Http\Resources\MediaItemResource;
use App\Models\MediaItem;
use App\Models\UserMediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MediaItemController extends Controller
{
    /**
     * Get all media items for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $request->user()->userMediaItems()->with(['mediaItem', 'tags', 'location']);

        // Filter by type (on media_items table)
        if ($request->has('type')) {
            $query->whereHas('mediaItem', function ($q) use ($request) {
                $q->where('type', $request->input('type'));
            });
        }

        // Filter by custom/non-custom (on media_items table)
        if ($request->has('is_custom')) {
            $query->whereHas('mediaItem', function ($q) use ($request) {
                $q->where('is_custom', $request->boolean('is_custom'));
            });
        }

        // Filter by location
        if ($request->has('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        // Filter by tag
        if ($request->has('tag_id')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.id', $request->input('tag_id'));
            });
        }

        // Filter by minimum rating
        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->integer('min_rating'));
        }

        // Search by title (on media_items table)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('mediaItem', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('original_title', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        // Sort fields that exist on user_media_items
        $userMediaItemSorts = ['rating', 'created_at', 'updated_at'];
        // Sort fields that exist on media_items
        $mediaItemSorts = ['title', 'year'];

        if (in_array($sortBy, $userMediaItemSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } elseif (in_array($sortBy, $mediaItemSorts)) {
            // Join with media_items for sorting
            $query->join('media_items', 'user_media_items.media_item_id', '=', 'media_items.id')
                ->orderBy('media_items.' . $sortBy, $sortDir === 'asc' ? 'asc' : 'desc')
                ->select('user_media_items.*');
        }

        $perPage = min($request->integer('per_page', 25), 100);

        return MediaItemResource::collection($query->paginate($perPage));
    }

    /**
     * Store a new media item in the user's collection.
     */
    public function store(StoreMediaItemRequest $request): JsonResponse
    {
        $user = $request->user();
        $mediaItemData = $request->mediaItemData();
        $userMediaItemData = $request->userMediaItemData();

        // For non-custom media with external ID, find or create in catalog
        if (! ($mediaItemData['is_custom'] ?? false) && ! empty($mediaItemData['external_id']) && ! empty($mediaItemData['external_source'])) {
            $mediaItem = MediaItem::firstOrCreate(
                [
                    'external_source' => $mediaItemData['external_source'],
                    'external_id' => $mediaItemData['external_id'],
                ],
                $mediaItemData
            );
        } else {
            // For custom media, always create new and associate with user
            $mediaItemData['is_custom'] = true;
            $mediaItemData['created_by_user_id'] = $user->id;
            $mediaItem = MediaItem::create($mediaItemData);
        }

        // Check if user already has this media in their collection
        $existingUserMediaItem = UserMediaItem::where('user_id', $user->id)
            ->where('media_item_id', $mediaItem->id)
            ->first();

        if ($existingUserMediaItem) {
            return response()->json([
                'message' => 'This media is already in your collection',
                'data' => new MediaItemResource($existingUserMediaItem->load(['mediaItem', 'tags', 'location'])),
            ], 409);
        }

        // Create user's collection entry
        $userMediaItemData['user_id'] = $user->id;
        $userMediaItemData['media_item_id'] = $mediaItem->id;

        $userMediaItem = UserMediaItem::create($userMediaItemData);

        // Sync tags if provided
        if ($request->has('tag_ids')) {
            $userMediaItem->tags()->sync($request->input('tag_ids'));
        }

        $userMediaItem->load(['mediaItem', 'tags', 'location']);

        return response()->json([
            'message' => 'Media item added to collection successfully',
            'data' => new MediaItemResource($userMediaItem),
        ], 201);
    }

    /**
     * Get a specific media item from the user's collection.
     */
    public function show(Request $request, string $userMediaItemId): MediaItemResource
    {
        // Find user's collection item
        $userMediaItem = $request->user()->userMediaItems()
            ->with(['mediaItem', 'tags', 'location'])
            ->findOrFail($userMediaItemId);

        return new MediaItemResource($userMediaItem);
    }

    /**
     * Update a media item in the user's collection.
     */
    public function update(UpdateMediaItemRequest $request, string $userMediaItemId): JsonResponse
    {
        // Find user's collection item
        $userMediaItem = $request->user()->userMediaItems()->findOrFail($userMediaItemId);

        $data = $request->validated();

        // Update user-specific data only
        $userMediaItem->update($data);

        // Sync tags if provided
        if (isset($data['tag_ids'])) {
            $userMediaItem->tags()->sync($data['tag_ids']);
        }

        $userMediaItem->load(['mediaItem', 'tags', 'location']);

        return response()->json([
            'message' => 'Media item updated successfully',
            'data' => new MediaItemResource($userMediaItem),
        ]);
    }

    /**
     * Delete a media item from the user's collection.
     */
    public function destroy(Request $request, string $userMediaItemId): JsonResponse
    {
        // Find user's collection item
        $userMediaItem = $request->user()->userMediaItems()->findOrFail($userMediaItemId);

        $userMediaItem->delete();

        return response()->json([
            'message' => 'Media item removed from collection',
        ]);
    }

    /**
     * Get available media types.
     */
    public function types(): JsonResponse
    {
        $types = array_map(fn (MediaType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'icon' => $type->icon(),
            'formats' => $type->availableFormats(),
        ], MediaType::cases());

        return response()->json([
            'data' => $types,
        ]);
    }

    /**
     * Get media collection statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = [
            'total' => $user->userMediaItems()->count(),
            'by_type' => [],
            'custom_count' => $user->userMediaItems()
                ->whereHas('mediaItem', fn ($q) => $q->where('is_custom', true))
                ->count(),
        ];

        foreach (MediaType::cases() as $type) {
            $stats['by_type'][$type->value] = [
                'count' => $user->userMediaItems()
                    ->whereHas('mediaItem', fn ($q) => $q->where('type', $type))
                    ->count(),
                'label' => $type->label(),
            ];
        }

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get available digital platforms for media storage.
     */
    public function digitalPlatforms(): JsonResponse
    {
        $platforms = config('digital_platforms.platforms', []);
        $categories = config('digital_platforms.categories', []);

        // Group platforms by category
        $grouped = [];
        foreach ($categories as $categoryKey => $categoryLabel) {
            $grouped[$categoryKey] = [
                'label' => $categoryLabel,
                'platforms' => [],
            ];
        }

        foreach ($platforms as $key => $platform) {
            $category = $platform['category'] ?? 'store';
            if (isset($grouped[$category])) {
                $grouped[$category]['platforms'][] = [
                    'id' => $key,
                    'name' => $platform['name'],
                    'icon' => $platform['icon'] ?? null,
                    'requires_path' => $platform['requires_path'] ?? false,
                    'url' => $platform['url'] ?? null,
                    'search_url' => $platform['search_url'] ?? null,
                ];
            }
        }

        // Filter out empty categories
        $grouped = array_filter($grouped, fn ($cat) => ! empty($cat['platforms']));

        return response()->json([
            'data' => [
                'grouped' => $grouped,
                'flat' => collect($platforms)->map(fn ($p, $key) => [
                    'id' => $key,
                    'name' => $p['name'],
                    'icon' => $p['icon'] ?? null,
                    'category' => $p['category'] ?? 'store',
                    'requires_path' => $p['requires_path'] ?? false,
                    'url' => $p['url'] ?? null,
                    'search_url' => $p['search_url'] ?? null,
                ])->values()->all(),
            ],
        ]);
    }
}
