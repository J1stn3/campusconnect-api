<?php

use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatbotController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsureIsFaculty;
use Illuminate\Support\Facades\Route;

Route::get('/v1/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'CampusConnect API is running.',
        'version' => '1.0.0',
        'timestamp' => now()->toDateTimeString(),
    ]);
});

Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::prefix('v1/auth')->middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);

    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::post('/events/{id}/rsvp', [EventController::class, 'rsvp']);

    Route::get('/schedules', [ScheduleController::class, 'index']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{id}', [NotificationController::class, 'show']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    Route::post('/chatbot', [ChatbotController::class, 'chat']);
    Route::get('/chatbot/history', [ChatbotController::class, 'history']);

    Route::middleware(EnsureIsAdmin::class)->group(function () {
        Route::post('/announcements', [AnnouncementController::class, 'store']);
        Route::put('/announcements/{id}', [AnnouncementController::class, 'update']);
        Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);
    });

    Route::middleware(EnsureIsFaculty::class)->group(function () {
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{id}', [EventController::class, 'update']);
        Route::delete('/events/{id}', [EventController::class, 'destroy']);
    });
});
