<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LoanSeeder extends Seeder
{
    public function run(): void
    {
        $admin  = User::query()->where('email', 'admin@auxfin.local')->firstOrFail();
        $base   = Carbon::now()->startOfMonth();

        $empSadia  = Employee::query()->where('employee_code', 'EMP-0101')->firstOrFail();
        $empFahim  = Employee::query()->where('employee_code', 'EMP-0102')->firstOrFail();
        $empNabila = Employee::query()->where('employee_code', 'EMP-0103')->firstOrFail();
        $empKarim  = Employee::query()->where('employee_code', 'EMP-0104')->firstOrFail();
        $empTania  = Employee::query()->where('employee_code', 'EMP-0105')->firstOrFail();

        // ── 1. PENDING loan (Sadia – awaiting admin review) ─────────────
        Loan::query()->updateOrCreate(
            ['loan_reference' => 'LON-2026-9001'],
            [
                'employee_id'      => $empSadia->id,
                'amount_requested' => 90000.00,
                'amount_approved'  => null,
                'repayment_months' => null,
                'emi_amount'       => null,
                'start_month'      => null,
                'reason'           => 'Medical emergency and family support requirement.',
                'status'           => 'pending',
                'amount_remaining' => null,
                'admin_note'       => null,
                'reviewed_by'      => null,
                'reviewed_at'      => null,
                'created_at'       => $base->copy()->subDays(3),
                'updated_at'       => $base->copy()->subDays(3),
            ]
        );

        // ── 2. REJECTED loan (Sadia – excessive amount) ─────────────────
        Loan::query()->updateOrCreate(
            ['loan_reference' => 'LON-2026-9002'],
            [
                'employee_id'      => $empSadia->id,
                'amount_requested' => 200000.00,
                'amount_approved'  => null,
                'repayment_months' => null,
                'emi_amount'       => null,
                'start_month'      => null,
                'reason'           => 'Personal electronics and home renovation.',
                'status'           => 'rejected',
                'amount_remaining' => null,
                'admin_note'       => 'Rejected: amount exceeds 3× monthly salary policy limit.',
                'reviewed_by'      => $admin->id,
                'reviewed_at'      => $base->copy()->subDays(20),
                'created_at'       => $base->copy()->subDays(35),
                'updated_at'       => $base->copy()->subDays(20),
            ]
        );

        // ── 3. ACTIVE loan (Fahim – 4 repayments done, 8 remaining) ─────
        $fahimStart = $base->copy()->subMonths(4);
        $activeLoan = Loan::query()->updateOrCreate(
            ['loan_reference' => 'LON-2026-9003'],
            [
                'employee_id'      => $empFahim->id,
                'amount_requested' => 180000.00,
                'amount_approved'  => 180000.00,
                'repayment_months' => 12,
                'emi_amount'       => 15000.00,
                'start_month'      => $fahimStart->toDateString(),
                'reason'           => 'Home renovation and relocation support.',
                'status'           => 'active',
                'amount_remaining' => 120000.00,
                'admin_note'       => 'Approved under standard policy. 3× salary limit met.',
                'reviewed_by'      => $admin->id,
                'reviewed_at'      => $fahimStart->copy()->subDays(5),
                'created_at'       => $fahimStart->copy()->subDays(6),
                'updated_at'       => Carbon::now()->subDays(10),
            ]
        );
        $this->seedRepayments($activeLoan, $fahimStart, 4, 15000.00);

        // ── 4. COMPLETED loan (Nabila – all 6 months repaid) ────────────
        $nabilaStart = $base->copy()->subMonths(8);
        $completedLoan = Loan::query()->updateOrCreate(
            ['loan_reference' => 'LON-2025-7801'],
            [
                'employee_id'      => $empNabila->id,
                'amount_requested' => 60000.00,
                'amount_approved'  => 60000.00,
                'repayment_months' => 6,
                'emi_amount'       => 10000.00,
                'start_month'      => $nabilaStart->toDateString(),
                'reason'           => 'Skill development certification and professional exams.',
                'status'           => 'completed',
                'amount_remaining' => 0.00,
                'admin_note'       => 'Approved and fully repaid.',
                'reviewed_by'      => $admin->id,
                'reviewed_at'      => $nabilaStart->copy()->subDays(4),
                'created_at'       => $nabilaStart->copy()->subDays(5),
                'updated_at'       => $base->copy()->subMonths(2),
            ]
        );
        $this->seedRepayments($completedLoan, $nabilaStart, 6, 10000.00);

        // ── 5. APPROVED loan (Karim – approved but not yet started) ─────
        Loan::query()->updateOrCreate(
            ['loan_reference' => 'LON-2026-9004'],
            [
                'employee_id'      => $empKarim->id,
                'amount_requested' => 120000.00,
                'amount_approved'  => 110000.00,
                'repayment_months' => 10,
                'emi_amount'       => 11000.00,
                'start_month'      => $base->copy()->addMonth()->toDateString(),
                'reason'           => 'Vehicle purchase for client visits.',
                'status'           => 'approved',
                'amount_remaining' => 110000.00,
                'admin_note'       => 'Approved with adjusted amount. EMI starts next month.',
                'reviewed_by'      => $admin->id,
                'reviewed_at'      => Carbon::now()->subDays(2),
                'created_at'       => Carbon::now()->subDays(7),
                'updated_at'       => Carbon::now()->subDays(2),
            ]
        );

        // ── 6. PENDING loan (Tania – just applied) ──────────────────────
        Loan::query()->updateOrCreate(
            ['loan_reference' => 'LON-2026-9005'],
            [
                'employee_id'      => $empTania->id,
                'amount_requested' => 50000.00,
                'amount_approved'  => null,
                'repayment_months' => null,
                'emi_amount'       => null,
                'start_month'      => null,
                'reason'           => 'Educational expense for part-time MBA programme.',
                'status'           => 'pending',
                'amount_remaining' => null,
                'admin_note'       => null,
                'reviewed_by'      => null,
                'reviewed_at'      => null,
                'created_at'       => Carbon::now()->subDay(),
                'updated_at'       => Carbon::now()->subDay(),
            ]
        );
    }

    private function seedRepayments(Loan $loan, Carbon $startMonth, int $months, float $amount): void
    {
        for ($i = 0; $i < $months; $i++) {
            $month = $startMonth->copy()->addMonths($i)->toDateString();

            LoanRepayment::query()->updateOrCreate(
                ['loan_id' => $loan->id, 'month' => $month],
                [
                    'amount_paid' => $amount,
                    'created_at'  => Carbon::parse($month)->endOfMonth()->subDay(),
                    'updated_at'  => Carbon::parse($month)->endOfMonth()->subDay(),
                ]
            );
        }
    }
}
