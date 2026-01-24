<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * UserMediaItem represents an item in a user's personal collection.
 *
 * Links a user to a MediaItem with user-specific data like rating,
 * formats owned, notes, location, and tags.
 */
class UserMediaItem extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'media_item_id',
        'location_id',
        'formats',
        'rating',
        'notes',
        'is_digital',
        'digital_platform',
        'digital_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'formats' => 'array',
            'rating' => 'integer',
            'is_digital' => 'boolean',
        ];
    }

    /**
     * Check if this digital platform requires a path input.
     */
    public function digitalPlatformRequiresPath(): bool
    {
        if (! $this->is_digital || ! $this->digital_platform) {
            return false;
        }

        $platforms = config('digital_platforms.platforms', []);

        return $platforms[$this->digital_platform]['requires_path'] ?? false;
    }

    /**
     * Get the digital platform display name.
     */
    public function getDigitalPlatformNameAttribute(): ?string
    {
        if (! $this->is_digital || ! $this->digital_platform) {
            return null;
        }

        $platforms = config('digital_platforms.platforms', []);

        return $platforms[$this->digital_platform]['name'] ?? $this->digital_platform;
    }

    /**
     * Get the digital platform search URL with title filled in and affiliate ID appended.
     *
     * @param  string|null  $title  The media title to search for
     */
    public function getDigitalPlatformSearchUrl(?string $title = null): ?string
    {
        if (! $this->is_digital || ! $this->digital_platform) {
            return null;
        }

        $platforms = config('digital_platforms.platforms', []);
        $platform = $platforms[$this->digital_platform] ?? null;
        $searchUrlTemplate = $platform['search_url'] ?? null;

        if (! $searchUrlTemplate) {
            return null;
        }

        // Replace placeholders with URL-encoded values
        $searchUrl = str_replace(
            ['{title}', '{year}'],
            [urlencode($title ?? ''), urlencode((string) ($this->mediaItem?->year ?? ''))],
            $searchUrlTemplate
        );

        // Append affiliate parameter if configured
        $searchUrl = $this->appendAffiliateParam($searchUrl, $platform);

        return $searchUrl;
    }

    /**
     * Append affiliate parameter to a URL if configured.
     */
    protected function appendAffiliateParam(string $url, array $platform): string
    {
        $affiliateParam = $platform['affiliate_param'] ?? null;
        $affiliateEnv = $platform['affiliate_env'] ?? null;

        if (! $affiliateParam || ! $affiliateEnv) {
            return $url;
        }

        $affiliateId = env($affiliateEnv);

        if (! $affiliateId) {
            return $url;
        }

        // Append the affiliate parameter
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . $affiliateParam . '=' . urlencode($affiliateId);
    }

    /**
     * Get the digital platform URL with affiliate ID appended.
     */
    public function getDigitalPlatformUrlAttribute(): ?string
    {
        if (! $this->is_digital || ! $this->digital_platform) {
            return null;
        }

        $platforms = config('digital_platforms.platforms', []);
        $platform = $platforms[$this->digital_platform] ?? null;
        $url = $platform['url'] ?? null;

        if (! $url) {
            return null;
        }

        // Append affiliate parameter if configured
        return $this->appendAffiliateParam($url, $platform);
    }

    /**
     * Get the user who owns this collection item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the media item from the shared catalog.
     */
    public function mediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class);
    }

    /**
     * Get the location where this media is stored.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the tags associated with this collection item.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'user_media_item_tag')
            ->withTimestamps();
    }

    /**
     * Scope to filter by minimum rating.
     */
    public function scopeMinRating($query, int $minRating)
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Scope to filter by location.
     */
    public function scopeAtLocation($query, string $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope to filter by tag.
     */
    public function scopeWithTag($query, string $tagId)
    {
        return $query->whereHas('tags', function ($q) use ($tagId) {
            $q->where('tags.id', $tagId);
        });
    }
}
