<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Barcode represents a user's vote that a barcode matches a media item.
 *
 * Multiple users can vote for the same barcode-media combination.
 * The same barcode can be associated with multiple media items
 * (to handle user errors, with cleanup later if needed).
 */
class Barcode extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'barcode',
        'barcode_type',
        'media_item_id',
        'user_id',
    ];

    /**
     * Get the media item this barcode is associated with.
     */
    public function mediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class);
    }

    /**
     * Get the user who made this association.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Look up media items associated with a barcode, ranked by vote count.
     *
     * @param  string  $barcode  The barcode to look up
     * @return \Illuminate\Support\Collection Collection of media items with vote_count
     */
    public static function lookupByBarcode(string $barcode): \Illuminate\Support\Collection
    {
        return static::query()
            ->select('media_item_id', DB::raw('COUNT(*) as vote_count'))
            ->where('barcode', $barcode)
            ->groupBy('media_item_id')
            ->orderByDesc('vote_count')
            ->with(['mediaItem' => function ($query) {
                $query->orderBy('title');
            }])
            ->get()
            ->map(function ($row) {
                return [
                    'media_item' => $row->mediaItem,
                    'vote_count' => $row->vote_count,
                ];
            })
            ->sortBy([
                ['vote_count', 'desc'],
                ['media_item.title', 'asc'],
            ])
            ->values();
    }

    /**
     * Check if a user has already voted for a barcode-media combination.
     */
    public static function hasUserVoted(string $barcode, string $mediaItemId, string $userId): bool
    {
        return static::where('barcode', $barcode)
            ->where('media_item_id', $mediaItemId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Detect barcode type from the barcode string.
     */
    public static function detectBarcodeType(string $barcode): string
    {
        $length = strlen(preg_replace('/[^0-9]/', '', $barcode));

        return match ($length) {
            12 => 'upc_a',
            13 => 'ean_13',
            8 => 'ean_8',
            14 => 'gtin_14',
            default => 'unknown',
        };
    }

    /**
     * Keywords that suggest a product is a box set.
     */
    public static function getBoxSetKeywords(): array
    {
        return [
            'trilogy',
            'collection',
            'box set',
            'boxset',
            'complete series',
            'complete season',
            'anthology',
            'saga',
            'the complete',
            'seasons 1',
            'seasons 2',
            '-film collection',
            '-movie collection',
            'film collection',
            'movie collection',
        ];
    }

    /**
     * Check if a product title suggests it's a box set.
     */
    public static function isLikelyBoxSet(string $title): bool
    {
        $lowerTitle = strtolower($title);

        foreach (static::getBoxSetKeywords() as $keyword) {
            if (str_contains($lowerTitle, $keyword)) {
                return true;
            }
        }

        // Check for patterns like "3-Film", "4 Movie", etc.
        if (preg_match('/\d+[- ]?(film|movie|disc|dvd|bluray|blu-ray)/i', $title)) {
            return true;
        }

        return false;
    }

    /**
     * Extract franchise name from a box set title for searching.
     */
    public static function extractFranchiseName(string $title): string
    {
        // Remove common box set suffixes
        $patterns = [
            '/\s*[\(\[].*?[\)\]]/i', // Remove parenthetical content
            '/\s*(trilogy|collection|box\s*set|complete\s*series|anthology|saga)/i',
            '/\s*\d+[- ]?(film|movie|disc)s?\s*(collection)?/i',
            '/\s*(blu-?ray|dvd|4k|uhd|hd|digital)/i',
            '/\s*(extended|special|remastered|anniversary)\s*(edition)?s?/i',
            '/\s*(the\s+complete|complete)/i',
        ];

        $cleaned = $title;
        foreach ($patterns as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned);
        }

        return trim($cleaned);
    }
}
