<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\QueueController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\RotationController;
use App\Http\Controllers\Api\TimelineController;
use App\Http\Controllers\Api\NotificationsController;
use App\Http\Controllers\Api\TimelineCommentController;

// Route de santé
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'L\'API RMS est en cours d\'exécution.',
    ]);
});

// Auth
Route::prefix('auth')->group(function () {
    Route::get('/ping', [AuthController::class, 'ping']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/request-otp', [AuthController::class, 'requestOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
});

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {

    // Wallet
    Route::prefix('wallet')->group(function () {
        Route::post('/adhesion', [WalletController::class, 'adhesion']);
        Route::post('/remboursement', [WalletController::class, 'remboursement']);
        Route::get('/repayment', [WalletController::class, 'currentRepayment']);
        Route::get('/', [WalletController::class, 'index']);

    });

    // Queue
    Route::prefix('queue')->group(function () {
        Route::get('/position', [QueueController::class, 'position']);
    });

    // Admin
    // Route::prefix('admin')->group(function () {
    //     Route::post('/rotations/run-once', [RotationController::class, 'runOnce']);
    // });

    // Timeline posts (feed, création, likes)
    Route::prefix('timeline')->group(function () {
        Route::get('/posts', [TimelineController::class, 'index']);
        Route::post('/posts', [TimelineController::class, 'store']);
        Route::post('/posts/{post}/like', [TimelineController::class, 'toggleLike']);
    });

    // Commentaires (table comments)
    Route::get('/timeline/posts/{post}/comments', [TimelineCommentController::class, 'index']);
    Route::post('/timeline/posts/{post}/comments', [TimelineCommentController::class, 'store']);
    Route::patch('/timeline/posts/{post}/comments/{comment}', [TimelineCommentController::class, 'update']);

    // Notifications
    Route::get('/notifications', [NotificationsController::class, 'index']);
    Route::post('/notifications/mark-as-read', [NotificationsController::class, 'markAsRead']);

    // Rotations
    Route::get('/rotations', [RotationController::class, 'index']);

});

// Routes admin (middleware admin)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::post('/rotations/run-once', [RotationController::class, 'runOnce']);
    Route::get('/stats', [AdminController::class, 'stats']);
    Route::get('/users-count', [AdminController::class, 'usersCount']);
    Route::get('/queue-stats', [AdminController::class, 'queueStats']);
});

