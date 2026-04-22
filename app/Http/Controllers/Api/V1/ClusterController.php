<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateClusterRequest;
use App\Models\Cluster;
use App\Models\Cooperative;
use App\Support\ApiResponse;
use App\Support\ScopeAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClusterController extends Controller
{
    public function __construct(
        private readonly ScopeAccess $scopeAccess,
    ) {}

    /**
     * Return clusters for one cooperative.
     */
    public function indexByCooperative(Request $request, Cooperative $cooperative): JsonResponse
    {
        if (! $this->scopeAccess->canAccessCooperative($request->user(), $cooperative)) {
            return $this->forbiddenScopeResponse();
        }

        $clusters = Cluster::query()
            ->where('cooperative_id', $cooperative->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Cluster $cluster): array => [
                'id' => $cluster->id,
                'cooperative_id' => $cluster->cooperative_id,
                'name' => $cluster->name,
                'code' => $cluster->code,
                'supervisor_user_id' => $cluster->supervisor_user_id,
                'status' => $cluster->status,
                'created_at' => $cluster->created_at?->toIso8601String(),
            ])
            ->all();

        return ApiResponse::success([
            'cooperative_id' => $cooperative->id,
            'items' => $clusters,
        ]);
    }

    /**
     * Create one cluster under a cooperative.
     */
    public function store(CreateClusterRequest $request): JsonResponse
    {
        if (! $this->scopeAccess->canAccessCooperative($request->user(), (int) $request->validated('cooperative_id'))) {
            return $this->forbiddenScopeResponse();
        }

        $cluster = Cluster::query()->create($request->validated());

        return ApiResponse::success([
            'id' => $cluster->id,
            'cooperative_id' => $cluster->cooperative_id,
            'name' => $cluster->name,
            'code' => $cluster->code,
            'supervisor_user_id' => $cluster->supervisor_user_id,
            'status' => $cluster->status,
            'created_at' => $cluster->created_at?->toIso8601String(),
        ], 201);
    }

    private function forbiddenScopeResponse(): JsonResponse
    {
        return ApiResponse::error(
            message: 'You are not authorized to access this resource within your assigned scope.',
            code: 'permission_denied',
            status: 403,
        );
    }
}
