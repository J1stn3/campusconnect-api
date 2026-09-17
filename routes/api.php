<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CampusConnect API Routes — v1
|--------------------------------------------------------------------------
|
| All routes here are prefixed with /api automatically by Laravel.
| We add /v1 for versioning.
|
*/

// ─── Health Check (Public) ───────────────────────────────────────────────────
Route::get('/v1/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'CampusConnect API is running.',
        'version' => '1.0.0',
        'timestamp' => now()->toDateTimeString(),
    ], 200);
});

// ─── Authentication Routes (Public) ──────────────────────────────────────────
Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\V1\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);
});

// ─── Protected Routes (Requires Authentication) ───────────────────────────────
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth — logout & profile
    Route::post('/auth/logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
    Route::get('/auth/me', [\App\Http\Controllers\Api\V1\AuthController::class, 'me']);

    // Announcements
    Route::apiResource('announcements', \App\Http\Controllers\Api\V1\AnnouncementController::class);

    // Events
    Route::apiResource('events', \App\Http\Controllers\Api\V1\EventController::class);
    Route::post('/events/{event}/rsvp', [\App\Http\Controllers\Api\V1\EventController::class, 'rsvp']);

    // Schedules
    Route::get('/schedules', [\App\Http\Controllers\Api\V1\ScheduleController::class, 'index']);

    // AI Chatbot
    Route::post('/chatbot', [\App\Http\Controllers\Api\V1\ChatbotController::class, 'chat']);

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Api\V1\NotificationController::class, 'index']);
    Route::get('/notifications/{id}', [\App\Http\Controllers\Api\V1\NotificationController::class, 'show']);
    Route::patch('/notifications/{id}/read', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [\App\Http\Controllers\Api\V1\NotificationController::class, 'destroy']);
});
