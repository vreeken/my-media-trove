<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LocationController extends Controller
{
    /**
     * Get all locations for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $locations = $request->user()
            ->locations()
            ->withCount('userMediaItems')
            ->orderBy('name')
            ->get();

        return LocationResource::collection($locations);
    }

    /**
     * Create a new location.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $location = $request->user()->locations()->create($validated);

        return response()->json([
            'message' => 'Location created successfully',
            'data' => new LocationResource($location),
        ], 201);
    }

    /**
     * Update a location.
     */
    public function update(Request $request, Location $location): JsonResponse
    {
        // Ensure user owns this location
        if ($location->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $location->update($validated);

        return response()->json([
            'message' => 'Location updated successfully',
            'data' => new LocationResource($location),
        ]);
    }

    /**
     * Delete a location.
     */
    public function destroy(Request $request, Location $location): JsonResponse
    {
        // Ensure user owns this location
        if ($location->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $location->delete();

        return response()->json([
            'message' => 'Location deleted successfully',
        ]);
    }
}
