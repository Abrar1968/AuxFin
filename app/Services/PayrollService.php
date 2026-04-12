<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\PublicHoliday;
use App\Models\SalaryMonth;
use App\Models\Setting;
use Carbon\Carbon;

class PayrollService
{
    public function processMonth(Employee $employee, string $month, array $overrides = []): SalaryMonth
    {
        $monthDate = Carbon::parse($month)->startOfMonth();
        $expectedDays = max(1, $this->getExpectedWorkingDays($employee, $monthDate->toDateString()));

        $gross = (float) $employee->basic_salary
            + (float) $employee->house_rent
            + (float) $employee->conveyance
            + (float) $employee->medical_allowance
            + (float) ($overrides['performance_bonus'] ?? 0)
            + (float) ($overrides['festival_bonus'] ?? 0)
            + (float) ($overrides['overtime_pay'] ?? 0)
            + (float) ($overrides['other_bonus'] ?? 0);

        $dailyRate = $gross / $expectedDays;
        $lateDays = $this->getConfirmedLateDays($employee->id, $monthDate->toDateString());
        $latePolicy = $this->getLatePolicy();
        $latePolicyUnit = (int) ($overrides['late_days_per_unit'] ?? $latePolicy['late_days_per_unit']);
        $deductionUnitType = (string) ($overrides['deduction_unit_type'] ?? $latePolicy['deduction_unit_type']);
        $lateUnitRate = $deductionUnitType === 'half_day' ? 0.5 : 1.0;
        $lateUnits = (int) floor($lateDays / max(1, $latePolicyUnit));
        $latePenalty = $dailyRate * $lateUnits * $lateUnitRate;

        $unpaidLeaveDays = min($expectedDays, $this->getUnpaidLeaveDays($employee->id, $monthDate->toDateString()));
        $leaveDeduction = $dailyRate * $unpaidLeaveDays;

        $tds = $gross * ((float) $employee->tds_rate / 100);
        $pf = (float) $employee->basic_salary * ((float) $employee->pf_rate / 100);
        $professionalTax = (float) $employee->professional_tax;
        $loanEmi = $this->getActiveLoanEmi($employee->id, $monthDate->toDateString());

        $totalDeductions = $tds + $pf + $professionalTax + $leaveDeduction + $latePenalty + $loanEmi;
        $netPayable = $gross - $totalDeductions;

        return SalaryMonth::query()->updateOrCreate(
            ['employee_id' => $employee->id, 'month' => $monthDate->toDateString()],
            [
                'basic_salary' => $employee->basic_salary,
                'house_rent' => $employee->house_rent,
                'conveyance' => $employee->conveyance,
                'medical_allowance' => $employee->medical_allowance,
                'performance_bonus' => (float) ($overrides['performance_bonus'] ?? 0),
                'festival_bonus' => (float) ($overrides['festival_bonus'] ?? 0),
                'overtime_pay' => (float) ($overrides['overtime_pay'] ?? 0),
                'other_bonus' => (float) ($overrides['other_bonus'] ?? 0),
                'gross_earnings' => round($gross, 2),
                'tds_deduction' => round($tds, 2),
                'pf_deduction' => round($pf, 2),
                'professional_tax' => round($professionalTax, 2),
                'unpaid_leave_deduction' => round($leaveDeduction, 2),
                'late_penalty_deduction' => round($latePenalty, 2),
                'loan_emi_deduction' => round($loanEmi, 2),
                'total_deductions' => round($totalDeductions, 2),
                'net_payable' => round($netPayable, 2),
                'unpaid_leave_days' => $unpaidLeaveDays,
                'late_entries' => $lateDays,
                'expected_working_days' => $expectedDays,
                'days_present' => $this->getPresentDays($employee->id, $monthDate->toDateString()),
                'status' => $overrides['status'] ?? 'draft',
            ]
        );
    }

    public function buildPayslipPayload(SalaryMonth $salaryMonth): array
    {
        $salaryMonth->loadMissing(['employee.user', 'employee.department']);

        $previous = SalaryMonth::query()
            ->where('employee_id', $salaryMonth->employee_id)
            ->whereDate('month', '<', Carbon::parse($salaryMonth->month)->startOfMonth()->toDateString())
            ->orderByDesc('month')
            ->first();

        $previousNet = (float) ($previous?->net_payable ?? 0);
        $currentNet = (float) $salaryMonth->net_payable;
        $deltaPercentage = $previousNet > 0
            ? (($currentNet - $previousNet) / $previousNet) * 100
            : 0;

        $activeLoan = Loan::query()
            ->where('employee_id', $salaryMonth->employee_id)
            ->whereIn('status', ['approved', 'active', 'completed'])
            ->whereDate('start_month', '<=', Carbon::parse($salaryMonth->month)->startOfMonth()->toDateString())
            ->orderByDesc('id')
            ->first();

        $loanRepayments = $activeLoan
            ? LoanRepayment::query()
                ->where('loan_id', $activeLoan->id)
                ->orderBy('month')
                ->get(['month', 'amount_paid'])
                ->map(fn (LoanRepayment $repayment) => [
                    'month' => Carbon::parse($repayment->month)->format('Y-m'),
                    'amount_paid' => (float) $repayment->amount_paid,
                ])
                ->values()
                ->all()
            : [];

        $monthsLeft = 0;
        if ($activeLoan && (float) $activeLoan->emi_amount > 0) {
            $monthsLeft = (int) ceil(((float) $activeLoan->amount_remaining) / ((float) $activeLoan->emi_amount));
        }

        return [
            'meta' => [
                'month' => Carbon::parse($salaryMonth->month)->format('F Y'),
                'payment_date' => optional($salaryMonth->paid_at)->toDateString(),
                'status' => $salaryMonth->status,
            ],
            'employee' => [
                'name' => $salaryMonth->employee?->user?->name,
                'employee_code' => $salaryMonth->employee?->employee_code,
                'department' => $salaryMonth->employee?->department?->name,
                'designation' => $salaryMonth->employee?->designation,
            ],
            'earnings' => [
                'basic_salary' => (float) $salaryMonth->basic_salary,
                'house_rent' => (float) $salaryMonth->house_rent,
                'conveyance' => (float) $salaryMonth->conveyance,
                'medical_allowance' => (float) $salaryMonth->medical_allowance,
                'performance_bonus' => (float) $salaryMonth->performance_bonus,
                'festival_bonus' => (float) $salaryMonth->festival_bonus,
                'overtime_pay' => (float) $salaryMonth->overtime_pay,
                'other_bonus' => (float) $salaryMonth->other_bonus,
                'gross_earnings' => (float) $salaryMonth->gross_earnings,
            ],
            'deductions' => [
                'tds_deduction' => (float) $salaryMonth->tds_deduction,
                'pf_deduction' => (float) $salaryMonth->pf_deduction,
                'professional_tax' => (float) $salaryMonth->professional_tax,
                'unpaid_leave_deduction' => (float) $salaryMonth->unpaid_leave_deduction,
                'late_penalty_deduction' => (float) $salaryMonth->late_penalty_deduction,
                'loan_emi_deduction' => (float) $salaryMonth->loan_emi_deduction,
                'total_deductions' => (float) $salaryMonth->total_deductions,
            ],
            'net_payable' => (float) $salaryMonth->net_payable,
            'month_over_month_delta_percent' => round($deltaPercentage, 2),
            'loan' => [
                'loan_reference' => $activeLoan?->loan_reference,
                'status' => $activeLoan?->status,
                'emi_amount' => (float) ($activeLoan?->emi_amount ?? 0),
                'amount_remaining' => (float) ($activeLoan?->amount_remaining ?? 0),
                'months_left' => $monthsLeft,
                'repayment_schedule' => $loanRepayments,
            ],
        ];
    }

    public function getAttendanceSummary(Employee $employee, string $month): array
    {
        $monthDate = Carbon::parse($month)->startOfMonth();
        $expected = $this->getExpectedWorkingDays($employee, $monthDate->toDateString());
        $present = $this->getPresentDays($employee->id, $monthDate->toDateString());
        $lateEntries = $this->getConfirmedLateDays($employee->id, $monthDate->toDateString());

        $gross = (float) $employee->basic_salary
            + (float) $employee->house_rent
            + (float) $employee->conveyance
            + (float) $employee->medical_allowance;
        $dailyRate = $expected > 0 ? $gross / $expected : 0;

        $policy = $this->getLatePolicy();
        $daysPerUnit = max(1, (int) $policy['late_days_per_unit']);
        $unitRate = $policy['deduction_unit_type'] === 'half_day' ? 0.5 : 1.0;
        $lateUnits = (int) floor($lateEntries / $daysPerUnit);
        $lateDeductionAmount = $dailyRate * $lateUnits * $unitRate;
        $remainingLateBudget = $daysPerUnit - ($lateEntries % $daysPerUnit);

        return [
            'expected_working_days' => $expected,
            'days_present' => $present,
            'days_absent' => max(0, $expected - $present),
            'late_entries' => $lateEntries,
            'daily_rate' => round($dailyRate, 2),
            'late_deduction_applied' => round($lateDeductionAmount, 2),
            'remaining_late_budget_before_next_deduction' => $remainingLateBudget,
            'late_days_per_unit' => $daysPerUnit,
            'deduction_unit_type' => $policy['deduction_unit_type'],
        ];
    }

    public function getExpectedWorkingDays(Employee $employee, string $month): int
    {
        $monthDate = Carbon::parse($month)->startOfMonth();
        $daysInMonth = $monthDate->daysInMonth;
        $offDays = collect($this->resolveWeeklyOffDays($employee))
            ->map(fn ($day) => strtolower((string) $day))
            ->filter()
            ->values();

        $offDayCount = 0;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $monthDate->copy()->day($day);
            if ($offDays->contains(strtolower($date->englishDayOfWeek))) {
                $offDayCount++;
            }
        }

        $holidayCount = PublicHoliday::query()
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month)
            ->where('is_optional', false)
            ->count();

        return max(1, $daysInMonth - $offDayCount - $holidayCount);
    }

    public function getConfirmedLateDays(int $employeeId, string $month): int
    {
        $monthDate = Carbon::parse($month);

        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month)
            ->where('is_late', true)
            ->count();
    }

    public function getUnpaidLeaveDays(int $employeeId, string $month): int
    {
        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $leaves = Leave::query()
            ->where('employee_id', $employeeId)
            ->where('leave_type', 'unpaid')
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $endOfMonth->toDateString())
            ->whereDate('to_date', '>=', $startOfMonth->toDateString())
            ->get(['from_date', 'to_date']);

        $days = 0;
        foreach ($leaves as $leave) {
            $from = Carbon::parse($leave->from_date)->max($startOfMonth);
            $to = Carbon::parse($leave->to_date)->min($endOfMonth);
            if ($to->gte($from)) {
                $days += $from->diffInDays($to) + 1;
            }
        }

        return $days;
    }

    public function getActiveLoanEmi(int $employeeId, string $month): float
    {
        $monthDate = Carbon::parse($month)->startOfMonth();

        $loan = Loan::query()
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['approved', 'active'])
            ->whereDate('start_month', '<=', $monthDate->toDateString())
            ->where('amount_remaining', '>', 0)
            ->orderBy('id')
            ->first();

        return (float) ($loan?->emi_amount ?? 0);
    }

    public function getPresentDays(int $employeeId, string $month): int
    {
        $monthDate = Carbon::parse($month);

        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month)
            ->whereIn('status', ['present', 'late'])
            ->count();
    }

    private function getLatePolicy(): array
    {
        $defaults = [
            'late_days_per_unit' => 2,
            'deduction_unit_type' => 'full_day',
            'grace_period_minutes' => 15,
            'office_start_time' => '09:00',
            'carry_forward' => false,
        ];

        $value = Setting::getValue('late_policy', $defaults);
        if (! is_array($value)) {
            return $defaults;
        }

        return array_merge($defaults, $value);
    }

    private function resolveWeeklyOffDays(Employee $employee): array
    {
        $configured = (array) ($employee->weekly_off_days ?? []);
        if ($configured !== []) {
            return $configured;
        }

        return match ((int) $employee->working_days_per_week) {
            6 => ['friday'],
            5 => ['friday', 'saturday'],
            4 => ['thursday', 'friday', 'saturday'],
            default => [],
        };
    }
}
