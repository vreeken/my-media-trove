<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TagController extends Controller
{
    /**
     * Get all tags for the authenticated user (including system tags).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $userTags = $request->user()->tags()->orderBy('name')->get();
        $systemTags = Tag::system()->orderBy('name')->get();

        $allTags = $systemTags->merge($userTags);

        return TagResource::collection($allTags);
    }

    /**
     * Create a new user tag.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $slug = Str::slug($validated['name']);

        // Check if tag already exists for user
        $existingTag = $request->user()->tags()
            ->where('slug', $slug)
            ->first();

        if ($existingTag) {
            return response()->json([
                'message' => 'A tag with this name already exists',
            ], 422);
        }

        $tag = $request->user()->tags()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'color' => $validated['color'] ?? $this->generateRandomColor(),
            'is_system' => false,
        ]);

        return response()->json([
            'message' => 'Tag created successfully',
            'data' => new TagResource($tag),
        ], 201);
    }

    /**
     * Update a user tag.
     */
    public function update(Request $request, Tag $tag): JsonResponse
    {
        // Can only update own non-system tags
        if ($tag->user_id !== $request->user()->id || $tag->is_system) {
            abort(403, 'Cannot modify this tag');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);

            // Check for duplicate
            $existingTag = $request->user()->tags()
                ->where('slug', $validated['slug'])
                ->where('id', '!=', $tag->id)
                ->first();

            if ($existingTag) {
                return response()->json([
                    'message' => 'A tag with this name already exists',
                ], 422);
            }
        }

        $tag->update($validated);

        return response()->json([
            'message' => 'Tag updated successfully',
            'data' => new TagResource($tag),
        ]);
    }

    /**
     * Delete a user tag.
     */
    public function destroy(Request $request, Tag $tag): JsonResponse
    {
        // Can only delete own non-system tags
        if ($tag->user_id !== $request->user()->id || $tag->is_system) {
            abort(403, 'Cannot delete this tag');
        }

        $tag->delete();

        return response()->json([
            'message' => 'Tag deleted successfully',
        ]);
    }

    /**
     * Generate a random hex color.
     */
    private function generateRandomColor(): string
    {
        $colors = [
            '#EF4444', '#F97316', '#F59E0B', '#EAB308', '#84CC16',
            '#22C55E', '#10B981', '#14B8A6', '#06B6D4', '#0EA5E9',
            '#3B82F6', '#6366F1', '#8B5CF6', '#A855F7', '#D946EF',
            '#EC4899', '#F43F5E',
        ];

        return $colors[array_rand($colors)];
    }
}
