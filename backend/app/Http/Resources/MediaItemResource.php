<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UserMediaItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for UserMediaItem.
 *
 * Flattens data from UserMediaItem and its related MediaItem
 * to provide a consistent API response structure.
 */
class MediaItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var UserMediaItem $this */
        $mediaItem = $this->mediaItem;

        return [
            // UserMediaItem ID (used for updates/deletes)
            'id' => $this->id,

            // Media catalog data (from media_items table)
            'type' => $mediaItem->type->value,
            'type_label' => $mediaItem->type->label(),
            'type_icon' => $mediaItem->type->icon(),
            'title' => $mediaItem->title,
            'original_title' => $mediaItem->original_title,
            'year' => $mediaItem->year,
            'description' => $mediaItem->description,
            'poster_url' => $mediaItem->poster_url,
            'external_id' => $mediaItem->external_id,
            'external_source' => $mediaItem->external_source,
            'is_custom' => $mediaItem->is_custom,
            'metadata' => $mediaItem->metadata ?? [],
            'available_formats' => $mediaItem->type->availableFormats(),

            // User-specific collection data (from user_media_items table)
            'formats' => $this->formats ?? [],
            'rating' => $this->rating,
            'notes' => $this->notes,
            'location' => new LocationResource($this->whenLoaded('location')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),

            // Digital vs Physical fields
            'is_digital' => $this->is_digital ?? false,
            'digital_platform' => $this->digital_platform,
            'digital_platform_name' => $this->digital_platform_name,
            'digital_platform_url' => $this->digital_platform_url,
            'digital_platform_search_url' => $this->getDigitalPlatformSearchUrl($mediaItem->title),
            'digital_path' => $this->digital_path,

            // Timestamps (from user_media_items - when added to collection)
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Additional reference IDs (useful for debugging/advanced features)
            'media_item_id' => $mediaItem->id,
        ];
    }
}
