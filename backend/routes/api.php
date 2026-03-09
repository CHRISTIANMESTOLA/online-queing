<?php

use App\Http\Controllers\Api\AdminStaffController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\QueueController;
use App\Http\Controllers\Api\StaffQueueController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->name('api/auth/login');

Route::get('/offices/public', [OfficeController::class, 'publicIndex'])->name('api/offices/public');
Route::post('/queues/generate', [QueueController::class, 'generate'])->name('api/queues/generate');
Route::get('/queues/monitor', [QueueController::class, 'monitor'])->name('api/queues/monitor');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::middleware('role:admin')->prefix('admin')->group(function (): void {
        Route::apiResource('offices', OfficeController::class);
        Route::post('offices/{office}/staff', [OfficeController::class, 'assignStaff']);
        Route::delete('offices/{office}/staff/{staff}', [OfficeController::class, 'unassignStaff']);

        Route::get('staff', [AdminStaffController::class, 'index']);
        Route::post('staff', [AdminStaffController::class, 'store']);
        Route::put('staff/{staff}', [AdminStaffController::class, 'update']);
        Route::delete('staff/{staff}', [AdminStaffController::class, 'destroy']);
    });

    Route::middleware('role:admin,staff')->prefix('staff')->group(function (): void {
        Route::get('offices', [StaffQueueController::class, 'offices']);
        Route::get('offices/{office}/queue', [StaffQueueController::class, 'dashboard']);
        Route::post('offices/{office}/call-next', [StaffQueueController::class, 'callNext']);

        Route::patch('queues/{queueTicket}/serving', [StaffQueueController::class, 'markServing']);
        Route::patch('queues/{queueTicket}/done', [StaffQueueController::class, 'markDone']);
        Route::patch('queues/{queueTicket}/skip', [StaffQueueController::class, 'skip']);
    });
});
