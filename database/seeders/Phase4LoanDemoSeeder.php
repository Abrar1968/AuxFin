<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class Phase4LoanDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@finerp.local')->first()
            ?? User::query()->where('email', 'owner@finerp.local')->first();

        $empSadia = Employee::query()->where('employee_code', 'EMP-0101')->first();
        $empFahim = Employee::query()->where('employee_code', 'EMP-0102')->first();
        $empNabila = Employee::query()->where('employee_code', 'EMP-0103')->first();

        if (! $admin || ! $empSadia || ! $empFahim || ! $empNabila) {
            return;
        }

        $pending = Loan::query()->updateOrCreate(
            ['loan_reference' => 'LON-2026-9001'],
            [
                'employee_id' => $empSadia->id,
                'amount_requested' => 90000,
                'amount_approved' => null,
                'repayment_months' => null,
                'emi_amount' => null,
                'start_month' => null,
                'reason' => 'Medical emergency and family support requirement.',
                'status' => 'pending',
                'amount_remaining' => null,
                'admin_note' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        $rejected = Loan::query()->updateOrCreate(
            ['loan_reference' => 'LON-2026-9002'],
            [
                'employee_id' => $empSadia->id,
                'amount_requested' => 160000,
                'amount_approved' => null,
                'repayment_months' => null,
                'emi_amount' => null,
                'start_month' => null,
                'reason' => 'Personal electronics purchase.',
                'status' => 'rejected',
                'amount_remaining' => null,
                'admin_note' => 'Rejected due to policy eligibility and non-critical purpose.',
                'reviewed_by' => $admin->id,
                'reviewed_at' => Carbon::now()->subDays(20),
            ]
        );

        $activeStart = Carbon::now()->subMonths(4)->startOfMonth();
        $active = Loan::query()->updateOrCreate(
            ['loan_reference' => 'LON-2026-9003'],
            [
                'employee_id' => $empFahim->id,
                'amount_requested' => 180000,
                'amount_approved' => 180000,
                'repayment_months' => 12,
                'emi_amount' => 15000,
                'start_month' => $activeStart->toDateString(),
                'reason' => 'Home renovation and relocation support.',
                'status' => 'active',
                'amount_remaining' => 120000,
                'admin_note' => 'Approved under standard policy.',
                'reviewed_by' => $admin->id,
                'reviewed_at' => $activeStart->copy()->subDays(5),
            ]
        );

        $completedStart = Carbon::now()->subMonths(8)->startOfMonth();
        $completed = Loan::query()->updateOrCreate(
            ['loan_reference' => 'LON-2025-7801'],
            [
                'employee_id' => $empNabila->id,
                'amount_requested' => 60000,
                'amount_approved' => 60000,
                'repayment_months' => 6,
                'emi_amount' => 10000,
                'start_month' => $completedStart->toDateString(),
                'reason' => 'Skill development certification and exams.',
                'status' => 'completed',
                'amount_remaining' => 0,
                'admin_note' => 'Completed successfully.',
                'reviewed_by' => $admin->id,
                'reviewed_at' => $completedStart->copy()->subDays(4),
            ]
        );

        $this->seedRepayments($active, $activeStart, 4, 15000);
        $this->seedRepayments($completed, $completedStart, 6, 10000);

        Loan::query()->whereKey($pending->id)->update([
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(3),
        ]);

        Loan::query()->whereKey($rejected->id)->update([
            'created_at' => Carbon::now()->subDays(35),
            'updated_at' => Carbon::now()->subDays(20),
        ]);

        Loan::query()->whereKey($active->id)->update([
            'created_at' => $activeStart->copy()->subDays(6),
            'updated_at' => Carbon::now()->subDays(10),
        ]);

        Loan::query()->whereKey($completed->id)->update([
            'created_at' => $completedStart->copy()->subDays(6),
            'updated_at' => Carbon::now()->subMonths(4),
        ]);
    }

    private function seedRepayments(Loan $loan, Carbon $startMonth, int $months, float $amount): void
    {
        for ($i = 0; $i < $months; $i++) {
            $month = $startMonth->copy()->addMonths($i)->toDateString();

            LoanRepayment::query()->updateOrCreate(
                [
                    'loan_id' => $loan->id,
                    'month' => $month,
                ],
                [
                    'amount_paid' => $amount,
                    'created_at' => Carbon::parse($month)->endOfMonth(),
                ]
            );
        }
    }
}
