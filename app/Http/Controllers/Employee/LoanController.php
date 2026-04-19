<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct(private readonly LoanService $loanService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $loans = Loan::query()
            ->where('employee_id', $employee->id)
            ->with('repayments')
            ->withSum('repayments as total_repaid', 'amount_paid')
            ->orderByDesc('id')
            ->get();

        $loans->transform(fn (Loan $loan) => $this->appendLoanMetrics($loan));

        return response()->json($loans);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $loan = Loan::query()
            ->where('employee_id', $employee->id)
            ->with([
                'reviewer:id,name,email',
                'repayments' => fn ($query) => $query->orderBy('month'),
            ])
            ->withSum('repayments as total_repaid', 'amount_paid')
            ->findOrFail($id);

        $loan = $this->appendLoanMetrics($loan);

        return response()->json([
            'loan' => $loan,
            'repayment_schedule' => $loan->repayments,
        ]);
    }

    public function policy(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $policy = $this->loanService->getPolicy();

        return response()->json([
            'policy' => $policy,
            'max_amount_for_employee' => round((float) $employee->basic_salary * (int) $policy['max_loan_multiplier'], 2),
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $payload = $request->validate([
            'amount_requested' => ['required', 'numeric', 'min:1'],
            'reason' => ['required', 'string', 'min:3'],
            'preferred_repayment_months' => ['nullable', 'integer', 'between:1,12'],
        ]);

        $loan = $this->loanService->apply($employee, $payload);

        return response()->json([
            'message' => 'Loan application submitted successfully.',
            'loan' => $loan,
        ], 201);
    }

    private function appendLoanMetrics(Loan $loan): Loan
    {
        $approved = (float) ($loan->amount_approved ?? 0);
        $remaining = (float) ($loan->amount_remaining ?? 0);
        $repaid = (float) ($loan->total_repaid ?? max(0, $approved - $remaining));
        $monthsLeft = (float) ($loan->emi_amount ?? 0) > 0
            ? (int) ceil($remaining / (float) $loan->emi_amount)
            : 0;

        $loan->setAttribute('total_repaid', round($repaid, 2));
        $loan->setAttribute('repayment_progress_percent', $approved > 0 ? round(($repaid / $approved) * 100, 2) : 0);
        $loan->setAttribute('months_left', max(0, $monthsLeft));

        return $loan;
    }
}
