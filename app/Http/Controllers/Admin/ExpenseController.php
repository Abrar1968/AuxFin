<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = Expense::query()
            ->with('creator:id,name,email')
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->query('category')))
            ->when($request->filled('is_recurring'), fn ($query) => $query->where('is_recurring', filter_var($request->query('is_recurring'), FILTER_VALIDATE_BOOLEAN)))
            ->when($request->filled('month'), function ($query) use ($request): void {
                $month = Carbon::parse((string) $request->query('month'));
                $query->whereYear('expense_date', $month->year)
                    ->whereMonth('expense_date', $month->month);
            })
            ->latest('expense_date')
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($rows);
    }

    public function show(int $id): JsonResponse
    {
        $expense = Expense::query()
            ->with('creator:id,name,email')
            ->findOrFail($id);

        return response()->json($expense);
    }

    public function summary(Request $request): JsonResponse
    {
        $monthDate = Carbon::parse((string) ($request->query('month') ?? now()->toDateString()))->startOfMonth();

        $monthlyTotal = (float) Expense::query()
            ->whereYear('expense_date', $monthDate->year)
            ->whereMonth('expense_date', $monthDate->month)
            ->sum('amount');

        $recurringTotal = (float) Expense::query()
            ->where('is_recurring', true)
            ->sum('amount');

        $categoryTotals = Expense::query()
            ->whereYear('expense_date', $monthDate->year)
            ->whereMonth('expense_date', $monthDate->month)
            ->selectRaw('category, COALESCE(SUM(amount), 0) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'month' => $monthDate->toDateString(),
            'monthly_total' => round($monthlyTotal, 2),
            'recurring_total' => round($recurringTotal, 2),
            'category_totals' => $categoryTotals,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence' => ['nullable', 'in:monthly,quarterly,yearly'],
            'next_due_date' => ['nullable', 'date'],
        ]);

        $isRecurring = (bool) ($payload['is_recurring'] ?? false);
        if ($isRecurring && (! isset($payload['recurrence']) || ! isset($payload['next_due_date']))) {
            return response()->json([
                'message' => 'recurrence and next_due_date are required for recurring expenses.',
            ], 422);
        }

        $expense = Expense::query()->create([
            'category' => $payload['category'],
            'description' => $payload['description'],
            'amount' => $payload['amount'],
            'expense_date' => $payload['expense_date'],
            'is_recurring' => $isRecurring,
            'recurrence' => $isRecurring ? ($payload['recurrence'] ?? null) : null,
            'next_due_date' => $isRecurring ? ($payload['next_due_date'] ?? null) : null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Expense created successfully.',
            'expense' => $expense->load('creator:id,name,email'),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $expense = Expense::query()->findOrFail($id);

        $payload = $request->validate([
            'category' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'string'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'expense_date' => ['sometimes', 'date'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence' => ['nullable', 'in:monthly,quarterly,yearly'],
            'next_due_date' => ['nullable', 'date'],
        ]);

        $isRecurring = array_key_exists('is_recurring', $payload)
            ? (bool) $payload['is_recurring']
            : (bool) $expense->is_recurring;

        if ($isRecurring && (! array_key_exists('recurrence', $payload) && ! $expense->recurrence)) {
            return response()->json([
                'message' => 'recurrence is required for recurring expenses.',
            ], 422);
        }

        if (! $isRecurring) {
            $payload['recurrence'] = null;
            $payload['next_due_date'] = null;
        }

        $payload['is_recurring'] = $isRecurring;

        $expense->update($payload);

        return response()->json([
            'message' => 'Expense updated successfully.',
            'expense' => $expense->fresh()->load('creator:id,name,email'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $expense = Expense::query()->findOrFail($id);
        $expense->delete();

        return response()->json(['message' => 'Expense deleted successfully.']);
    }
}
