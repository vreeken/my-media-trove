<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaItemResource;
use App\Models\Barcode;
use App\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarcodeController extends Controller
{
    /**
     * Look up a barcode and return associated media items.
     *
     * Returns media items ranked by vote count (ties broken alphabetically).
     * Also indicates if any result has 3+ confirmations (high confidence).
     */
    public function lookup(Request $request, string $barcode): JsonResponse
    {
        // Normalize barcode (remove any non-numeric characters)
        $normalizedBarcode = preg_replace('/[^0-9]/', '', $barcode);

        if (strlen($normalizedBarcode) < 8 || strlen($normalizedBarcode) > 14) {
            return response()->json([
                'message' => 'Invalid barcode format',
            ], 422);
        }

        // Look up in our database
        $results = Barcode::query()
            ->select('media_item_id', DB::raw('COUNT(*) as vote_count'))
            ->where('barcode', $normalizedBarcode)
            ->groupBy('media_item_id')
            ->orderByDesc('vote_count')
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'data' => [
                    'found' => false,
                    'barcode' => $normalizedBarcode,
                    'barcode_type' => Barcode::detectBarcodeType($normalizedBarcode),
                    'matches' => [],
                    'high_confidence_match' => null,
                ],
            ]);
        }

        // Get media items with vote counts
        $mediaItemIds = $results->pluck('media_item_id')->toArray();
        $voteCounts = $results->pluck('vote_count', 'media_item_id')->toArray();

        $mediaItems = MediaItem::whereIn('id', $mediaItemIds)
            ->orderBy('title')
            ->get();

        // Sort by vote count desc, then title asc
        $matches = $mediaItems->map(function ($item) use ($voteCounts) {
            return [
                'media_item' => [
                    'id' => $item->id,
                    'type' => $item->type->value,
                    'type_label' => $item->type->label(),
                    'title' => $item->title,
                    'year' => $item->year,
                    'poster_url' => $item->poster_url,
                    'external_id' => $item->external_id,
                    'external_source' => $item->external_source,
                ],
                'vote_count' => $voteCounts[$item->id] ?? 0,
            ];
        })->sortBy([
            fn ($a, $b) => $b['vote_count'] <=> $a['vote_count'],
            fn ($a, $b) => $a['media_item']['title'] <=> $b['media_item']['title'],
        ])->values();

        // Check if there's a high confidence match (3+ votes and single result, or significantly higher than others)
        $highConfidenceMatch = null;
        if ($matches->count() === 1 && $matches[0]['vote_count'] >= 3) {
            $highConfidenceMatch = $matches[0];
        } elseif ($matches->count() > 1) {
            $topVotes = $matches[0]['vote_count'];
            $secondVotes = $matches[1]['vote_count'];
            // High confidence if top has 3+ votes AND at least 2x the second place
            if ($topVotes >= 3 && $topVotes >= $secondVotes * 2) {
                $highConfidenceMatch = $matches[0];
            }
        }

        return response()->json([
            'data' => [
                'found' => true,
                'barcode' => $normalizedBarcode,
                'barcode_type' => Barcode::detectBarcodeType($normalizedBarcode),
                'matches' => $matches,
                'high_confidence_match' => $highConfidenceMatch,
            ],
        ]);
    }

    /**
     * Create a barcode-to-media association.
     *
     * This represents the current user's vote that this barcode
     * corresponds to this media item.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'min:8', 'max:20'],
            'media_item_id' => ['required', 'uuid', 'exists:media_items,id'],
        ]);

        $user = $request->user();
        $barcode = preg_replace('/[^0-9]/', '', $validated['barcode']);

        // Check if user already voted for this combination
        if (Barcode::hasUserVoted($barcode, $validated['media_item_id'], $user->id)) {
            return response()->json([
                'message' => 'You have already confirmed this barcode association',
            ], 409);
        }

        $barcodeRecord = Barcode::create([
            'barcode' => $barcode,
            'barcode_type' => Barcode::detectBarcodeType($barcode),
            'media_item_id' => $validated['media_item_id'],
            'user_id' => $user->id,
        ]);

        // Get the current vote count for this barcode-media combination
        $voteCount = Barcode::where('barcode', $barcode)
            ->where('media_item_id', $validated['media_item_id'])
            ->count();

        return response()->json([
            'message' => 'Barcode association created',
            'data' => [
                'id' => $barcodeRecord->id,
                'barcode' => $barcode,
                'barcode_type' => $barcodeRecord->barcode_type,
                'media_item_id' => $validated['media_item_id'],
                'vote_count' => $voteCount,
            ],
        ], 201);
    }

    /**
     * Report a barcode association as incorrect.
     *
     * This removes the current user's vote for the association if they had one.
     * Optionally creates a new vote for a different media item.
     */
    public function reportIncorrect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'min:8', 'max:20'],
            'incorrect_media_item_id' => ['required', 'uuid', 'exists:media_items,id'],
            'correct_media_item_id' => ['nullable', 'uuid', 'exists:media_items,id'],
        ]);

        $user = $request->user();
        $barcode = preg_replace('/[^0-9]/', '', $validated['barcode']);

        // Remove user's vote for the incorrect association
        Barcode::where('barcode', $barcode)
            ->where('media_item_id', $validated['incorrect_media_item_id'])
            ->where('user_id', $user->id)
            ->delete();

        // If a correct media item was provided, create a vote for it
        if (! empty($validated['correct_media_item_id'])) {
            if (! Barcode::hasUserVoted($barcode, $validated['correct_media_item_id'], $user->id)) {
                Barcode::create([
                    'barcode' => $barcode,
                    'barcode_type' => Barcode::detectBarcodeType($barcode),
                    'media_item_id' => $validated['correct_media_item_id'],
                    'user_id' => $user->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Report received. Thank you for helping improve our database.',
        ]);
    }

    /**
     * Check if a product title suggests it's a box set.
     */
    public function checkBoxSet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:500'],
        ]);

        $isBoxSet = Barcode::isLikelyBoxSet($validated['title']);
        $franchiseName = $isBoxSet ? Barcode::extractFranchiseName($validated['title']) : null;

        return response()->json([
            'data' => [
                'is_box_set' => $isBoxSet,
                'franchise_name' => $franchiseName,
                'original_title' => $validated['title'],
            ],
        ]);
    }
}
