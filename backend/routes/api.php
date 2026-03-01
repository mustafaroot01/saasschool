<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\AuthController;

// Public routes - no auth required
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::middleware('auth:api')->group(function () {
    Route::get('dashboard/stats', [\App\Http\Controllers\Api\DashboardController::class, 'stats']);
    // School Actions
    Route::post('/schools/{id}/reset-password', [SchoolController::class, 'resetPassword']);
    Route::post('/schools/{id}/impersonate', [SchoolController::class, 'impersonate']);
    Route::get('/schools/{id}/storage', [SchoolController::class, 'calculateStorageUsage']);
    
    // Backups
    Route::get('/schools/{id}/backups', [SchoolController::class, 'listBackups']);
    Route::post('/schools/{id}/backups', [SchoolController::class, 'createBackup']);
    Route::get('/schools/{id}/backups/{filename}/download', [SchoolController::class, 'downloadBackup']);

    
    Route::apiResource('schools', SchoolController::class);
    Route::apiResource('plans', PlanController::class);
    Route::apiResource('subscriptions', SubscriptionController::class);
    Route::apiResource('announcements', \App\Http\Controllers\Api\AnnouncementController::class);

    // Support Tickets
    Route::get('/support-tickets', [\App\Http\Controllers\Api\SupportTicketController::class, 'index']);
    Route::get('/support-tickets/{id}', [\App\Http\Controllers\Api\SupportTicketController::class, 'show']);
    Route::post('/support-tickets/{id}/reply', [\App\Http\Controllers\Api\SupportTicketController::class, 'reply']);
    Route::put('/support-tickets/{id}/status', [\App\Http\Controllers\Api\SupportTicketController::class, 'updateStatus']);

    Route::get('/audit-logs', [\App\Http\Controllers\Api\AuditLogController::class, 'index']);
    Route::get('/error-logs', [\App\Http\Controllers\Api\ErrorLogController::class, 'index']);
    Route::post('subscriptions/{id}/renew', [SubscriptionController::class, 'renew']);
    Route::get('/schools/{id}/subscription-history', [SubscriptionController::class, 'history']);
    Route::get('/all-subscriptions-history', [SubscriptionController::class, 'allHistory']);
    Route::delete('/all-subscriptions-history', [SubscriptionController::class, 'resetHistory']);
    Route::put('/subscriptions/{id}/toggle-payment', [SubscriptionController::class, 'togglePaymentStatus']);
    Route::get('/all-backups', [SchoolController::class, 'allBackups']);
    Route::delete('/all-backups', [SchoolController::class, 'deleteAllBackups']);
    Route::get('/settings', [\App\Http\Controllers\Api\SettingController::class, 'index']);
    Route::put('/settings', [\App\Http\Controllers\Api\SettingController::class, 'update']);
});
