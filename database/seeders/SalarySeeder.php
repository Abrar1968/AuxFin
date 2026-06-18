<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Loan;
use App\Models\SalaryMonth;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SalarySeeder extends Seeder
{
    public function run(): void
    {
        $admin     = User::query()->where('email', 'admin@auxfin.local')->firstOrFail();
        $employees = Employee::query()->get();

        // Seed 6 months: 5 past (paid) + current month (processed)
        $months = collect(range(0, 5))
            ->map(fn(int $o) => Carbon::now()->startOfMonth()->subMonths(5 - $o));

        foreach ($employees as $emp) {
            foreach ($months as $idx => $monthDate) {
                $isCurrentMonth = $monthDate->equalTo(Carbon::now()->startOfMonth());
                $month          = $monthDate->toDateString();

                // ── Bonuses vary by month index ──────────────────────────
                $performanceBonus = match ($idx % 3) {
                    0       => 1500.00,
                    1       => 2000.00,
                    default => 1000.00,
                };
                $festivalBonus = ($idx === 2) ? 5000.00 : 0.00; // Eid bonus in month 2
                $overtimePay   = ($idx % 4 === 1) ? 2500.00 : 0.00;
                $otherBonus    = 0.00;

                $basic   = (float) $emp->basic_salary;
                $hr      = (float) $emp->house_rent;
                $conv    = (float) $emp->conveyance;
                $med     = (float) $emp->medical_allowance;
                $gross   = $basic + $hr + $conv + $med + $performanceBonus + $festivalBonus + $overtimePay;

                // ── Working days  ────────────────────────────────────────
                $expectedWorkingDays = (int) $emp->working_days_per_week >= 6 ? 24 : 22;
                $dailyRate           = $gross / max(1, $expectedWorkingDays);

                // ── Late / unpaid leave vary per employee + month ────────
                $lateEntries     = ($idx + $emp->id) % 4;           // 0–3 per month
                $latePenalty     = round($dailyRate * floor($lateEntries / 2), 2);
                $unpaidLeaveDays = ($idx % 3 === 0) ? 1 : 0;
                $leaveDeduction  = round($dailyRate * $unpaidLeaveDays, 2);

                // ── Statutory deductions ─────────────────────────────────
                $tds  = round($gross * ((float) $emp->tds_rate / 100), 2);
                $pf   = round($basic * ((float) $emp->pf_rate / 100), 2);
                $pt   = (float) $emp->professional_tax;

                // ── Active loan EMI for this month ───────────────────────
                $loanEmi = $this->loanEmiForMonth($emp->id, $monthDate);

                $totalDeductions = round($tds + $pf + $pt + $leaveDeduction + $latePenalty + $loanEmi, 2);
                $netPayable      = round($gross - $totalDeductions, 2);
                $daysPresent     = max(0, $expectedWorkingDays - $unpaidLeaveDays);

                $status      = $isCurrentMonth ? 'processed' : 'paid';
                $processedAt = $monthDate->copy()->day(25);
                $paidAt      = $isCurrentMonth ? null : $monthDate->copy()->day(28);

                SalaryMonth::query()->updateOrCreate(
                    ['employee_id' => $emp->id, 'month' => $month],
                    [
                        'basic_salary'            => $basic,
                        'house_rent'              => $hr,
                        'conveyance'              => $conv,
                        'medical_allowance'       => $med,
                        'performance_bonus'       => $performanceBonus,
                        'festival_bonus'          => $festivalBonus,
                        'overtime_pay'            => $overtimePay,
                        'other_bonus'             => $otherBonus,
                        'gross_earnings'          => round($gross, 2),
                        'tds_deduction'           => $tds,
                        'pf_deduction'            => $pf,
                        'professional_tax'        => $pt,
                        'unpaid_leave_deduction'  => $leaveDeduction,
                        'late_penalty_deduction'  => $latePenalty,
                        'loan_emi_deduction'      => $loanEmi,
                        'total_deductions'        => $totalDeductions,
                        'net_payable'             => $netPayable,
                        'days_present'            => $daysPresent,
                        'unpaid_leave_days'       => $unpaidLeaveDays,
                        'late_entries'            => $lateEntries,
                        'expected_working_days'   => $expectedWorkingDays,
                        'status'                  => $status,
                        'processed_at'            => $processedAt,
                        'paid_at'                 => $paidAt,
                        'processed_by'            => $admin->id,
                    ]
                );
            }
        }
    }

    private function loanEmiForMonth(int $employeeId, Carbon $monthDate): float
    {
        $loan = Loan::query()
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['active', 'completed'])
            ->whereNotNull('start_month')
            ->whereDate('start_month', '<=', $monthDate->toDateString())
            ->orderByDesc('id')
            ->first();

        if (! $loan) {
            return 0.0;
        }

        // Only deduct if repayment month falls within loan term
        $startMonth   = Carbon::parse($loan->start_month);
        $monthsDiff   = $startMonth->diffInMonths($monthDate);
        $totalMonths  = (int) $loan->repayment_months;

        if ($monthsDiff < $totalMonths) {
            return (float) ($loan->emi_amount ?? 0);
        }

        return 0.0;
    }
}
