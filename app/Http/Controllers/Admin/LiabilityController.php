<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'principal_amount' => ['required', 'numeric', 'min:0.01'],
            'outstanding' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'monthly_payment' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'next_due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:active,completed,defaulted'],
        ]);

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

    public function update(Request $request, int $id): JsonResponse
    {
        $liability = Liability::query()->findOrFail($id);

        $payload = $request->validate([
            'name' => ['sometimes', 'string', 'max:200'],
            'principal_amount' => ['sometimes', 'numeric', 'min:0.01'],
            'outstanding' => ['sometimes', 'numeric', 'min:0'],
            'interest_rate' => ['sometimes', 'numeric', 'min:0'],
            'monthly_payment' => ['sometimes', 'numeric', 'min:0.01'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date'],
            'next_due_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:active,completed,defaulted'],
        ]);

        $liability->update($payload);

        return response()->json([
            'message' => 'Liability updated successfully.',
            'liability' => $liability->fresh(),
        ]);
    }

    public function processPayment(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
        ]);

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
