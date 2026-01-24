<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Google OAuth routes
Route::prefix('auth/google')->group(function () {
    Route::get('redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
    Route::get('url', [GoogleAuthController::class, 'getAuthUrl']);
});

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
