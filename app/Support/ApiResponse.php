<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Shared API response formatter for contract-compliant envelopes.
 */
class ApiResponse
{
    /**
     * Build a successful API response with data and standard meta context.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(array $data = [], int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => [
                'request_id' => request()?->attributes->get('request_id'),
                'timestamp' => now()->toIso8601String(),
                ...$meta,
            ],
        ], $status);
    }

    /**
        * Build an API error response with an explicit machine-readable code.
        *
     * @param  array<string, array<int, string>>  $errors
     */
    public static function error(string $message, string $code, int $status, array $errors = []): JsonResponse
    {
        $payload = [
            'message' => $message,
            'code' => $code,
            'meta' => [
                'request_id' => request()?->attributes->get('request_id'),
                'timestamp' => now()->toIso8601String(),
            ],
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
