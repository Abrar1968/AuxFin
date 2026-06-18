<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\CompanySnapshot;
use App\Models\Employee;
use App\Models\EmployeeMessage;
use App\Models\MessageRead;
use App\Models\SalaryMonth;
use App\Models\User;
use App\Services\SnapshotService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MessagesSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        $admin     = User::query()->where('email', 'admin@auxfin.local')->firstOrFail();
        $owner     = User::query()->where('email', 'owner@auxfin.local')->firstOrFail();
        $employees = Employee::query()->with('user')->get();
        $base      = Carbon::now()->startOfMonth();

        // ─────────────────────────────────────────────
        //  EMPLOYEE MESSAGES (all 5 employees)
        // ─────────────────────────────────────────────
        $messageTemplates = [
            // EMP-0101 Sadia
            'EMP-0101' => [
                [
                    'subject'    => 'Late attendance clarification – traffic delay',
                    'type'       => 'late_appeal',
                    'body'       => 'I was marked late on ' . $base->copy()->addDays(2)->format('d M Y') . ' due to an unexpected road blockage near Gulshan 1. Attached is the Google Maps screenshot showing the traffic situation at 9:15 AM. Request to mark as excused.',
                    'status'     => 'open',
                    'priority'   => 'normal',
                    'action_taken' => 'none',
                    'reference_date' => $base->copy()->addDays(2)->toDateString(),
                    'reference_month' => $base->toDateString(),
                    'admin_reply' => null,
                ],
                [
                    'subject'    => 'Deduction dispute – previous month penalty reversed',
                    'type'       => 'deduction_dispute',
                    'body'       => 'The late penalty deducted in the previous month was incorrect. I have the log-in records showing I checked in at 9:03 AM. Please review and reverse the deduction.',
                    'status'     => 'resolved',
                    'priority'   => 'high',
                    'action_taken' => 'deduction_reversed',
                    'reference_month' => $base->copy()->subMonth()->toDateString(),
                    'admin_reply' => 'Reviewed and confirmed. The late marking was a system error. Deduction of BDT 3,800 has been reversed in the current payroll.',
                    'replied_at' => $base->copy()->subDays(5),
                    'resolved_at' => $base->copy()->subDays(5),
                ],
                [
                    'subject'    => 'Salary query – festival bonus calculation',
                    'type'       => 'salary_query',
                    'body'       => 'Could you please confirm how the festival bonus is calculated and what criteria determines eligibility? The Eid bonus this year appears different from my expectation.',
                    'status'     => 'under_review',
                    'priority'   => 'normal',
                    'action_taken' => 'noted',
                    'reference_month' => $base->copy()->subMonths(2)->toDateString(),
                    'admin_reply' => 'Festival bonus is calculated as per HR policy 3.2 – one month basic salary for employees with > 1 year service. We will share a detailed breakdown shortly.',
                    'replied_at' => Carbon::now()->subDays(2),
                ],
            ],
            // EMP-0102 Fahim
            'EMP-0102' => [
                [
                    'subject'    => 'Loan repayment schedule query',
                    'type'       => 'loan_query',
                    'body'       => 'I would like to request the updated loan repayment schedule with remaining balance and projected completion date for my active loan LON-2026-9003.',
                    'status'     => 'under_review',
                    'priority'   => 'normal',
                    'action_taken' => 'noted',
                    'admin_reply' => 'Loan LON-2026-9003: EMI BDT 15,000/month, 8 instalments remaining, projected completion ' . $base->copy()->addMonths(8)->format('M Y') . '.',
                    'replied_at' => Carbon::now()->subDays(3),
                ],
                [
                    'subject'    => 'Leave clarification – unpaid leave deduction',
                    'type'       => 'leave_clarification',
                    'body'       => 'My last salary slip shows an unpaid leave deduction but my leave was marked as approved casual leave. Please clarify.',
                    'status'     => 'resolved',
                    'priority'   => 'high',
                    'action_taken' => 'salary_adjusted',
                    'reference_month' => $base->copy()->subMonths(1)->toDateString(),
                    'admin_reply' => 'This has been corrected. The casual leave was incorrectly tagged as unpaid in the system. Adjustment will reflect in the next payroll.',
                    'replied_at' => Carbon::now()->subDays(8),
                    'resolved_at' => Carbon::now()->subDays(8),
                ],
            ],
            // EMP-0103 Nabila
            'EMP-0103' => [
                [
                    'subject'    => 'General HR – work from home policy query',
                    'type'       => 'general_hr',
                    'body'       => 'Could you please share the updated WFH policy document? I need to submit a WFH request for next week due to a planned minor surgery recovery.',
                    'status'     => 'resolved',
                    'priority'   => 'normal',
                    'action_taken' => 'noted',
                    'admin_reply' => 'WFH policy document has been shared on the company portal under HR Policies. You are approved for WFH for 3 days next week. Please coordinate with your team lead.',
                    'replied_at' => Carbon::now()->subDays(4),
                    'resolved_at' => Carbon::now()->subDays(4),
                ],
                [
                    'subject'    => 'Attendance correction – holiday marked as absent',
                    'type'       => 'late_appeal',
                    'body'       => 'I was marked absent on ' . $base->copy()->subMonths(1)->addDays(14)->format('d M Y') . ' which was a declared public holiday. Please correct the attendance record.',
                    'status'     => 'resolved',
                    'priority'   => 'normal',
                    'action_taken' => 'mark_excused',
                    'reference_date' => $base->copy()->subMonths(1)->addDays(14)->toDateString(),
                    'admin_reply' => 'Confirmed – that date was a gazetted public holiday. Attendance has been corrected to holiday status.',
                    'replied_at' => Carbon::now()->subDays(12),
                    'resolved_at' => Carbon::now()->subDays(12),
                ],
            ],
            // EMP-0104 Karim
            'EMP-0104' => [
                [
                    'subject'    => 'Salary query – conveyance allowance update',
                    'type'       => 'salary_query',
                    'body'       => 'My designation was updated to Marketing Manager last quarter. I understand the conveyance allowance is revised accordingly. Could you confirm the updated salary structure effective date?',
                    'status'     => 'open',
                    'priority'   => 'normal',
                    'action_taken' => 'none',
                    'admin_reply' => null,
                ],
            ],
            // EMP-0105 Tania
            'EMP-0105' => [
                [
                    'subject'    => 'General HR – confirmation letter request',
                    'type'       => 'general_hr',
                    'body'       => 'I require an official employment confirmation letter for bank loan processing. The letter should confirm my designation, joining date, and current gross salary.',
                    'status'     => 'resolved',
                    'priority'   => 'normal',
                    'action_taken' => 'noted',
                    'admin_reply' => 'Your employment confirmation letter has been prepared and sent to your official email. It has been signed by the HR head and stamped.',
                    'replied_at' => Carbon::now()->subDays(1),
                    'resolved_at' => Carbon::now()->subDays(1),
                ],
                [
                    'subject'    => 'Late attendance – transport breakdown',
                    'type'       => 'late_appeal',
                    'body'       => 'I arrived 40 minutes late on ' . $base->copy()->addDays(3)->format('d M Y') . ' due to a CNG breakdown. I have the vehicle receipt as proof. Requesting to excuse this late mark.',
                    'status'     => 'open',
                    'priority'   => 'normal',
                    'action_taken' => 'none',
                    'reference_date' => $base->copy()->addDays(3)->toDateString(),
                    'admin_reply' => null,
                ],
            ],
        ];

        $createdMessages = [];

        foreach ($messageTemplates as $empCode => $msgs) {
            $emp = Employee::query()->where('employee_code', $empCode)->firstOrFail();

            foreach ($msgs as $m) {
                $msg = EmployeeMessage::query()->updateOrCreate(
                    ['employee_id' => $emp->id, 'subject' => $m['subject']],
                    [
                        'type'            => $m['type'],
                        'body'            => $m['body'],
                        'reference_date'  => $m['reference_date'] ?? null,
                        'reference_month' => $m['reference_month'] ?? null,
                        'status'          => $m['status'],
                        'priority'        => $m['priority'],
                        'action_taken'    => $m['action_taken'],
                        'admin_reply'     => $m['admin_reply'] ?? null,
                        'replied_by'      => isset($m['admin_reply']) && $m['admin_reply'] ? $admin->id : null,
                        'replied_at'      => $m['replied_at'] ?? null,
                        'resolved_at'     => $m['resolved_at'] ?? null,
                    ]
                );

                // Mark admin-read for replied messages
                if (isset($m['admin_reply']) && $m['admin_reply']) {
                    MessageRead::query()->updateOrCreate(
                        ['message_id' => $msg->id, 'user_id' => $admin->id],
                        ['read_at' => $m['replied_at'] ?? Carbon::now()->subDays(1)]
                    );
                    // Mark employee-read for resolved
                    if ($m['status'] === 'resolved') {
                        MessageRead::query()->updateOrCreate(
                            ['message_id' => $msg->id, 'user_id' => $emp->user_id],
                            ['read_at' => ($m['resolved_at'] ?? Carbon::now())->copy()->addHours(2)]
                        );
                    }
                }

                $createdMessages[] = $msg;
            }
        }

        // ─────────────────────────────────────────────
        //  COMPANY SNAPSHOTS  (6 months)
        // ─────────────────────────────────────────────

        // Try SnapshotService first; fall back to manual insert
        try {
            /** @var SnapshotService $snapshotSvc */
            $snapshotSvc = app(SnapshotService::class);
            $months = collect(range(0, 5))
                ->map(fn(int $o) => Carbon::now()->startOfMonth()->subMonths(5 - $o));
            foreach ($months as $monthDate) {
                $snapshotSvc->capture($monthDate->toDateString());
            }
        } catch (\Throwable) {
            // Manual realistic snapshots if service unavailable
            $snapshotData = [
                ['offset' => 5, 'revenue' => 850000,  'payroll' => 310000, 'opex' => 260000, 'ar' => 175000],
                ['offset' => 4, 'revenue' => 960000,  'payroll' => 315000, 'opex' => 275000, 'ar' => 210000],
                ['offset' => 3, 'revenue' => 1050000, 'payroll' => 320000, 'opex' => 285000, 'ar' => 240000],
                ['offset' => 2, 'revenue' => 920000,  'payroll' => 325000, 'opex' => 295000, 'ar' => 310000],
                ['offset' => 1, 'revenue' => 1180000, 'payroll' => 330000, 'opex' => 302000, 'ar' => 285000],
                ['offset' => 0, 'revenue' => 1250000, 'payroll' => 335000, 'opex' => 310000, 'ar' => 270000],
            ];

            $headcount = Employee::query()->count();

            foreach ($snapshotData as $s) {
                $snapshotMonth = $base->copy()->subMonths($s['offset'])->toDateString();
                $grossProfit   = $s['revenue'] - $s['payroll'];
                $netProfit     = $grossProfit - $s['opex'];
                $burnRate      = $s['payroll'] + $s['opex'];
                $availCash     = 2500000;
                $runway        = $burnRate > 0 ? round($availCash / $burnRate, 2) : 0;

                CompanySnapshot::query()->updateOrCreate(
                    ['snapshot_month' => $snapshotMonth],
                    [
                        'total_revenue'       => $s['revenue'],
                        'total_cash_collected' => $s['revenue'] * 0.85,
                        'total_payroll'       => $s['payroll'],
                        'total_opex'          => $s['opex'],
                        'gross_profit'        => $grossProfit,
                        'net_profit'          => $netProfit,
                        'burn_rate'           => $burnRate,
                        'cash_runway_months'  => $runway,
                        'headcount'           => $headcount,
                        'total_ar'            => $s['ar'],
                    ]
                );
            }
        }

        // ─────────────────────────────────────────────
        //  AUDIT LOGS
        // ─────────────────────────────────────────────
        $auditEntries = [
            ['user_id' => $admin->id, 'action' => 'auth.login',         'model_type' => 'User',         'model_id' => $admin->id,  'ip' => '192.168.1.10'],
            ['user_id' => $admin->id, 'action' => 'employee.created',   'model_type' => 'Employee',     'model_id' => 1,           'ip' => '192.168.1.10'],
            ['user_id' => $admin->id, 'action' => 'payroll.processed',  'model_type' => 'SalaryMonth',  'model_id' => 1,           'ip' => '192.168.1.10'],
            ['user_id' => $admin->id, 'action' => 'payroll.paid',       'model_type' => 'SalaryMonth',  'model_id' => 1,           'ip' => '192.168.1.10'],
            ['user_id' => $admin->id, 'action' => 'loan.approved',      'model_type' => 'Loan',         'model_id' => 1,           'ip' => '192.168.1.10'],
            ['user_id' => $admin->id, 'action' => 'leave.approved',     'model_type' => 'Leave',        'model_id' => 1,           'ip' => '192.168.1.10'],
            ['user_id' => $admin->id, 'action' => 'invoice.status',     'model_type' => 'Invoice',      'model_id' => 1,           'ip' => '192.168.1.10'],
            ['user_id' => $admin->id, 'action' => 'expense.created',    'model_type' => 'Expense',      'model_id' => 1,           'ip' => '192.168.1.10'],
            ['user_id' => $admin->id, 'action' => 'asset.depreciated',  'model_type' => 'Asset',        'model_id' => 1,           'ip' => '192.168.1.10'],
            ['user_id' => $owner->id, 'action' => 'settings.updated',   'model_type' => 'Setting',      'model_id' => null,        'ip' => '192.168.1.5'],
            ['user_id' => $admin->id, 'action' => 'message.replied',    'model_type' => 'EmployeeMessage', 'model_id' => 1,        'ip' => '192.168.1.10'],
            ['user_id' => $admin->id, 'action' => 'snapshot.captured',  'model_type' => 'CompanySnapshot', 'model_id' => 1,        'ip' => '127.0.0.1'],
        ];

        foreach ($auditEntries as $entry) {
            AuditLog::query()->create([
                'user_id'    => $entry['user_id'],
                'action'     => $entry['action'],
                'model_type' => $entry['model_type'],
                'model_id'   => $entry['model_id'],
                'old_values' => null,
                'new_values' => null,
                'ip_address' => $entry['ip'],
                'user_agent' => 'Mozilla/5.0 (seeder)',
            ]);
        }
    }
}
