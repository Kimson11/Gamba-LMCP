<?php

use App\Http\Controllers\Web\Admin\AnalyticsController;
use App\Http\Controllers\Web\Admin\ApprovalHistoryController;
use App\Http\Controllers\Web\Admin\AuthController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\MemberAssignmentController;
use App\Http\Controllers\Web\Admin\NotificationController;
use App\Http\Controllers\Web\Admin\SecurityController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('landing');

Route::redirect('/login', '/admin/login')->name('login');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');

    Route::middleware(['auth', 'admin.role'])->group(function (): void {
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

        Route::get('/security', [SecurityController::class, 'show'])->name('security.show');
        Route::post('/security/session', [SecurityController::class, 'store'])->name('security.store');
        Route::delete('/security/session', [SecurityController::class, 'destroy'])->name('security.destroy');

        Route::middleware('web.privileged_session')->group(function (): void {
            Route::get('/', DashboardController::class)->name('dashboard');
            Route::get('/analytics', AnalyticsController::class)->name('analytics.index');
            Route::get('/assignments', [MemberAssignmentController::class, 'index'])->name('assignments.index');
            Route::get('/assignments/history', ApprovalHistoryController::class)->name('assignments.history');
            Route::get('/assignments/history/{memberAssignment}', [ApprovalHistoryController::class, 'show'])->name('assignments.history.show');
            Route::post('/assignments/{memberAssignment}/approve', [MemberAssignmentController::class, 'approve'])->name('assignments.approve');
            Route::post('/assignments/{memberAssignment}/reject', [MemberAssignmentController::class, 'reject'])->name('assignments.reject');
            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        });
    });
});
