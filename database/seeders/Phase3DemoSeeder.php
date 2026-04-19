<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Liability;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class Phase3DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@auxfin.local')->first()
            ?? User::query()->where('email', 'owner@auxfin.local')->first();

        if (! $admin) {
            return;
        }

        Setting::query()->updateOrCreate(
            ['key' => 'loan_policy'],
            ['value' => [
                'max_loan_multiplier' => 3,
                'max_repayment_months' => 12,
                'cooling_period_months' => 3,
                'concurrent_loans' => 1,
            ]]
        );

        $clients = [
            ['name' => 'Acme Logistics Ltd', 'email' => 'finance@acme-logistics.com', 'phone' => '+8801711000001', 'contact_person' => 'Rafiq Hasan'],
            ['name' => 'BlueWave Retail', 'email' => 'accounts@bluewave-retail.com', 'phone' => '+8801711000002', 'contact_person' => 'Mim Akter'],
            ['name' => 'GreenField Agro', 'email' => 'finance@greenfield-agro.com', 'phone' => '+8801711000003', 'contact_person' => 'Anik Sarker'],
            ['name' => 'Vertex Healthcare', 'email' => 'billing@vertex-health.com', 'phone' => '+8801711000004', 'contact_person' => 'Nitu Paul'],
        ];

        $clientMap = [];
        foreach ($clients as $row) {
            $clientMap[$row['name']] = Client::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'address' => 'Dhaka, Bangladesh',
                    'contact_person' => $row['contact_person'],
                ]
            );
        }

        $projects = [
            ['client' => 'Acme Logistics Ltd', 'name' => 'Fleet Cost Intelligence Platform', 'contract_amount' => 1250000, 'status' => 'active', 'start_date' => '2026-01-10'],
            ['client' => 'BlueWave Retail', 'name' => 'Retail ERP Rollout', 'contract_amount' => 980000, 'status' => 'active', 'start_date' => '2026-02-01'],
            ['client' => 'GreenField Agro', 'name' => 'Supply Chain Tracker', 'contract_amount' => 760000, 'status' => 'on_hold', 'start_date' => '2025-11-20'],
            ['client' => 'Vertex Healthcare', 'name' => 'Claims Automation Suite', 'contract_amount' => 1430000, 'status' => 'completed', 'start_date' => '2025-07-15', 'end_date' => '2026-03-28'],
        ];

        $projectMap = [];
        foreach ($projects as $row) {
            $projectMap[$row['name']] = Project::query()->updateOrCreate(
                [
                    'client_id' => $clientMap[$row['client']]->id,
                    'name' => $row['name'],
                ],
                [
                    'description' => 'Phase 3 seeded project for realistic finance dashboards.',
                    'contract_amount' => $row['contract_amount'],
                    'status' => $row['status'],
                    'start_date' => $row['start_date'],
                    'end_date' => $row['end_date'] ?? null,
                ]
            );
        }

        $invoices = [
            ['number' => 'INV-2026-0001', 'project' => 'Fleet Cost Intelligence Platform', 'amount' => 250000, 'due_date' => '2026-01-25', 'status' => 'paid', 'partial_amount' => 250000, 'paid_at' => '2026-01-23 14:00:00'],
            ['number' => 'INV-2026-0002', 'project' => 'Fleet Cost Intelligence Platform', 'amount' => 180000, 'due_date' => '2026-02-25', 'status' => 'partial', 'partial_amount' => 90000],
            ['number' => 'INV-2026-0003', 'project' => 'Fleet Cost Intelligence Platform', 'amount' => 220000, 'due_date' => '2026-03-25', 'status' => 'sent'],
            ['number' => 'INV-2026-0004', 'project' => 'Retail ERP Rollout', 'amount' => 210000, 'due_date' => '2026-02-20', 'status' => 'paid', 'partial_amount' => 210000, 'paid_at' => '2026-02-19 10:30:00'],
            ['number' => 'INV-2026-0005', 'project' => 'Retail ERP Rollout', 'amount' => 240000, 'due_date' => '2026-03-20', 'status' => 'overdue'],
            ['number' => 'INV-2026-0006', 'project' => 'Retail ERP Rollout', 'amount' => 150000, 'due_date' => '2026-04-20', 'status' => 'draft'],
            ['number' => 'INV-2026-0007', 'project' => 'Supply Chain Tracker', 'amount' => 175000, 'due_date' => '2025-12-22', 'status' => 'paid', 'partial_amount' => 175000, 'paid_at' => '2025-12-21 09:00:00'],
            ['number' => 'INV-2026-0008', 'project' => 'Supply Chain Tracker', 'amount' => 160000, 'due_date' => '2026-01-22', 'status' => 'overdue'],
            ['number' => 'INV-2026-0009', 'project' => 'Claims Automation Suite', 'amount' => 300000, 'due_date' => '2025-10-15', 'status' => 'paid', 'partial_amount' => 300000, 'paid_at' => '2025-10-13 15:20:00'],
            ['number' => 'INV-2026-0010', 'project' => 'Claims Automation Suite', 'amount' => 285000, 'due_date' => '2025-12-15', 'status' => 'paid', 'partial_amount' => 285000, 'paid_at' => '2025-12-14 13:45:00'],
        ];

        foreach ($invoices as $row) {
            Invoice::query()->updateOrCreate(
                ['invoice_number' => $row['number']],
                [
                    'project_id' => $projectMap[$row['project']]->id,
                    'amount' => $row['amount'],
                    'due_date' => $row['due_date'],
                    'status' => $row['status'],
                    'partial_amount' => $row['partial_amount'] ?? null,
                    'payment_completed_at' => $row['paid_at'] ?? null,
                    'notes' => 'Phase 3 seeded invoice.',
                ]
            );
        }

        $expenses = [
            ['category' => 'Office Rent', 'description' => 'Head office monthly rent', 'amount' => 120000, 'expense_date' => Carbon::now()->startOfMonth()->toDateString(), 'is_recurring' => true, 'recurrence' => 'monthly', 'next_due_date' => Carbon::now()->addMonth()->startOfMonth()->toDateString()],
            ['category' => 'Cloud Infrastructure', 'description' => 'AWS + DB services', 'amount' => 68000, 'expense_date' => Carbon::now()->startOfMonth()->addDays(3)->toDateString(), 'is_recurring' => true, 'recurrence' => 'monthly', 'next_due_date' => Carbon::now()->addMonth()->startOfMonth()->addDays(3)->toDateString()],
            ['category' => 'Internet & Telecom', 'description' => 'Corporate bandwidth and SIP', 'amount' => 14000, 'expense_date' => Carbon::now()->startOfMonth()->addDays(5)->toDateString(), 'is_recurring' => true, 'recurrence' => 'monthly', 'next_due_date' => Carbon::now()->addMonth()->startOfMonth()->addDays(5)->toDateString()],
            ['category' => 'Travel', 'description' => 'Client implementation visit', 'amount' => 26000, 'expense_date' => Carbon::now()->startOfMonth()->addDays(9)->toDateString(), 'is_recurring' => false],
            ['category' => 'Hardware', 'description' => 'Developer workstation upgrades', 'amount' => 91000, 'expense_date' => Carbon::now()->subMonth()->startOfMonth()->addDays(12)->toDateString(), 'is_recurring' => false],
            ['category' => 'Marketing', 'description' => 'Product webinar campaign', 'amount' => 35000, 'expense_date' => Carbon::now()->subMonth()->startOfMonth()->addDays(16)->toDateString(), 'is_recurring' => false],
        ];

        foreach ($expenses as $row) {
            Expense::query()->updateOrCreate(
                [
                    'category' => $row['category'],
                    'description' => $row['description'],
                    'expense_date' => $row['expense_date'],
                ],
                [
                    'amount' => $row['amount'],
                    'is_recurring' => $row['is_recurring'],
                    'recurrence' => $row['recurrence'] ?? null,
                    'next_due_date' => $row['next_due_date'] ?? null,
                    'created_by' => $admin->id,
                ]
            );
        }

        $liabilities = [
            ['name' => 'Term Loan - Expansion', 'principal_amount' => 950000, 'outstanding' => 620000, 'interest_rate' => 9.5, 'monthly_payment' => 55000, 'start_date' => '2025-01-01', 'next_due_date' => Carbon::now()->addDays(5)->toDateString(), 'status' => 'active'],
            ['name' => 'Equipment Leasing', 'principal_amount' => 300000, 'outstanding' => 125000, 'interest_rate' => 7.2, 'monthly_payment' => 22000, 'start_date' => '2025-06-01', 'next_due_date' => Carbon::now()->addDays(11)->toDateString(), 'status' => 'active'],
            ['name' => 'Vehicle Financing', 'principal_amount' => 180000, 'outstanding' => 0, 'interest_rate' => 8.0, 'monthly_payment' => 18000, 'start_date' => '2024-01-01', 'next_due_date' => null, 'status' => 'completed'],
        ];

        foreach ($liabilities as $row) {
            Liability::query()->updateOrCreate(
                ['name' => $row['name']],
                $row
            );
        }

        $assets = [
            ['name' => 'Primary Application Server', 'category' => 'IT Infrastructure', 'purchase_date' => '2025-04-05', 'purchase_cost' => 420000, 'current_book_value' => 315000, 'useful_life_months' => 60, 'monthly_depreciation' => 7000, 'status' => 'active'],
            ['name' => 'Office Furniture Set', 'category' => 'Furniture', 'purchase_date' => '2024-09-10', 'purchase_cost' => 160000, 'current_book_value' => 112000, 'useful_life_months' => 48, 'monthly_depreciation' => 3333.33, 'status' => 'active'],
            ['name' => 'Employee Laptops Batch A', 'category' => 'Hardware', 'purchase_date' => '2025-01-18', 'purchase_cost' => 520000, 'current_book_value' => 390000, 'useful_life_months' => 36, 'monthly_depreciation' => 14444.44, 'status' => 'active'],
            ['name' => 'Legacy Network Switches', 'category' => 'Networking', 'purchase_date' => '2022-05-01', 'purchase_cost' => 90000, 'current_book_value' => 0, 'useful_life_months' => 36, 'monthly_depreciation' => 2500, 'status' => 'fully_depreciated'],
        ];

        foreach ($assets as $row) {
            Asset::query()->updateOrCreate(
                ['name' => $row['name']],
                $row
            );
        }
    }
}
