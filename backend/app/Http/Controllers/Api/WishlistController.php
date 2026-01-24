<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wishlist\StoreWishlistItemRequest;
use App\Http\Requests\Wishlist\UpdateWishlistItemRequest;
use App\Http\Resources\WishlistItemResource;
use App\Models\MediaItem;
use App\Models\UserMediaItem;
use App\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WishlistController extends Controller
{
    /**
     * Get all wishlist items for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $request->user()
            ->wishlistItems()
            ->with('mediaItem');

        // Filter by type
        if ($request->has('type')) {
            $query->whereHas('mediaItem', function ($q) use ($request) {
                $q->where('type', $request->input('type'));
            });
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->integer('priority'));
        }

        // Search by title
        if ($request->has('search')) {
            $query->whereHas('mediaItem', function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->input('search') . '%');
            });
        }

        $query->byPriority();

        $perPage = min($request->integer('per_page', 25), 100);

        return WishlistItemResource::collection($query->paginate($perPage));
    }

    /**
     * Add item to wishlist.
     */
    public function store(StoreWishlistItemRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // Find or create the media item in the shared catalog
        if (!empty($data['external_id']) && !empty($data['external_source'])) {
            $mediaItem = MediaItem::firstOrCreate(
                [
                    'external_source' => $data['external_source'],
                    'external_id' => $data['external_id'],
                ],
                [
                    'type' => $data['type'],
                    'title' => $data['title'],
                    'year' => $data['year'] ?? null,
                    'description' => $data['description'] ?? null,
                    'poster_url' => $data['poster_url'] ?? null,
                    'is_custom' => false,
                    'metadata' => $data['metadata'] ?? null,
                ]
            );
        } else {
            // Custom media - create new entry owned by this user
            $mediaItem = MediaItem::create([
                'created_by_user_id' => $user->id,
                'type' => $data['type'],
                'title' => $data['title'],
                'year' => $data['year'] ?? null,
                'description' => $data['description'] ?? null,
                'poster_url' => $data['poster_url'] ?? null,
                'is_custom' => true,
                'metadata' => $data['metadata'] ?? null,
            ]);
        }

        // Check if user already has this in their wishlist
        $existingWishlistItem = WishlistItem::where('user_id', $user->id)
            ->where('media_item_id', $mediaItem->id)
            ->first();

        if ($existingWishlistItem) {
            return response()->json([
                'message' => 'This item is already in your wishlist',
                'data' => new WishlistItemResource($existingWishlistItem->load('mediaItem')),
            ], 409);
        }

        // Check if user already has this in their collection
        $existingCollectionItem = UserMediaItem::where('user_id', $user->id)
            ->where('media_item_id', $mediaItem->id)
            ->first();

        if ($existingCollectionItem) {
            return response()->json([
                'message' => 'This item is already in your collection',
            ], 409);
        }

        // Create wishlist entry
        $wishlistItem = WishlistItem::create([
            'user_id' => $user->id,
            'media_item_id' => $mediaItem->id,
            'notes' => $data['notes'] ?? null,
            'priority' => $data['priority'] ?? 3,
        ]);

        return response()->json([
            'message' => 'Added to wishlist',
            'data' => new WishlistItemResource($wishlistItem->load('mediaItem')),
        ], 201);
    }

    /**
     * Update wishlist item.
     */
    public function update(UpdateWishlistItemRequest $request, WishlistItem $wishlistItem): JsonResponse
    {
        // Ensure user owns this item
        if ($wishlistItem->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $wishlistItem->update($request->validated());
        $wishlistItem->load('mediaItem');

        return response()->json([
            'message' => 'Wishlist item updated',
            'data' => new WishlistItemResource($wishlistItem),
        ]);
    }

    /**
     * Remove item from wishlist.
     */
    public function destroy(Request $request, WishlistItem $wishlistItem): JsonResponse
    {
        // Ensure user owns this item
        if ($wishlistItem->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $wishlistItem->delete();

        return response()->json([
            'message' => 'Removed from wishlist',
        ]);
    }

    /**
     * Move wishlist item to collection (add as owned media).
     */
    public function moveToCollection(Request $request, WishlistItem $wishlistItem): JsonResponse
    {
        // Ensure user owns this item
        if ($wishlistItem->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'formats' => ['nullable', 'array'],
            'formats.*' => ['string'],
            'location_id' => ['nullable', 'uuid', 'exists:locations,id'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['uuid', 'exists:tags,id'],
        ]);

        $user = $request->user();
        $mediaItem = $wishlistItem->mediaItem;

        // Check if user already has this in their collection
        $existingUserMediaItem = UserMediaItem::where('user_id', $user->id)
            ->where('media_item_id', $mediaItem->id)
            ->first();

        if ($existingUserMediaItem) {
            // Delete from wishlist anyway
            $wishlistItem->delete();

            return response()->json([
                'message' => 'This media is already in your collection',
                'data' => [
                    'user_media_item_id' => $existingUserMediaItem->id,
                ],
            ], 409);
        }

        // Create user's collection entry
        $userMediaItem = UserMediaItem::create([
            'user_id' => $user->id,
            'media_item_id' => $mediaItem->id,
            'formats' => $validated['formats'] ?? null,
            'location_id' => $validated['location_id'] ?? null,
            'rating' => $validated['rating'] ?? null,
            'notes' => $validated['notes'] ?? $wishlistItem->notes,
        ]);

        // Sync tags if provided
        if (!empty($validated['tag_ids'])) {
            $userMediaItem->tags()->sync($validated['tag_ids']);
        }

        // Delete from wishlist
        $wishlistItem->delete();

        return response()->json([
            'message' => 'Moved to collection',
            'data' => [
                'user_media_item_id' => $userMediaItem->id,
            ],
        ]);
    }
}
