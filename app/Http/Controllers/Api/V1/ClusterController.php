<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateClusterRequest;
use App\Models\Cluster;
use App\Models\Cooperative;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ClusterController extends Controller
{
    /**
     * Return clusters for one cooperative.
     */
    public function indexByCooperative(Cooperative $cooperative): JsonResponse
    {
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
}
