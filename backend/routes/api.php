<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BarcodeController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MediaItemController;
use App\Http\Controllers\Api\MediaSearchController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('password', [AuthController::class, 'updatePassword']);
    });

    // Media Items
    Route::prefix('media')->group(function () {
        Route::get('types', [MediaItemController::class, 'types']);
        Route::get('stats', [MediaItemController::class, 'stats']);
        Route::get('digital-platforms', [MediaItemController::class, 'digitalPlatforms']);
    });
    Route::apiResource('media', MediaItemController::class);

    // Media Search
    Route::prefix('search')->group(function () {
        Route::get('all', [MediaSearchController::class, 'searchAll']);
        Route::get('movies', [MediaSearchController::class, 'searchMovies']);
        Route::get('movies/{imdbId}', [MediaSearchController::class, 'getMovieDetails']);
        Route::get('albums', [MediaSearchController::class, 'searchAlbums']);
        Route::get('albums/{mbid}', [MediaSearchController::class, 'getAlbumDetails']);
        Route::get('songs', [MediaSearchController::class, 'searchSongs']);
        Route::get('streaming/{imdbId}', [MediaSearchController::class, 'getStreamingAvailability']);
        Route::post('streaming/{imdbId}/refresh', [MediaSearchController::class, 'refreshStreamingAvailability']);
    });

    // Tags
    Route::apiResource('tags', TagController::class)->except(['show']);

    // Locations
    Route::apiResource('locations', LocationController::class)->except(['show']);

    // Wishlist
    Route::post('wishlist/{wishlistItem}/to-collection', [WishlistController::class, 'moveToCollection']);
    Route::apiResource('wishlist', WishlistController::class)
        ->except(['show'])
        ->parameters(['wishlist' => 'wishlistItem']);

    // Barcodes
    Route::prefix('barcodes')->group(function () {
        Route::get('{barcode}', [BarcodeController::class, 'lookup']);
        Route::post('/', [BarcodeController::class, 'store']);
        Route::post('report-incorrect', [BarcodeController::class, 'reportIncorrect']);
        Route::post('check-box-set', [BarcodeController::class, 'checkBoxSet']);
    });
});
