<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MediaItem represents the shared media catalog.
 *
 * Common media data (title, year, description, etc.) is stored once here
 * and referenced by UserMediaItem for user-specific collection data.
 */
class MediaItem extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'created_by_user_id',
        'type',
        'title',
        'original_title',
        'year',
        'description',
        'poster_url',
        'external_id',
        'external_source',
        'is_custom',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'year' => 'integer',
            'is_custom' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user who created this custom media item.
     * Only set for custom/homemade media.
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get all user collection entries for this media item.
     */
    public function userMediaItems(): HasMany
    {
        return $this->hasMany(UserMediaItem::class);
    }

    /**
     * Get all wishlist entries for this media item.
     */
    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    /**
     * Scope to filter by media type.
     */
    public function scopeOfType($query, MediaType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter custom/homemade media.
     */
    public function scopeCustom($query, bool $isCustom = true)
    {
        return $query->where('is_custom', $isCustom);
    }

    /**
     * Scope to find by external ID (e.g., IMDb ID, MusicBrainz ID).
     */
    public function scopeByExternalId($query, string $source, string $externalId)
    {
        return $query->where('external_source', $source)
            ->where('external_id', $externalId);
    }

    /**
     * Scope to only show shared media (not custom) or custom media created by a specific user.
     */
    public function scopeVisibleToUser($query, string $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('is_custom', false)
                ->orWhere('created_by_user_id', $userId);
        });
    }
}
