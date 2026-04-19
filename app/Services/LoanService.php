<?php

namespace App\Services;

use App\Events\LoanApplied;
use App\Events\LoanApproved;
use App\Events\LoanRejected;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoanService
{
    public function getPolicy(): array
    {
        return $this->getLoanPolicy();
    }

    public function apply(Employee $employee, array $payload): Loan
    {
        $policy = $this->getLoanPolicy();

        $activeLoanCount = Loan::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved', 'active'])
            ->count();

        if ($activeLoanCount >= (int) $policy['concurrent_loans']) {
            abort(422, 'Employee already has the maximum allowed active or pending loans.');
        }

        $maxAmount = (float) $employee->basic_salary * (float) $policy['max_loan_multiplier'];
        if ((float) $payload['amount_requested'] > $maxAmount) {
            abort(422, sprintf('Requested amount exceeds policy limit of %.2f.', $maxAmount));
        }

        if (! empty($payload['preferred_repayment_months'])
            && (int) $payload['preferred_repayment_months'] > (int) $policy['max_repayment_months']) {
            abort(422, sprintf(
                'Preferred repayment months cannot exceed %d.',
                (int) $policy['max_repayment_months']
            ));
        }

        $coolingMonths = (int) $policy['cooling_period_months'];
        if ($coolingMonths > 0) {
            $latestCompleted = Loan::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'completed')
                ->latest('updated_at')
                ->first();

            if ($latestCompleted) {
                $eligibleAt = Carbon::parse($latestCompleted->updated_at)->addMonths($coolingMonths);
                if ($eligibleAt->isFuture()) {
                    abort(422, 'Cooling period is still active. Next eligibility date: '.$eligibleAt->toDateString().'.');
                }
            }
        }

        $loan = Loan::query()->create([
            'employee_id' => $employee->id,
            'loan_reference' => $this->generateReference(),
            'amount_requested' => $payload['amount_requested'],
            'reason' => $payload['reason'],
            'status' => 'pending',
        ]);

        event(new LoanApplied([
            'loan_id' => $loan->id,
            'employee_id' => $employee->id,
            'amount_requested' => (float) $loan->amount_requested,
        ]));

        return $loan;
    }

    public function approve(Loan $loan, array $payload, User $admin): Loan
    {
        if ($loan->status !== 'pending') {
            abort(422, 'Only pending loans can be approved.');
        }

        $policy = $this->getLoanPolicy();
        $approved = (float) $payload['amount_approved'];
        $months = (int) $payload['repayment_months'];

        $maxAmount = (float) $loan->employee->basic_salary * (float) $policy['max_loan_multiplier'];
        if ($approved > $maxAmount) {
            abort(422, sprintf('Approved amount exceeds policy limit of %.2f.', $maxAmount));
        }

        if ($approved > (float) $loan->amount_requested) {
            abort(422, 'Approved amount cannot exceed requested amount.');
        }

        if ($months > (int) $policy['max_repayment_months']) {
            abort(422, sprintf(
                'Repayment months cannot exceed policy limit of %d.',
                (int) $policy['max_repayment_months']
            ));
        }

        $emi = $months > 0 ? $approved / $months : 0;

        $loan->update([
            'amount_approved' => $approved,
            'repayment_months' => $months,
            'emi_amount' => round($emi, 2),
            'start_month' => Carbon::parse($payload['start_month'])->startOfMonth()->toDateString(),
            'amount_remaining' => $approved,
            'status' => 'approved',
            'admin_note' => $payload['admin_note'] ?? null,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        event(new LoanApproved($loan->employee_id, [
            'loan_id' => $loan->id,
            'amount_approved' => (float) $loan->amount_approved,
            'emi_amount' => (float) $loan->emi_amount,
        ]));

        return $loan->refresh();
    }

    public function reject(Loan $loan, string $adminNote, User $admin): Loan
    {
        if ($loan->status !== 'pending') {
            abort(422, 'Only pending loans can be rejected.');
        }

        $loan->update([
            'status' => 'rejected',
            'admin_note' => $adminNote,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        event(new LoanRejected($loan->employee_id, [
            'loan_id' => $loan->id,
            'admin_note' => $adminNote,
        ]));

        return $loan->refresh();
    }

    public function applyMonthlyDeduction(Employee $employee, string $month): void
    {
        DB::transaction(function () use ($employee, $month): void {
            $targetMonth = Carbon::parse($month)->startOfMonth()->toDateString();

            /** @var Loan|null $loan */
            $loan = Loan::query()
                ->where('employee_id', $employee->id)
                ->whereIn('status', ['approved', 'active'])
                ->whereDate('start_month', '<=', $targetMonth)
                ->where('amount_remaining', '>', 0)
                ->orderBy('id')
                ->first();

            if (! $loan) {
                return;
            }

            $emi = (float) $loan->emi_amount;
            $remaining = max(0, (float) $loan->amount_remaining - $emi);
            $paidAmount = min((float) $loan->amount_remaining, $emi);

            LoanRepayment::query()->firstOrCreate(
                [
                    'loan_id' => $loan->id,
                    'month' => $targetMonth,
                ],
                [
                    'amount_paid' => $paidAmount,
                    'created_at' => now(),
                ]
            );

            $loan->update([
                'amount_remaining' => round($remaining, 2),
                'status' => $remaining <= 0 ? 'completed' : 'active',
            ]);
        });
    }

    private function generateReference(): string
    {
        $year = now()->format('Y');
        $next = (int) Loan::query()->whereYear('created_at', now()->year)->count() + 1;

        return sprintf('LON-%s-%04d', $year, $next);
    }

    private function getLoanPolicy(): array
    {
        $defaults = [
            'max_loan_multiplier' => 3,
            'max_repayment_months' => 12,
            'cooling_period_months' => 3,
            'concurrent_loans' => 1,
        ];

        $value = Setting::getValue('loan_policy', $defaults);
        if (! is_array($value)) {
            return $defaults;
        }

        $merged = array_merge($defaults, $value);

        return [
            'max_loan_multiplier' => max(1, (int) ($merged['max_loan_multiplier'] ?? 3)),
            'max_repayment_months' => max(1, (int) ($merged['max_repayment_months'] ?? 12)),
            'cooling_period_months' => max(0, (int) ($merged['cooling_period_months'] ?? 3)),
            'concurrent_loans' => max(1, (int) ($merged['concurrent_loans'] ?? 1)),
        ];
    }
}
