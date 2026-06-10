<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TeamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — TaskFlow API v1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1 via RouteServiceProvider configuration.
| Security: all protected routes require Sanctum authentication middleware.
| Security: rate limiting is applied via throttle middleware.
|
*/

Route::prefix('v1')->group(function () {

    // ─── Public Auth Routes ──────────────────────────────────────────────────
    // Security: stricter rate limit on auth endpoints (5 per minute)
    Route::prefix('auth')->middleware(['throttle:auth'])->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('login',    [AuthController::class, 'login'])->name('api.v1.auth.login');
    });

    // ─── Protected Routes ────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('logout',          [AuthController::class, 'logout'])->name('api.v1.auth.logout');
            Route::get('me',               [AuthController::class, 'me'])->name('api.v1.auth.me');
            Route::post('change-password', [AuthController::class, 'changePassword'])->name('api.v1.auth.change-password');
        });

        // Teams
        Route::apiResource('teams', TeamController::class)->names([
            'index'   => 'api.v1.teams.index',
            'store'   => 'api.v1.teams.store',
            'show'    => 'api.v1.teams.show',
            'update'  => 'api.v1.teams.update',
            'destroy' => 'api.v1.teams.destroy',
        ]);
        Route::post('teams/{team}/members',             [TeamController::class, 'inviteMember'])->name('api.v1.teams.members.invite');
        Route::delete('teams/{team}/members/{userId}',  [TeamController::class, 'removeMember'])->name('api.v1.teams.members.remove');

        // Tasks (nested under projects)
        Route::prefix('projects/{project}')->group(function () {
            Route::get('tasks',  [TaskController::class, 'index'])->name('api.v1.projects.tasks.index');
            Route::post('tasks', [TaskController::class, 'store'])->name('api.v1.projects.tasks.store');
        });

        // Task operations by ID
        Route::get('tasks/{task}',              [TaskController::class, 'show'])->name('api.v1.tasks.show');
        Route::put('tasks/{task}',              [TaskController::class, 'update'])->name('api.v1.tasks.update');
        Route::patch('tasks/{task}/status',     [TaskController::class, 'updateStatus'])->name('api.v1.tasks.status');
        Route::delete('tasks/{task}',           [TaskController::class, 'destroy'])->name('api.v1.tasks.destroy');

        // Attachments
        Route::get('attachments/{attachment}/download', [\App\Http\Controllers\Api\V1\AttachmentController::class, 'download'])
            ->name('api.v1.attachments.download');
    });
});
