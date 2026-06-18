<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssetRequest;
use App\Models\Asset;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(private readonly FinanceService $financeService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $rows = Asset::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->query('category')))
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($rows);
    }

    public function show(int $id): JsonResponse
    {
        $asset = Asset::query()->findOrFail($id);

        return response()->json($asset);
    }

    public function store(AssetRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $normalized = $this->financeService->normalizeAssetPayload($payload);

        $asset = Asset::query()->create([
            ...$normalized,
            'status' => $payload['status'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'Asset created successfully.',
            'asset' => $asset,
        ], 201);
    }

    public function update(AssetRequest $request, int $id): JsonResponse
    {
        $asset = Asset::query()->findOrFail($id);
        $payload = $request->validated();

        $normalized = $this->financeService->normalizeAssetPayload($payload, $asset);
        $asset->update($normalized);

        return response()->json([
            'message' => 'Asset updated successfully.',
            'asset' => $asset->fresh(),
        ]);
    }

    public function depreciate(int $id): JsonResponse
    {
        $asset = Asset::query()->findOrFail($id);
        $updated = $this->financeService->depreciateAsset($asset);

        return response()->json([
            'message' => 'Asset depreciation applied successfully.',
            'asset' => $updated,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $asset = Asset::query()->findOrFail($id);
        $asset->delete();

        return response()->json(['message' => 'Asset deleted successfully.']);
    }
}
