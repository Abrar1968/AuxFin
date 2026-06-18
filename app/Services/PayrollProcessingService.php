<?php

namespace App\Services;

use App\Events\SalaryPaid;
use App\Events\SalaryProcessed;
use App\Models\Employee;
use App\Models\SalaryMonth;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PayrollProcessingService
{
    public function __construct(
        private readonly PayrollService $payrollService,
        private readonly LoanService $loanService,
    ) {
    }

    public function listMonth(string $month, int $perPage = 20): LengthAwarePaginator
    {
        $monthDate = Carbon::parse($month)->startOfMonth()->toDateString();

        return SalaryMonth::query()
            ->with(['employee.user', 'employee.department'])
            ->whereDate('month', $monthDate)
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function buildPayslip(int $id): array
    {
        $salaryMonth = SalaryMonth::query()
            ->with(['employee.user', 'employee.department'])
            ->findOrFail($id);

        return $this->payrollService->buildPayslipPayload($salaryMonth);
    }

    public function ensureProcessable(int $employeeId, string $month): void
    {
        $monthDate = Carbon::parse($month)->startOfMonth()->toDateString();
        $existing = SalaryMonth::query()
            ->where('employee_id', $employeeId)
            ->whereDate('month', $monthDate)
            ->first();

        if ($existing?->status === 'paid') {
            throw ValidationException::withMessages([
                'month' => ['Paid salary records cannot be reprocessed.'],
            ]);
        }
    }

    public function processEmployee(int $employeeId, string $month, int $processedBy, array $overrides = []): SalaryMonth
    {
        $this->ensureProcessable($employeeId, $month);

        $employee = Employee::query()->findOrFail($employeeId);
        $salaryMonth = $this->payrollService->processMonth($employee, $month, $overrides);
        $this->loanService->applyMonthlyDeduction($employee, $month);

        $salaryMonth->update([
            'status' => 'processed',
            'processed_at' => now(),
            'processed_by' => $processedBy,
        ]);

        event(new SalaryProcessed($employee->id, [
            'salary_month_id' => $salaryMonth->id,
            'month' => $salaryMonth->month,
            'net_payable' => (float) $salaryMonth->net_payable,
        ]));

        return $salaryMonth->fresh();
    }

    /**
     * @return array{processed_count:int,salary_month_ids:array<int,int>}
     */
    public function processBulk(string $month, int $processedBy): array
    {
        $processed = [];

        /** @var \Illuminate\Database\Eloquent\Collection<int, Employee> $employees */
        $employees = Employee::query()
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->get();

        foreach ($employees as $employee) {
            $existing = SalaryMonth::query()
                ->where('employee_id', $employee->id)
                ->whereDate('month', Carbon::parse($month)->startOfMonth()->toDateString())
                ->first();

            if ($existing?->status === 'paid') {
                continue;
            }

            $row = $this->processEmployee($employee->id, $month, $processedBy);
            $processed[] = (int) $row->id;
        }

        return [
            'processed_count' => count($processed),
            'salary_month_ids' => $processed,
        ];
    }

    public function update(SalaryMonth $salaryMonth, array $payload): SalaryMonth
    {
        if ($salaryMonth->status === 'paid') {
            throw ValidationException::withMessages([
                'salary_month' => ['Paid salary records cannot be modified.'],
            ]);
        }

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
            ...$payload,
            'gross_earnings' => round($gross, 2),
            'total_deductions' => round($deductions, 2),
            'net_payable' => round($gross - $deductions, 2),
        ]);

        return $salaryMonth->fresh();
    }

    public function markPaid(SalaryMonth $salaryMonth): SalaryMonth
    {
        $salaryMonth->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        event(new SalaryPaid($salaryMonth->employee_id, [
            'salary_month_id' => $salaryMonth->id,
            'month' => $salaryMonth->month,
            'net_payable' => (float) $salaryMonth->net_payable,
        ]));

        return $salaryMonth->fresh();
    }

    public function delete(SalaryMonth $salaryMonth): void
    {
        if ($salaryMonth->status === 'paid') {
            throw ValidationException::withMessages([
                'salary_month' => ['Paid salary records cannot be deleted.'],
            ]);
        }

        $salaryMonth->delete();
    }
}
