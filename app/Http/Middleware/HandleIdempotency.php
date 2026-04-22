<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces idempotent behavior for API write requests.
 *
 * A repeated request with the same key and payload replays the original JSON response,
 * while a repeated key with a different payload returns a conflict.
 */
class HandleIdempotency
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodCacheable() && $request->is('api/*')) {
            $idempotencyKey = $request->header('Idempotency-Key');

            if (! is_string($idempotencyKey) || $idempotencyKey === '') {
                return ApiResponse::error(
                    message: __('api.errors.idempotency_key_required'),
                    code: 'idempotency_key_required',
                    status: 400,
                );
            }

            $normalizedPayload = Arr::sortRecursive($request->all());
            $encodedPayload = json_encode($normalizedPayload);
            $requestHash = hash('sha256', $encodedPayload === false ? '' : $encodedPayload);
            $method = $request->method();
            $path = '/'.$request->path();

            // Idempotency is scoped to key + HTTP method + endpoint path.
            $existing = IdempotencyKey::query()
                ->where('idempotency_key', $idempotencyKey)
                ->where('method', $method)
                ->where('path', $path)
                ->first();

            if ($existing !== null) {
                // A reused key must carry the exact same normalized payload hash.
                if ($existing->request_hash !== $requestHash) {
                    return ApiResponse::error(
                        message: __('api.errors.idempotency_payload_mismatch'),
                        code: 'duplicate_with_payload_mismatch',
                        status: 409,
                    );
                }

                $replayedResponse = ApiResponse::success(
                    data: $existing->response_body,
                    status: $existing->response_status,
                );

                $replayedResponse->headers->set('X-Idempotency-Replayed', 'true');

                return $replayedResponse;
            }

            $response = $next($request);

            // Persist only successful/recoverable JSON responses for safe replay.
            if ($response instanceof JsonResponse && $response->getStatusCode() < 500) {
                IdempotencyKey::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'method' => $method,
                    'path' => $path,
                    'request_hash' => $requestHash,
                    'response_status' => $response->getStatusCode(),
                    'response_body' => data_get($response->getData(true), 'data', []),
                ]);
            }

            return $response;
        }

        return $next($request);
    }
}
