<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeMessage;
use App\Models\Leave;
use App\Models\Loan;
use App\Models\MessageRead;
use App\Models\PublicHoliday;
use App\Models\SalaryMonth;
use App\Models\Setting;
use App\Models\User;
use App\Services\SnapshotService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class Phase5OperationsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@finerp.local')->first()
            ?? User::query()->where('email', 'owner@finerp.local')->first();

        if (! $admin) {
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Employee> $employees */
        $employees = Employee::query()->with('user')->get();

        if ($employees->isEmpty()) {
            return;
        }

        Setting::query()->updateOrCreate(
            ['key' => 'general_settings'],
            ['value' => [
                'company_name' => 'AuxFin',
                'company_email' => 'finance@auxfin.local',
                'currency' => 'BDT',
                'timezone' => 'Asia/Dhaka',
                'available_cash' => 1500000,
            ]]
        );

        $months = collect(range(0, 5))
            ->map(fn (int $offset) => now()->startOfMonth()->subMonths(5 - $offset));

        foreach ($employees as $employee) {
            foreach ($months as $index => $monthDate) {
                $month = $monthDate->toDateString();

                $gross = (float) $employee->basic_salary
                    + (float) $employee->house_rent
                    + (float) $employee->conveyance
                    + (float) $employee->medical_allowance
                    + ($index % 2 === 0 ? 1200 : 800)
                    + ($index === 2 ? 2000 : 0);

                $expectedWorkingDays = (int) $employee->working_days_per_week >= 6 ? 24 : 22;
                $dailyRate = $gross / max(1, $expectedWorkingDays);
                $lateEntries = ($index + $employee->id) % 4;
                $latePenalty = round($dailyRate * floor($lateEntries / 2), 2);
                $unpaidLeaveDays = $index % 3 === 0 ? 1 : 0;
                $leaveDeduction = round($dailyRate * $unpaidLeaveDays, 2);

                $tds = round($gross * ((float) $employee->tds_rate / 100), 2);
                $pf = round((float) $employee->basic_salary * ((float) $employee->pf_rate / 100), 2);
                $professionalTax = (float) $employee->professional_tax;

                $loanEmi = $this->loanEmiForMonth($employee->id, $monthDate);

                $totalDeductions = round($tds + $pf + $professionalTax + $leaveDeduction + $latePenalty + $loanEmi, 2);
                $netPayable = round($gross - $totalDeductions, 2);

                $isCurrentMonth = $monthDate->equalTo(now()->startOfMonth());
                $status = $isCurrentMonth ? 'processed' : 'paid';

                SalaryMonth::query()->updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'month' => $month,
                    ],
                    [
                        'basic_salary' => $employee->basic_salary,
                        'house_rent' => $employee->house_rent,
                        'conveyance' => $employee->conveyance,
                        'medical_allowance' => $employee->medical_allowance,
                        'performance_bonus' => $index % 2 === 0 ? 1200 : 800,
                        'festival_bonus' => $index === 2 ? 2000 : 0,
                        'overtime_pay' => 0,
                        'other_bonus' => 0,
                        'gross_earnings' => round($gross, 2),
                        'tds_deduction' => $tds,
                        'pf_deduction' => $pf,
                        'professional_tax' => $professionalTax,
                        'unpaid_leave_deduction' => $leaveDeduction,
                        'late_penalty_deduction' => $latePenalty,
                        'loan_emi_deduction' => $loanEmi,
                        'total_deductions' => $totalDeductions,
                        'net_payable' => $netPayable,
                        'days_present' => max(0, $expectedWorkingDays - $unpaidLeaveDays),
                        'unpaid_leave_days' => $unpaidLeaveDays,
                        'late_entries' => $lateEntries,
                        'expected_working_days' => $expectedWorkingDays,
                        'status' => $status,
                        'processed_at' => $monthDate->copy()->endOfMonth()->subDays(2),
                        'paid_at' => $status === 'paid' ? $monthDate->copy()->endOfMonth()->subDay() : null,
                        'processed_by' => $admin->id,
                    ]
                );
            }

            $this->seedCurrentMonthAttendance($employee);
            $this->seedLeaveHistory($employee, $admin->id);
            $this->seedMessages($employee, $admin->id);
        }

        PublicHoliday::query()->updateOrCreate(
            ['date' => now()->startOfMonth()->addDays(14)->toDateString()],
            ['name' => 'Mid-Month Public Holiday', 'is_optional' => false]
        );

        PublicHoliday::query()->updateOrCreate(
            ['date' => now()->startOfMonth()->addMonth()->addDays(5)->toDateString()],
            ['name' => 'Founders Day', 'is_optional' => false]
        );

        /** @var SnapshotService $snapshotService */
        $snapshotService = app(SnapshotService::class);

        foreach ($months as $monthDate) {
            $snapshotService->capture($monthDate->toDateString());
        }
    }

    private function seedCurrentMonthAttendance(Employee $employee): void
    {
        $monthStart = now()->startOfMonth();
        $created = 0;

        for ($day = 1; $day <= $monthStart->daysInMonth; $day++) {
            if ($created >= 14) {
                break;
            }

            $date = $monthStart->copy()->day($day);
            if (in_array(strtolower($date->englishDayOfWeek), (array) $employee->weekly_off_days, true)) {
                continue;
            }

            $isLate = $created % 5 === 0;

            Attendance::query()->updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $date->toDateString(),
                ],
                [
                    'check_in' => $isLate ? '09:28:00' : '09:02:00',
                    'check_out' => '18:05:00',
                    'status' => $isLate ? 'late' : 'present',
                    'is_late' => $isLate,
                    'late_minutes' => $isLate ? 28 : null,
                ]
            );

            $created++;
        }
    }

    private function seedLeaveHistory(Employee $employee, int $adminId): void
    {
        $base = now()->startOfMonth();

        Leave::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'from_date' => $base->copy()->subMonths(2)->addDays(8)->toDateString(),
                'to_date' => $base->copy()->subMonths(2)->addDays(9)->toDateString(),
            ],
            [
                'leave_type' => 'casual',
                'days' => 2,
                'reason' => 'Family travel',
                'status' => 'approved',
                'admin_note' => 'Approved',
                'reviewed_by' => $adminId,
                'reviewed_at' => now()->subMonths(2),
            ]
        );

        Leave::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'from_date' => $base->copy()->subMonth()->addDays(3)->toDateString(),
                'to_date' => $base->copy()->subMonth()->addDays(3)->toDateString(),
            ],
            [
                'leave_type' => 'sick',
                'days' => 1,
                'reason' => 'Flu recovery',
                'status' => 'rejected',
                'admin_note' => 'Insufficient leave documentation',
                'reviewed_by' => $adminId,
                'reviewed_at' => now()->subMonth(),
            ]
        );

        Leave::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'from_date' => $base->copy()->addDays(20)->toDateString(),
                'to_date' => $base->copy()->addDays(21)->toDateString(),
            ],
            [
                'leave_type' => 'earned',
                'days' => 2,
                'reason' => 'Personal engagement',
                'status' => 'pending',
                'admin_note' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );
    }

    private function seedMessages(Employee $employee, int $adminId): void
    {
        $month = now()->startOfMonth()->toDateString();

        $open = EmployeeMessage::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'subject' => 'Late attendance clarification',
            ],
            [
                'type' => 'late_appeal',
                'body' => 'Requesting review for one late marking due to traffic.',
                'reference_date' => now()->startOfMonth()->addDay()->toDateString(),
                'reference_month' => $month,
                'status' => 'open',
                'priority' => 'normal',
                'action_taken' => 'none',
            ]
        );

        $resolved = EmployeeMessage::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'subject' => 'Deduction dispute resolved',
            ],
            [
                'type' => 'deduction_dispute',
                'body' => 'Please review late penalty from previous month.',
                'reference_month' => now()->startOfMonth()->subMonth()->toDateString(),
                'status' => 'resolved',
                'priority' => 'high',
                'admin_reply' => 'Resolved and adjusted in payroll.',
                'replied_by' => $adminId,
                'replied_at' => now()->subDays(5),
                'action_taken' => 'deduction_reversed',
                'resolved_at' => now()->subDays(5),
            ]
        );

        EmployeeMessage::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'subject' => 'Loan repayment schedule query',
            ],
            [
                'type' => 'loan_query',
                'body' => 'Need latest repayment schedule details.',
                'status' => 'under_review',
                'priority' => 'normal',
                'admin_reply' => 'We are reviewing and will share breakdown.',
                'replied_by' => $adminId,
                'replied_at' => now()->subDays(2),
                'action_taken' => 'noted',
            ]
        );

        EmployeeMessage::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'subject' => 'General HR request declined',
            ],
            [
                'type' => 'general_hr',
                'body' => 'Request for unsupported policy exception.',
                'status' => 'rejected',
                'priority' => 'normal',
                'admin_reply' => 'Cannot proceed under current policy.',
                'replied_by' => $adminId,
                'replied_at' => now()->subDays(7),
                'action_taken' => 'none',
            ]
        );

        MessageRead::query()->updateOrCreate(
            [
                'message_id' => $resolved->id,
                'user_id' => $employee->user_id,
            ],
            [
                'read_at' => now()->subDays(4),
            ]
        );

        MessageRead::query()->updateOrCreate(
            [
                'message_id' => $open->id,
                'user_id' => $adminId,
            ],
            [
                'read_at' => now()->subHour(),
            ]
        );
    }

    private function loanEmiForMonth(int $employeeId, Carbon $monthDate): float
    {
        $loan = Loan::query()
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['approved', 'active', 'completed'])
            ->whereNotNull('start_month')
            ->whereDate('start_month', '<=', $monthDate->toDateString())
            ->orderByDesc('id')
            ->first();

        if (! $loan) {
            return 0;
        }

        return (float) ($loan->emi_amount ?? 0);
    }
}
