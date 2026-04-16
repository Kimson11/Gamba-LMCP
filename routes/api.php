<?php

use App\Http\Controllers\Api\V1\SystemController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/system/ping', [SystemController::class, 'ping']);

    Route::middleware('idempotency')->group(function (): void {
        Route::post('/system/idempotent-echo', [SystemController::class, 'idempotentEcho']);
    });
});
