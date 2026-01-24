<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $mediaItem = $this->mediaItem;

        return [
            'id' => $this->id,
            'media_item_id' => $mediaItem->id,
            'type' => $mediaItem->type->value,
            'type_label' => $mediaItem->type->label(),
            'title' => $mediaItem->title,
            'original_title' => $mediaItem->original_title,
            'year' => $mediaItem->year,
            'description' => $mediaItem->description,
            'poster_url' => $mediaItem->poster_url,
            'external_id' => $mediaItem->external_id,
            'external_source' => $mediaItem->external_source,
            'is_custom' => $mediaItem->is_custom,
            'notes' => $this->notes,
            'priority' => $this->priority,
            'metadata' => $mediaItem->metadata ?? [],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
