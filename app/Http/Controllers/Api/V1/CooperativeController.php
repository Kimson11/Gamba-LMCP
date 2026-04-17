<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateCooperativeRequest;
use App\Models\Cooperative;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CooperativeController extends Controller
{
    /**
     * Return paginated cooperatives for admin/cooperative operators.
     */
    public function index(): JsonResponse
    {
        $paginator = Cooperative::query()
            ->orderBy('name')
            ->paginate(20);

        return ApiResponse::success([
            'items' => $paginator->getCollection()->map(fn (Cooperative $cooperative): array => [
                'id' => $cooperative->id,
                'name' => $cooperative->name,
                'code' => $cooperative->code,
                'country_code' => $cooperative->country_code,
                'status' => $cooperative->status,
                'created_at' => $cooperative->created_at?->toIso8601String(),
            ])->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Create a cooperative record.
     */
    public function store(CreateCooperativeRequest $request): JsonResponse
    {
        $cooperative = Cooperative::query()->create($request->validated());

        return ApiResponse::success([
            'id' => $cooperative->id,
            'name' => $cooperative->name,
            'code' => $cooperative->code,
            'country_code' => $cooperative->country_code,
            'status' => $cooperative->status,
            'created_at' => $cooperative->created_at?->toIso8601String(),
        ], 201);
    }
}
