<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LiabilityRequest;
use App\Models\Liability;
use App\Services\FinanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiabilityController extends Controller
{
    public function __construct(private readonly FinanceService $financeService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $rows = Liability::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('due_within_days'), function ($query) use ($request): void {
                $days = max(0, $request->integer('due_within_days', 7));
                $query->whereBetween('next_due_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
            })
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        $rows->getCollection()->transform(function (Liability $liability): Liability {
            $meta = $this->financeService->liabilityMeta($liability);
            $liability->setAttribute('months_left', $meta['months_left']);
            $liability->setAttribute('next_payment_amount', $meta['next_payment_amount']);

            return $liability;
        });

        return response()->json($rows);
    }

    public function show(int $id): JsonResponse
    {
        $liability = Liability::query()->findOrFail($id);
        $meta = $this->financeService->liabilityMeta($liability);

        $liability->setAttribute('months_left', $meta['months_left']);
        $liability->setAttribute('next_payment_amount', $meta['next_payment_amount']);

        return response()->json($liability);
    }

    public function dueSoon(Request $request): JsonResponse
    {
        $days = max(1, $request->integer('days', 7));

        $rows = Liability::query()
            ->where('status', 'active')
            ->whereNotNull('next_due_date')
            ->whereBetween('next_due_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->orderBy('next_due_date')
            ->get();

        return response()->json([
            'days' => $days,
            'rows' => $rows,
        ]);
    }

    public function store(LiabilityRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $liability = Liability::query()->create([
            'name' => $payload['name'],
            'principal_amount' => $payload['principal_amount'],
            'outstanding' => $payload['outstanding'] ?? $payload['principal_amount'],
            'interest_rate' => $payload['interest_rate'] ?? 0,
            'monthly_payment' => $payload['monthly_payment'],
            'start_date' => $payload['start_date'],
            'end_date' => $payload['end_date'] ?? null,
            'next_due_date' => $payload['next_due_date'] ?? Carbon::parse($payload['start_date'])->toDateString(),
            'status' => $payload['status'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'Liability created successfully.',
            'liability' => $liability,
        ], 201);
    }

    public function update(LiabilityRequest $request, int $id): JsonResponse
    {
        $liability = Liability::query()->findOrFail($id);
        $payload = $request->validated();

        $liability->update($payload);

        return response()->json([
            'message' => 'Liability updated successfully.',
            'liability' => $liability->fresh(),
        ]);
    }

    public function processPayment(LiabilityRequest $request, int $id): JsonResponse
    {
        $payload = $request->validated();

        $liability = Liability::query()->findOrFail($id);
        $updated = $this->financeService->processLiabilityPayment(
            $liability,
            isset($payload['amount']) ? (float) $payload['amount'] : null,
        );

        return response()->json([
            'message' => 'Liability payment processed successfully.',
            'liability' => $updated,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $liability = Liability::query()->findOrFail($id);
        $liability->delete();

        return response()->json(['message' => 'Liability deleted successfully.']);
    }
}
