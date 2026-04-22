<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ConfigurationChangeEvent;
use App\Models\ConfigurationValue;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Provides a scoped system configuration baseline with append-only change events.
 */
class ConfigurationController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * List configuration values with optional filtering by key and scope.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['nullable', 'string', 'max:120'],
            'scope_type' => ['nullable', 'string', 'max:50'],
            'scope_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $query = ConfigurationValue::query()->orderBy('key');

        if (isset($validated['key'])) {
            $query->where('key', $validated['key']);
        }

        if (isset($validated['scope_type'])) {
            $query->where('scope_type', $validated['scope_type']);
        }

        if (isset($validated['scope_id'])) {
            $query->where('scope_id', $validated['scope_id']);
        }

        return ApiResponse::success([
            'items' => $query->get([
                'id',
                'key',
                'scope_type',
                'scope_id',
                'value',
                'updated_by_user_id',
                'updated_at',
            ]),
        ]);
    }

    /**
     * Upsert a configuration value and append a configuration change event.
     */
    public function upsert(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'scope_type' => ['nullable', 'string', 'max:50'],
            'scope_id' => ['nullable', 'integer', 'min:0'],
            'value' => ['required', 'array'],
        ]);

        $user = $request->user();
        $scopeType = $validated['scope_type'] ?? 'global';
        $scopeId = $validated['scope_id'] ?? 0;

        $configurationValue = DB::transaction(function () use ($user, $key, $scopeType, $scopeId, $validated, $request): ConfigurationValue {
            $configurationValue = ConfigurationValue::query()->firstOrNew([
                'key' => $key,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
            ]);

            $previousValue = $configurationValue->exists ? $configurationValue->value : null;

            $configurationValue->forceFill([
                'value' => $validated['value'],
                'updated_by_user_id' => $user->id,
            ])->save();

            ConfigurationChangeEvent::query()->create([
                'configuration_value_id' => $configurationValue->id,
                'key' => $configurationValue->key,
                'scope_type' => $configurationValue->scope_type,
                'scope_id' => $configurationValue->scope_id,
                'previous_value' => $previousValue,
                'new_value' => $configurationValue->value,
                'actor_id' => $user->id,
                'correlation_id' => $request->attributes->get('request_id'),
                'created_at' => now(),
            ]);

            return $configurationValue;
        });

        $this->auditLogger->record(
            action: 'configuration.value_upserted',
            subject: $configurationValue,
            actor: $user,
            context: [
                'key' => $configurationValue->key,
                'scope_type' => $configurationValue->scope_type,
                'scope_id' => $configurationValue->scope_id,
            ],
        );

        return ApiResponse::success([
            'id' => $configurationValue->id,
            'key' => $configurationValue->key,
            'scope_type' => $configurationValue->scope_type,
            'scope_id' => $configurationValue->scope_id,
            'value' => $configurationValue->value,
            'updated_by_user_id' => $configurationValue->updated_by_user_id,
            'updated_at' => $configurationValue->updated_at?->toIso8601String(),
        ]);
    }
}
