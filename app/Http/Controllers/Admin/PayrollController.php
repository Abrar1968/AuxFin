<?php

namespace App\Http\Controllers\Admin;

use App\Events\SalaryPaid;
use App\Events\SalaryProcessed;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalaryMonth;
use App\Services\LoanService;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService,
        private readonly LoanService $loanService,
    ) {
    }

    public function index(string $month): JsonResponse
    {
        $monthDate = Carbon::parse($month)->startOfMonth()->toDateString();

        $rows = SalaryMonth::query()
            ->with(['employee.user', 'employee.department'])
            ->whereDate('month', $monthDate)
            ->orderBy('id')
            ->get();

        return response()->json($rows);
    }

    public function showPayslip(int $id): JsonResponse
    {
        $salaryMonth = SalaryMonth::query()
            ->with(['employee.user', 'employee.department'])
            ->findOrFail($id);

        return response()->json($this->payrollService->buildPayslipPayload($salaryMonth));
    }

    public function process(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'month' => ['required', 'date'],
            'performance_bonus' => ['nullable', 'numeric', 'min:0'],
            'festival_bonus' => ['nullable', 'numeric', 'min:0'],
            'overtime_pay' => ['nullable', 'numeric', 'min:0'],
            'other_bonus' => ['nullable', 'numeric', 'min:0'],
        ]);

        $employee = Employee::query()->findOrFail($payload['employee_id']);
        $salaryMonth = $this->payrollService->processMonth($employee, $payload['month'], $payload);
        $this->loanService->applyMonthlyDeduction($employee, $payload['month']);

        $salaryMonth->update([
            'status' => 'processed',
            'processed_at' => now(),
            'processed_by' => $request->user()->id,
        ]);

        event(new SalaryProcessed($employee->id, [
            'salary_month_id' => $salaryMonth->id,
            'month' => $salaryMonth->month,
            'net_payable' => (float) $salaryMonth->net_payable,
        ]));

        return response()->json([
            'message' => 'Payroll processed successfully.',
            'salary_month' => $salaryMonth->fresh(),
        ]);
    }

    public function bulkProcess(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'month' => ['required', 'date'],
        ]);

        $processed = [];

        /** @var \Illuminate\Database\Eloquent\Collection<int, Employee> $employees */
        $employees = Employee::query()
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->get();

        foreach ($employees as $employee) {
            $row = $this->payrollService->processMonth($employee, $payload['month']);
            $this->loanService->applyMonthlyDeduction($employee, $payload['month']);

            $row->update([
                'status' => 'processed',
                'processed_at' => now(),
                'processed_by' => $request->user()->id,
            ]);

            $processed[] = $row->id;
        }

        return response()->json([
            'message' => 'Bulk payroll processing completed.',
            'processed_count' => count($processed),
            'salary_month_ids' => $processed,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $salaryMonth = SalaryMonth::query()->findOrFail($id);

        if ($salaryMonth->status === 'paid') {
            return response()->json(['message' => 'Paid salary records cannot be modified.'], 422);
        }

        $payload = $request->validate([
            'performance_bonus' => ['sometimes', 'numeric', 'min:0'],
            'festival_bonus' => ['sometimes', 'numeric', 'min:0'],
            'overtime_pay' => ['sometimes', 'numeric', 'min:0'],
            'other_bonus' => ['sometimes', 'numeric', 'min:0'],
            'tds_deduction' => ['sometimes', 'numeric', 'min:0'],
            'pf_deduction' => ['sometimes', 'numeric', 'min:0'],
            'professional_tax' => ['sometimes', 'numeric', 'min:0'],
            'unpaid_leave_deduction' => ['sometimes', 'numeric', 'min:0'],
            'late_penalty_deduction' => ['sometimes', 'numeric', 'min:0'],
            'loan_emi_deduction' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $salaryMonth->fill($payload);

        $gross = (float) $salaryMonth->basic_salary
            + (float) $salaryMonth->house_rent
            + (float) $salaryMonth->conveyance
            + (float) $salaryMonth->medical_allowance
            + (float) $salaryMonth->performance_bonus
            + (float) $salaryMonth->festival_bonus
            + (float) $salaryMonth->overtime_pay
            + (float) $salaryMonth->other_bonus;

        $deductions = (float) $salaryMonth->tds_deduction
            + (float) $salaryMonth->pf_deduction
            + (float) $salaryMonth->professional_tax
            + (float) $salaryMonth->unpaid_leave_deduction
            + (float) $salaryMonth->late_penalty_deduction
            + (float) $salaryMonth->loan_emi_deduction;

        $salaryMonth->update([
            'gross_earnings' => round($gross, 2),
            'total_deductions' => round($deductions, 2),
            'net_payable' => round($gross - $deductions, 2),
        ]);

        return response()->json([
            'message' => 'Payroll record updated successfully.',
            'salary_month' => $salaryMonth->fresh(),
        ]);
    }

    public function markPaid(int $id): JsonResponse
    {
        $salaryMonth = SalaryMonth::query()->findOrFail($id);

        $salaryMonth->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        event(new SalaryPaid($salaryMonth->employee_id, [
            'salary_month_id' => $salaryMonth->id,
            'month' => $salaryMonth->month,
            'net_payable' => (float) $salaryMonth->net_payable,
        ]));

        return response()->json([
            'message' => 'Salary marked as paid.',
            'salary_month' => $salaryMonth,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $salaryMonth = SalaryMonth::query()->findOrFail($id);

        if ($salaryMonth->status === 'paid') {
            return response()->json(['message' => 'Paid salary records cannot be deleted.'], 422);
        }

        $salaryMonth->delete();

        return response()->json([
            'message' => 'Payroll record deleted successfully.',
        ]);
    }
}
