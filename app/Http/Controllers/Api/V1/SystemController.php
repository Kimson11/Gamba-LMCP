<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Minimal system endpoints used to validate API contract primitives.
 */
class SystemController extends Controller
{
    /**
     * Lightweight health-style endpoint that returns the standard API envelope.
     */
    public function ping(): JsonResponse
    {
        return ApiResponse::success([
            'status' => 'ok',
            'service' => config('app.name'),
            'version' => 'v1',
        ]);
    }

    /**
     * Echo endpoint for idempotency middleware verification in tests and early integration.
     */
    public function idempotentEcho(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255'],
        ]);

        return ApiResponse::success([
            'echo' => $validated['value'],
            'accepted_at' => now()->toIso8601String(),
        ], 201);
    }
}
