<?php

namespace App\Http\Controllers\Admin;

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
        $loans = Loan::query()
            ->with(['employee.user', 'employee.department'])
            ->withSum('repayments as total_repaid', 'amount_paid')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        $loans->getCollection()->transform(fn (Loan $loan) => $this->appendLoanMetrics($loan));

        return response()->json($loans);
    }

    public function show(int $id): JsonResponse
    {
        $loan = Loan::query()
            ->with([
                'employee.user',
                'employee.department',
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

    public function approve(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'amount_approved' => ['required', 'numeric', 'min:1'],
            'repayment_months' => ['required', 'integer', 'between:1,60'],
            'start_month' => ['required', 'date'],
            'admin_note' => ['nullable', 'string'],
        ]);

        $loan = Loan::query()->findOrFail($id);
        $loan = $this->loanService->approve($loan, $payload, $request->user());

        return response()->json([
            'message' => 'Loan approved successfully.',
            'loan' => $loan,
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'admin_note' => ['required', 'string', 'min:3'],
        ]);

        $loan = Loan::query()->findOrFail($id);
        $loan = $this->loanService->reject($loan, $payload['admin_note'], $request->user());

        return response()->json([
            'message' => 'Loan rejected successfully.',
            'loan' => $loan,
        ]);
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
