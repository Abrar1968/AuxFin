<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpensePayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = Expense::query()
            ->with('creator:id,name,email')
            ->withSum('payments as paid_amount', 'amount')
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->query('category')))
            ->when($request->filled('is_recurring'), fn ($query) => $query->where('is_recurring', filter_var($request->query('is_recurring'), FILTER_VALIDATE_BOOLEAN)))
            ->when($request->filled('accounting_mode'), fn ($query) => $query->where('accounting_mode', $request->query('accounting_mode')))
            ->when($request->filled('month'), function ($query) use ($request): void {
                $month = Carbon::parse((string) $request->query('month'));
                $query->whereYear('expense_date', $month->year)
                    ->whereMonth('expense_date', $month->month);
            })
            ->latest('expense_date')
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        $rows->getCollection()->transform(function (Expense $expense): Expense {
            $paymentMeta = $this->expensePaymentMeta($expense, (float) ($expense->paid_amount ?? 0));

            $expense->setAttribute('paid_amount', $paymentMeta['paid_amount']);
            $expense->setAttribute('outstanding_amount', $paymentMeta['outstanding_amount']);
            $expense->setAttribute('payment_status', $paymentMeta['payment_status']);

            return $expense;
        });

        return response()->json($rows);
    }

    public function show(int $id): JsonResponse
    {
        $expense = Expense::query()
            ->with('payments.recorder:id,name,email')
            ->with('creator:id,name,email')
            ->findOrFail($id);

        $paidAmount = (float) $expense->payments()->sum('amount');
        $paymentMeta = $this->expensePaymentMeta($expense, $paidAmount);

        $expense->setAttribute('paid_amount', $paymentMeta['paid_amount']);
        $expense->setAttribute('outstanding_amount', $paymentMeta['outstanding_amount']);
        $expense->setAttribute('payment_status', $paymentMeta['payment_status']);

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

        $outstandingPayables = Expense::query()
            ->whereIn('accounting_mode', ['cash', 'payable'])
            ->withSum('payments as paid_amount', 'amount')
            ->get()
            ->sum(static function (Expense $expense): float {
                $paid = (float) ($expense->paid_amount ?? 0);
                return round(max(0, (float) $expense->amount - $paid), 2);
            });

        return response()->json([
            'month' => $monthDate->toDateString(),
            'monthly_total' => round($monthlyTotal, 2),
            'recurring_total' => round($recurringTotal, 2),
            'outstanding_payables' => round((float) $outstandingPayables, 2),
            'category_totals' => $categoryTotals,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'accounting_mode' => ['nullable', 'in:cash,payable,prepaid'],
            'expense_date' => ['required', 'date'],
            'payable_due_date' => ['nullable', 'date'],
            'prepaid_start_date' => ['nullable', 'date'],
            'prepaid_months' => ['nullable', 'integer', 'min:1'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence' => ['nullable', 'in:monthly,quarterly,yearly'],
            'next_due_date' => ['nullable', 'date'],
        ]);

        $mode = $payload['accounting_mode'] ?? 'cash';
        if ($mode === 'payable' && ! isset($payload['payable_due_date'])) {
            return response()->json([
                'message' => 'payable_due_date is required for payable expenses.',
            ], 422);
        }

        if ($mode === 'prepaid' && (! isset($payload['prepaid_start_date']) || ! isset($payload['prepaid_months']))) {
            return response()->json([
                'message' => 'prepaid_start_date and prepaid_months are required for prepaid expenses.',
            ], 422);
        }

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
            'accounting_mode' => $mode,
            'expense_date' => $payload['expense_date'],
            'payable_due_date' => $payload['payable_due_date'] ?? null,
            'prepaid_start_date' => $mode === 'prepaid' ? ($payload['prepaid_start_date'] ?? null) : null,
            'prepaid_months' => $mode === 'prepaid' ? ($payload['prepaid_months'] ?? null) : null,
            'is_recurring' => $isRecurring,
            'recurrence' => $isRecurring ? ($payload['recurrence'] ?? null) : null,
            'next_due_date' => $isRecurring ? ($payload['next_due_date'] ?? null) : null,
            'created_by' => $request->user()->id,
        ]);

        if (in_array($mode, ['cash', 'prepaid'], true)) {
            ExpensePayment::query()->create([
                'expense_id' => $expense->id,
                'recorded_by' => $request->user()->id,
                'payment_date' => $expense->expense_date?->toDateString() ?? now()->toDateString(),
                'amount' => $expense->amount,
                'payment_method' => $mode === 'prepaid' ? 'prepaid' : 'cash',
                'reference_number' => null,
                'notes' => $mode === 'prepaid'
                    ? 'Auto-payment for prepaid expense creation'
                    : 'Auto-payment for cash expense creation',
            ]);
        }

        $fresh = Expense::query()
            ->with('creator:id,name,email')
            ->withSum('payments as paid_amount', 'amount')
            ->findOrFail($expense->id);

        $paymentMeta = $this->expensePaymentMeta($fresh, (float) ($fresh->paid_amount ?? 0));
        $fresh->setAttribute('paid_amount', $paymentMeta['paid_amount']);
        $fresh->setAttribute('outstanding_amount', $paymentMeta['outstanding_amount']);
        $fresh->setAttribute('payment_status', $paymentMeta['payment_status']);

        return response()->json([
            'message' => 'Expense created successfully.',
            'expense' => $fresh,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $expense = Expense::query()->findOrFail($id);

        $payload = $request->validate([
            'category' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'string'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'accounting_mode' => ['sometimes', 'in:cash,payable,prepaid'],
            'expense_date' => ['sometimes', 'date'],
            'payable_due_date' => ['nullable', 'date'],
            'prepaid_start_date' => ['nullable', 'date'],
            'prepaid_months' => ['nullable', 'integer', 'min:1'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence' => ['nullable', 'in:monthly,quarterly,yearly'],
            'next_due_date' => ['nullable', 'date'],
        ]);

        $mode = $payload['accounting_mode'] ?? $expense->accounting_mode ?? 'cash';
        if ($mode === 'payable' && ! array_key_exists('payable_due_date', $payload) && ! $expense->payable_due_date) {
            return response()->json([
                'message' => 'payable_due_date is required for payable expenses.',
            ], 422);
        }

        if ($mode === 'prepaid') {
            $prepaidStart = $payload['prepaid_start_date'] ?? $expense->prepaid_start_date;
            $prepaidMonths = $payload['prepaid_months'] ?? $expense->prepaid_months;

            if (! $prepaidStart || ! $prepaidMonths) {
                return response()->json([
                    'message' => 'prepaid_start_date and prepaid_months are required for prepaid expenses.',
                ], 422);
            }
        }

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

        if ($mode !== 'payable') {
            $payload['payable_due_date'] = null;
        }

        if ($mode !== 'prepaid') {
            $payload['prepaid_start_date'] = null;
            $payload['prepaid_months'] = null;
        }

        $payload['accounting_mode'] = $mode;
        $payload['is_recurring'] = $isRecurring;

        $expense->update($payload);

        $fresh = Expense::query()
            ->with('creator:id,name,email')
            ->withSum('payments as paid_amount', 'amount')
            ->findOrFail($expense->id);

        $paymentMeta = $this->expensePaymentMeta($fresh, (float) ($fresh->paid_amount ?? 0));
        $fresh->setAttribute('paid_amount', $paymentMeta['paid_amount']);
        $fresh->setAttribute('outstanding_amount', $paymentMeta['outstanding_amount']);
        $fresh->setAttribute('payment_status', $paymentMeta['payment_status']);

        return response()->json([
            'message' => 'Expense updated successfully.',
            'expense' => $fresh,
        ]);
    }

    public function payments(int $id): JsonResponse
    {
        $expense = Expense::query()->findOrFail($id);

        $rows = ExpensePayment::query()
            ->with('recorder:id,name,email')
            ->where('expense_id', $expense->id)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'expense_id' => $expense->id,
            'rows' => $rows,
            'total_paid' => round((float) $rows->sum('amount'), 2),
        ]);
    }

    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $expense = Expense::query()->findOrFail($id);

        $payload = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'reference_number' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ]);

        $paidAmount = (float) $expense->payments()->sum('amount');
        $outstanding = round(max(0, (float) $expense->amount - $paidAmount), 2);
        $amount = round((float) $payload['amount'], 2);

        if ($outstanding <= 0) {
            return response()->json(['message' => 'Expense is already fully paid.'], 422);
        }

        if ($amount > $outstanding) {
            return response()->json(['message' => 'Payment amount cannot exceed outstanding balance.'], 422);
        }

        $payment = ExpensePayment::query()->create([
            'expense_id' => $expense->id,
            'recorded_by' => $request->user()->id,
            'payment_date' => $payload['payment_date'],
            'amount' => $amount,
            'payment_method' => $payload['payment_method'] ?? 'bank_transfer',
            'reference_number' => $payload['reference_number'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Expense payment recorded successfully.',
            'payment' => $payment->load('recorder:id,name,email'),
        ], 201);
    }

    public function deletePayment(int $expenseId, int $paymentId): JsonResponse
    {
        $expense = Expense::query()->findOrFail($expenseId);

        $payment = ExpensePayment::query()
            ->where('expense_id', $expense->id)
            ->findOrFail($paymentId);

        $payment->delete();

        return response()->json(['message' => 'Expense payment deleted successfully.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $expense = Expense::query()->findOrFail($id);
        $expense->delete();

        return response()->json(['message' => 'Expense deleted successfully.']);
    }

    /**
     * @return array{paid_amount: float, outstanding_amount: float, payment_status: string}
     */
    private function expensePaymentMeta(Expense $expense, float $paidAmount): array
    {
        $amount = round((float) $expense->amount, 2);
        $paid = round(max(0, $paidAmount), 2);
        $outstanding = round(max(0, $amount - $paid), 2);

        $status = 'unpaid';
        if ($outstanding <= 0) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        }

        return [
            'paid_amount' => $paid,
            'outstanding_amount' => $outstanding,
            'payment_status' => $status,
        ];
    }
}
