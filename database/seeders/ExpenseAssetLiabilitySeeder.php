<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\Liability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ExpenseAssetLiabilitySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@auxfin.local')->firstOrFail();
        $base  = Carbon::now()->startOfMonth();

        // ─────────────────────────────────────────────
        //  EXPENSES  (recurring + one-time + accrual)
        // ─────────────────────────────────────────────
        $expenseDefs = [
            // ── Recurring monthly ──
            [
                'category'        => 'Office Rent',
                'description'     => 'Head office monthly rent – Gulshan Commercial Tower',
                'amount'          => 120000.00,
                'expense_date'    => $base->copy()->toDateString(),
                'accounting_mode' => 'cash',
                'is_recurring'    => true,
                'recurrence'      => 'monthly',
                'next_due_date'   => $base->copy()->addMonth()->toDateString(),
            ],
            [
                'category'        => 'Cloud Infrastructure',
                'description'     => 'AWS EC2, RDS, S3 + CloudFront monthly bill',
                'amount'          => 68000.00,
                'expense_date'    => $base->copy()->addDays(3)->toDateString(),
                'accounting_mode' => 'cash',
                'is_recurring'    => true,
                'recurrence'      => 'monthly',
                'next_due_date'   => $base->copy()->addMonth()->addDays(3)->toDateString(),
            ],
            [
                'category'        => 'Internet & Telecom',
                'description'     => 'Corporate fibre bandwidth + SIP trunking',
                'amount'          => 14000.00,
                'expense_date'    => $base->copy()->addDays(5)->toDateString(),
                'accounting_mode' => 'cash',
                'is_recurring'    => true,
                'recurrence'      => 'monthly',
                'next_due_date'   => $base->copy()->addMonth()->addDays(5)->toDateString(),
            ],
            [
                'category'        => 'Office Utilities',
                'description'     => 'Electricity, water, gas – head office',
                'amount'          => 22000.00,
                'expense_date'    => $base->copy()->addDays(7)->toDateString(),
                'accounting_mode' => 'cash',
                'is_recurring'    => true,
                'recurrence'      => 'monthly',
                'next_due_date'   => $base->copy()->addMonth()->addDays(7)->toDateString(),
            ],
            [
                'category'        => 'Software Subscriptions',
                'description'     => 'Jira, Figma, Slack, GitHub enterprise licences',
                'amount'          => 18500.00,
                'expense_date'    => $base->copy()->addDays(1)->toDateString(),
                'accounting_mode' => 'cash',
                'is_recurring'    => true,
                'recurrence'      => 'monthly',
                'next_due_date'   => $base->copy()->addMonth()->addDays(1)->toDateString(),
            ],
            // ── Recurring quarterly ──
            [
                'category'        => 'Legal & Compliance',
                'description'     => 'Company secretarial and regulatory compliance fees',
                'amount'          => 45000.00,
                'expense_date'    => $base->copy()->subMonths(1)->toDateString(),
                'accounting_mode' => 'cash',
                'is_recurring'    => true,
                'recurrence'      => 'quarterly',
                'next_due_date'   => $base->copy()->addMonths(2)->toDateString(),
            ],
            [
                'category'        => 'Accounting & Audit',
                'description'     => 'External audit firm quarterly retainer',
                'amount'          => 60000.00,
                'expense_date'    => $base->copy()->subMonths(2)->toDateString(),
                'accounting_mode' => 'cash',
                'is_recurring'    => true,
                'recurrence'      => 'quarterly',
                'next_due_date'   => $base->copy()->addMonth()->toDateString(),
            ],
            // ── Recurring yearly ──
            [
                'category'        => 'Business Insurance',
                'description'     => 'Annual corporate liability insurance premium',
                'amount'          => 180000.00,
                'expense_date'    => $base->copy()->subMonths(4)->toDateString(),
                'accounting_mode' => 'prepaid',
                'prepaid_start_date' => $base->copy()->subMonths(4)->toDateString(),
                'prepaid_months'  => 12,
                'is_recurring'    => true,
                'recurrence'      => 'yearly',
                'next_due_date'   => $base->copy()->addMonths(8)->toDateString(),
            ],
            // ── One-time expenses ──
            [
                'category'        => 'Travel',
                'description'     => 'Client implementation visit – Acme Logistics site',
                'amount'          => 26000.00,
                'expense_date'    => $base->copy()->subMonths(1)->addDays(9)->toDateString(),
                'accounting_mode' => 'cash',
                'is_recurring'    => false,
            ],
            [
                'category'        => 'Hardware',
                'description'     => 'Developer workstation GPU upgrades – 3 units',
                'amount'          => 91000.00,
                'expense_date'    => $base->copy()->subMonths(2)->addDays(12)->toDateString(),
                'accounting_mode' => 'cash',
                'is_recurring'    => false,
            ],
            [
                'category'        => 'Marketing',
                'description'     => 'Digital product webinar campaign – Google + LinkedIn',
                'amount'          => 35000.00,
                'expense_date'    => $base->copy()->subMonths(2)->addDays(16)->toDateString(),
                'accounting_mode' => 'cash',
                'is_recurring'    => false,
            ],
            [
                'category'        => 'Training',
                'description'     => 'Team Laravel & Vue.js advanced workshop – external trainer',
                'amount'          => 42000.00,
                'expense_date'    => $base->copy()->subMonths(3)->addDays(10)->toDateString(),
                'accounting_mode' => 'cash',
                'is_recurring'    => false,
            ],
            [
                'category'        => 'Office Supplies',
                'description'     => 'Stationery and ergonomic accessories – bulk purchase',
                'amount'          => 15000.00,
                'expense_date'    => $base->copy()->subMonths(1)->addDays(5)->toDateString(),
                'accounting_mode' => 'cash',
                'is_recurring'    => false,
            ],
            // ── Payable (accrued but not yet paid) ──
            [
                'category'           => 'Consulting',
                'description'        => 'External UX consultant fee – Digital Lending Portal',
                'amount'             => 55000.00,
                'expense_date'       => $base->copy()->toDateString(),
                'accounting_mode'    => 'payable',
                'payable_due_date'   => $base->copy()->addDays(15)->toDateString(),
                'is_recurring'       => false,
            ],
        ];

        foreach ($expenseDefs as $row) {
            $expense = Expense::query()->updateOrCreate(
                ['description' => $row['description']],
                [
                    'category'           => $row['category'],
                    'amount'             => $row['amount'],
                    'expense_date'       => $row['expense_date'],
                    'accounting_mode'    => $row['accounting_mode'] ?? 'cash',
                    'payable_due_date'   => $row['payable_due_date'] ?? null,
                    'prepaid_start_date' => $row['prepaid_start_date'] ?? null,
                    'prepaid_months'     => $row['prepaid_months'] ?? null,
                    'is_recurring'       => $row['is_recurring'],
                    'recurrence'         => $row['recurrence'] ?? null,
                    'next_due_date'      => $row['next_due_date'] ?? null,
                    'created_by'         => $admin->id,
                ]
            );

            // ── ExpensePayment for cash-mode expenses ────────────────────
            if (($row['accounting_mode'] ?? 'cash') === 'cash') {
                ExpensePayment::query()->updateOrCreate(
                    ['expense_id' => $expense->id],
                    [
                        'recorded_by'      => $admin->id,
                        'payment_date'     => $row['expense_date'],
                        'amount'           => $row['amount'],
                        'payment_method'   => 'bank_transfer',
                        'reference_number' => 'PAY-EXP-' . $expense->id,
                        'notes'            => 'Auto-payment for ' . $row['category'],
                    ]
                );
            }

            // ── Partial payment for prepaid insurance ────────────────────
            if (($row['accounting_mode'] ?? 'cash') === 'prepaid') {
                ExpensePayment::query()->updateOrCreate(
                    ['expense_id' => $expense->id],
                    [
                        'recorded_by'      => $admin->id,
                        'payment_date'     => $row['expense_date'],
                        'amount'           => $row['amount'],
                        'payment_method'   => 'bank_transfer',
                        'reference_number' => 'PAY-PRE-' . $expense->id,
                        'notes'            => 'Annual prepaid payment',
                    ]
                );
            }
        }

        // ─────────────────────────────────────────────
        //  LIABILITIES
        // ─────────────────────────────────────────────
        $liabilityDefs = [
            [
                'name'             => 'Term Loan – Business Expansion',
                'principal_amount' => 2500000.00,
                'outstanding'      => 1650000.00,
                'interest_rate'    => 9.50,
                'monthly_payment'  => 85000.00,
                'start_date'       => $base->copy()->subMonths(10)->toDateString(),
                'end_date'         => $base->copy()->addMonths(20)->toDateString(),
                'next_due_date'    => $base->copy()->addDays(5)->toDateString(),
                'status'           => 'active',
            ],
            [
                'name'             => 'Equipment Leasing – Server Hardware',
                'principal_amount' => 450000.00,
                'outstanding'      => 180000.00,
                'interest_rate'    => 7.20,
                'monthly_payment'  => 30000.00,
                'start_date'       => $base->copy()->subMonths(9)->toDateString(),
                'end_date'         => $base->copy()->addMonths(3)->toDateString(),
                'next_due_date'    => $base->copy()->addDays(10)->toDateString(),
                'status'           => 'active',
            ],
            [
                'name'             => 'Office Vehicle Financing',
                'principal_amount' => 350000.00,
                'outstanding'      => 0.00,
                'interest_rate'    => 8.00,
                'monthly_payment'  => 19000.00,
                'start_date'       => $base->copy()->subMonths(19)->toDateString(),
                'end_date'         => $base->copy()->subMonths(1)->toDateString(),
                'next_due_date'    => null,
                'status'           => 'completed',
            ],
            [
                'name'             => 'Credit Line – Working Capital',
                'principal_amount' => 800000.00,
                'outstanding'      => 320000.00,
                'interest_rate'    => 11.00,
                'monthly_payment'  => 45000.00,
                'start_date'       => $base->copy()->subMonths(6)->toDateString(),
                'end_date'         => $base->copy()->addMonths(6)->toDateString(),
                'next_due_date'    => $base->copy()->addDays(3)->toDateString(),
                'status'           => 'active',
            ],
        ];

        foreach ($liabilityDefs as $row) {
            Liability::query()->updateOrCreate(['name' => $row['name']], $row);
        }

        // ─────────────────────────────────────────────
        //  ASSETS
        // ─────────────────────────────────────────────
        $assetDefs = [
            [
                'name'                => 'Primary Application Server (Dell PowerEdge)',
                'category'            => 'IT Infrastructure',
                'purchase_date'       => $base->copy()->subMonths(14)->toDateString(),
                'purchase_cost'       => 420000.00,
                'current_book_value'  => 322000.00,
                'useful_life_months'  => 60,
                'monthly_depreciation'=> 7000.00,
                'status'              => 'active',
            ],
            [
                'name'                => 'Developer Workstations – Batch A (5 units)',
                'category'            => 'Hardware',
                'purchase_date'       => $base->copy()->subMonths(17)->toDateString(),
                'purchase_cost'       => 520000.00,
                'current_book_value'  => 371667.00,
                'useful_life_months'  => 36,
                'monthly_depreciation'=> 14444.44,
                'status'              => 'active',
            ],
            [
                'name'                => 'Developer Workstations – Batch B (3 units)',
                'category'            => 'Hardware',
                'purchase_date'       => $base->copy()->subMonths(5)->toDateString(),
                'purchase_cost'       => 330000.00,
                'current_book_value'  => 302500.00,
                'useful_life_months'  => 36,
                'monthly_depreciation'=> 9166.67,
                'status'              => 'active',
            ],
            [
                'name'                => 'Office Furniture & Fitout',
                'category'            => 'Furniture',
                'purchase_date'       => $base->copy()->subMonths(21)->toDateString(),
                'purchase_cost'       => 160000.00,
                'current_book_value'  => 93333.00,
                'useful_life_months'  => 48,
                'monthly_depreciation'=> 3333.33,
                'status'              => 'active',
            ],
            [
                'name'                => 'Company Vehicle – Toyota Noah',
                'category'            => 'Vehicles',
                'purchase_date'       => $base->copy()->subMonths(30)->toDateString(),
                'purchase_cost'       => 3200000.00,
                'current_book_value'  => 2240000.00,
                'useful_life_months'  => 100,
                'monthly_depreciation'=> 32000.00,
                'status'              => 'active',
            ],
            [
                'name'                => 'Legacy Network Switches',
                'category'            => 'Networking',
                'purchase_date'       => $base->copy()->subMonths(40)->toDateString(),
                'purchase_cost'       => 90000.00,
                'current_book_value'  => 0.00,
                'useful_life_months'  => 36,
                'monthly_depreciation'=> 2500.00,
                'status'              => 'fully_depreciated',
            ],
            [
                'name'                => 'Old UPS System',
                'category'            => 'IT Infrastructure',
                'purchase_date'       => $base->copy()->subMonths(50)->toDateString(),
                'purchase_cost'       => 75000.00,
                'current_book_value'  => 0.00,
                'useful_life_months'  => 36,
                'monthly_depreciation'=> 2083.33,
                'status'              => 'disposed',
            ],
        ];

        foreach ($assetDefs as $row) {
            Asset::query()->updateOrCreate(['name' => $row['name']], $row);
        }
    }
}
