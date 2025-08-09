<?php

// File: routes/api.php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\KosController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\Owner\BookingController as OwnerBookingController;
use App\Http\Controllers\Api\Owner\KosController as OwnerKosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no auth required)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Public kos routes
Route::get('kos', [KosController::class, 'index']);
Route::get('kos/{kos}', [KosController::class, 'show']);

// Protected routes (auth required)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::post('profile', [AuthController::class, 'updateProfile']); // CHANGED: PUT -> POST
    });
    
    // Society routes
    Route::middleware('role:society')->group(function () {
        // Bookings
        Route::get('bookings', [BookingController::class, 'index']);
        Route::post('bookings', [BookingController::class, 'store']);
        Route::get('bookings/{booking}', [BookingController::class, 'show']);
        
        // Reviews
        Route::post('kos/{kos}/reviews', [ReviewController::class, 'store']);
        Route::put('reviews/{review}', [ReviewController::class, 'update']);
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);
        
        // Favorites 
        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('favorites', [FavoriteController::class, 'store']);
        Route::delete('favorites/{favorite}', [FavoriteController::class, 'destroy']);
    });
    
    // Owner routes
    Route::middleware('role:owner')->prefix('owner')->group(function () {
        // Kos management
        Route::get('kos', [OwnerKosController::class, 'index']);
        Route::post('kos', [OwnerKosController::class, 'store']);
        Route::get('kos/{kos}', [OwnerKosController::class, 'show']);
        Route::put('kos/{kos}', [OwnerKosController::class, 'update']);
        Route::delete('kos/{kos}', [OwnerKosController::class, 'destroy']);
        
        // Kos sub-resources
        Route::post('kos/{kos}/rooms', [OwnerKosController::class, 'addRooms']);
        Route::post('kos/{kos}/facilities', [OwnerKosController::class, 'addFacilities']);
        Route::post('kos/{kos}/images', [OwnerKosController::class, 'uploadImages']);
        Route::post('kos/{kos}/payment-methods', [OwnerKosController::class, 'addPaymentMethods']);
        
        // Bookings management
        Route::get('bookings', [OwnerBookingController::class, 'index']);
        Route::get('bookings/{booking}', [OwnerBookingController::class, 'show']);
        Route::put('bookings/{booking}/status', [OwnerBookingController::class, 'updateStatus']);
        
        // Review replies
        Route::post('reviews/{review}/reply', [OwnerKosController::class, 'replyToReview']);
        
        // Reports
        Route::get('reports/bookings', [OwnerBookingController::class, 'report']);
    });
});