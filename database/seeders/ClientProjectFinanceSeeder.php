<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ClientProjectFinanceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@auxfin.local')->firstOrFail();
        $base  = Carbon::now()->startOfMonth();

        // ─────────────────────────────────────────────
        //  CLIENTS  (6 clients)
        // ─────────────────────────────────────────────
        $clientData = [
            [
                'name'           => 'Acme Logistics Ltd',
                'email'          => 'finance@acme-logistics.com',
                'phone'          => '+8801711000001',
                'contact_person' => 'Rafiq Hasan',
                'address'        => 'Motijheel, Dhaka-1000',
            ],
            [
                'name'           => 'BlueWave Retail',
                'email'          => 'accounts@bluewave-retail.com',
                'phone'          => '+8801711000002',
                'contact_person' => 'Mim Akter',
                'address'        => 'Gulshan-2, Dhaka-1212',
            ],
            [
                'name'           => 'GreenField Agro',
                'email'          => 'finance@greenfield-agro.com',
                'phone'          => '+8801711000003',
                'contact_person' => 'Anik Sarker',
                'address'        => 'Farmgate, Dhaka-1215',
            ],
            [
                'name'           => 'Vertex Healthcare',
                'email'          => 'billing@vertex-health.com',
                'phone'          => '+8801711000004',
                'contact_person' => 'Nitu Paul',
                'address'        => 'Dhanmondi-32, Dhaka-1209',
            ],
            [
                'name'           => 'Nova Fintech Solutions',
                'email'          => 'cfo@nova-fintech.com',
                'phone'          => '+8801711000005',
                'contact_person' => 'Tanvir Rahman',
                'address'        => 'Banani, Dhaka-1213',
            ],
            [
                'name'           => 'PrimeTech Industries',
                'email'          => 'accounts@primetech-bd.com',
                'phone'          => '+8801711000006',
                'contact_person' => 'Sumaiya Khatun',
                'address'        => 'Uttara, Dhaka-1230',
            ],
        ];

        $clients = [];
        foreach ($clientData as $row) {
            $clients[$row['name']] = Client::query()->updateOrCreate(
                ['name' => $row['name']],
                $row
            );
        }

        // ─────────────────────────────────────────────
        //  PROJECTS  (8 projects across 6 clients)
        // ─────────────────────────────────────────────
        $projectData = [
            [
                'client'          => 'Acme Logistics Ltd',
                'name'            => 'Fleet Cost Intelligence Platform',
                'description'     => 'End-to-end fleet management and cost tracking platform with real-time analytics.',
                'contract_amount' => 1250000.00,
                'status'          => 'active',
                'start_date'      => $base->copy()->subMonths(5)->toDateString(),
                'end_date'        => null,
            ],
            [
                'client'          => 'Acme Logistics Ltd',
                'name'            => 'Route Optimisation Module',
                'description'     => 'AI-driven route planning and delivery optimisation add-on.',
                'contract_amount' => 620000.00,
                'status'          => 'on_hold',
                'start_date'      => $base->copy()->subMonths(2)->toDateString(),
                'end_date'        => null,
            ],
            [
                'client'          => 'BlueWave Retail',
                'name'            => 'Retail ERP Rollout',
                'description'     => 'Full ERP implementation covering POS, inventory and financials.',
                'contract_amount' => 980000.00,
                'status'          => 'active',
                'start_date'      => $base->copy()->subMonths(4)->toDateString(),
                'end_date'        => null,
            ],
            [
                'client'          => 'GreenField Agro',
                'name'            => 'Supply Chain Tracker',
                'description'     => 'Farm-to-shelf supply chain visibility and compliance platform.',
                'contract_amount' => 760000.00,
                'status'          => 'on_hold',
                'start_date'      => $base->copy()->subMonths(7)->toDateString(),
                'end_date'        => null,
            ],
            [
                'client'          => 'Vertex Healthcare',
                'name'            => 'Claims Automation Suite',
                'description'     => 'Insurance claims processing and medical billing automation.',
                'contract_amount' => 1430000.00,
                'status'          => 'completed',
                'start_date'      => $base->copy()->subMonths(11)->toDateString(),
                'end_date'        => $base->copy()->subMonths(1)->toDateString(),
            ],
            [
                'client'          => 'Nova Fintech Solutions',
                'name'            => 'Digital Lending Portal',
                'description'     => 'Customer-facing digital loan origination and disbursement portal.',
                'contract_amount' => 1850000.00,
                'status'          => 'active',
                'start_date'      => $base->copy()->subMonths(3)->toDateString(),
                'end_date'        => null,
            ],
            [
                'client'          => 'PrimeTech Industries',
                'name'            => 'Factory HRMS Integration',
                'description'     => 'HRMS integration connecting factory floor attendance with central payroll.',
                'contract_amount' => 540000.00,
                'status'          => 'completed',
                'start_date'      => $base->copy()->subMonths(9)->toDateString(),
                'end_date'        => $base->copy()->subMonths(3)->toDateString(),
            ],
            [
                'client'          => 'PrimeTech Industries',
                'name'            => 'ERP Phase 2 – Finance Module',
                'description'     => 'Financial reporting, budgeting and forecasting module.',
                'contract_amount' => 780000.00,
                'status'          => 'active',
                'start_date'      => $base->copy()->subMonths(1)->toDateString(),
                'end_date'        => null,
            ],
        ];

        $projects = [];
        foreach ($projectData as $row) {
            $projects[$row['name']] = Project::query()->updateOrCreate(
                ['client_id' => $clients[$row['client']]->id, 'name' => $row['name']],
                [
                    'description'     => $row['description'],
                    'contract_amount' => $row['contract_amount'],
                    'status'          => $row['status'],
                    'start_date'      => $row['start_date'],
                    'end_date'        => $row['end_date'],
                ]
            );
        }

        // ─────────────────────────────────────────────
        //  INVOICES + PROJECT PAYMENTS
        // ─────────────────────────────────────────────
        $invoiceData = [
            // Fleet Cost Intelligence Platform (active)
            ['num' => 'INV-2026-0001', 'project' => 'Fleet Cost Intelligence Platform',
             'amount' => 250000, 'invoice_date' => $base->copy()->subMonths(5)->addDays(5)->toDateString(),
             'due_date' => $base->copy()->subMonths(5)->addDays(25)->toDateString(),
             'status' => 'paid', 'partial_amount' => 250000,
             'paid_at' => $base->copy()->subMonths(5)->addDays(23)],
            ['num' => 'INV-2026-0002', 'project' => 'Fleet Cost Intelligence Platform',
             'amount' => 300000, 'invoice_date' => $base->copy()->subMonths(3)->addDays(3)->toDateString(),
             'due_date' => $base->copy()->subMonths(3)->addDays(25)->toDateString(),
             'status' => 'paid', 'partial_amount' => 300000,
             'paid_at' => $base->copy()->subMonths(3)->addDays(22)],
            ['num' => 'INV-2026-0003', 'project' => 'Fleet Cost Intelligence Platform',
             'amount' => 220000, 'invoice_date' => $base->copy()->subMonths(1)->addDays(5)->toDateString(),
             'due_date' => $base->copy()->subMonths(1)->addDays(25)->toDateString(),
             'status' => 'partial', 'partial_amount' => 110000, 'paid_at' => null],
            ['num' => 'INV-2026-0004', 'project' => 'Fleet Cost Intelligence Platform',
             'amount' => 180000, 'invoice_date' => $base->copy()->addDays(3)->toDateString(),
             'due_date' => $base->copy()->addDays(28)->toDateString(),
             'status' => 'sent', 'partial_amount' => null, 'paid_at' => null],

            // Route Optimisation Module (on_hold)
            ['num' => 'INV-2026-0005', 'project' => 'Route Optimisation Module',
             'amount' => 150000, 'invoice_date' => $base->copy()->subMonths(2)->addDays(2)->toDateString(),
             'due_date' => $base->copy()->subMonths(2)->addDays(20)->toDateString(),
             'status' => 'overdue', 'partial_amount' => null, 'paid_at' => null],

            // Retail ERP Rollout (active)
            ['num' => 'INV-2026-0006', 'project' => 'Retail ERP Rollout',
             'amount' => 210000, 'invoice_date' => $base->copy()->subMonths(4)->addDays(5)->toDateString(),
             'due_date' => $base->copy()->subMonths(4)->addDays(20)->toDateString(),
             'status' => 'paid', 'partial_amount' => 210000,
             'paid_at' => $base->copy()->subMonths(4)->addDays(18)],
            ['num' => 'INV-2026-0007', 'project' => 'Retail ERP Rollout',
             'amount' => 240000, 'invoice_date' => $base->copy()->subMonths(2)->addDays(5)->toDateString(),
             'due_date' => $base->copy()->subMonths(2)->addDays(20)->toDateString(),
             'status' => 'overdue', 'partial_amount' => null, 'paid_at' => null],
            ['num' => 'INV-2026-0008', 'project' => 'Retail ERP Rollout',
             'amount' => 180000, 'invoice_date' => $base->copy()->addDays(5)->toDateString(),
             'due_date' => $base->copy()->addDays(25)->toDateString(),
             'status' => 'draft', 'partial_amount' => null, 'paid_at' => null],

            // Supply Chain Tracker (on_hold)
            ['num' => 'INV-2026-0009', 'project' => 'Supply Chain Tracker',
             'amount' => 175000, 'invoice_date' => $base->copy()->subMonths(7)->addDays(5)->toDateString(),
             'due_date' => $base->copy()->subMonths(7)->addDays(22)->toDateString(),
             'status' => 'paid', 'partial_amount' => 175000,
             'paid_at' => $base->copy()->subMonths(7)->addDays(20)],
            ['num' => 'INV-2026-0010', 'project' => 'Supply Chain Tracker',
             'amount' => 160000, 'invoice_date' => $base->copy()->subMonths(5)->addDays(5)->toDateString(),
             'due_date' => $base->copy()->subMonths(5)->addDays(22)->toDateString(),
             'status' => 'overdue', 'partial_amount' => null, 'paid_at' => null],

            // Claims Automation Suite (completed)
            ['num' => 'INV-2025-0011', 'project' => 'Claims Automation Suite',
             'amount' => 350000, 'invoice_date' => $base->copy()->subMonths(11)->addDays(5)->toDateString(),
             'due_date' => $base->copy()->subMonths(11)->addDays(20)->toDateString(),
             'status' => 'paid', 'partial_amount' => 350000,
             'paid_at' => $base->copy()->subMonths(11)->addDays(18)],
            ['num' => 'INV-2025-0012', 'project' => 'Claims Automation Suite',
             'amount' => 400000, 'invoice_date' => $base->copy()->subMonths(8)->addDays(5)->toDateString(),
             'due_date' => $base->copy()->subMonths(8)->addDays(20)->toDateString(),
             'status' => 'paid', 'partial_amount' => 400000,
             'paid_at' => $base->copy()->subMonths(8)->addDays(18)],
            ['num' => 'INV-2025-0013', 'project' => 'Claims Automation Suite',
             'amount' => 350000, 'invoice_date' => $base->copy()->subMonths(5)->addDays(5)->toDateString(),
             'due_date' => $base->copy()->subMonths(5)->addDays(20)->toDateString(),
             'status' => 'paid', 'partial_amount' => 350000,
             'paid_at' => $base->copy()->subMonths(5)->addDays(18)],
            ['num' => 'INV-2025-0014', 'project' => 'Claims Automation Suite',
             'amount' => 330000, 'invoice_date' => $base->copy()->subMonths(2)->addDays(5)->toDateString(),
             'due_date' => $base->copy()->subMonths(2)->addDays(20)->toDateString(),
             'status' => 'paid', 'partial_amount' => 330000,
             'paid_at' => $base->copy()->subMonths(2)->addDays(18)],

            // Digital Lending Portal (active)
            ['num' => 'INV-2026-0015', 'project' => 'Digital Lending Portal',
             'amount' => 450000, 'invoice_date' => $base->copy()->subMonths(3)->addDays(5)->toDateString(),
             'due_date' => $base->copy()->subMonths(3)->addDays(25)->toDateString(),
             'status' => 'paid', 'partial_amount' => 450000,
             'paid_at' => $base->copy()->subMonths(3)->addDays(22)],
            ['num' => 'INV-2026-0016', 'project' => 'Digital Lending Portal',
             'amount' => 380000, 'invoice_date' => $base->copy()->subMonths(1)->addDays(5)->toDateString(),
             'due_date' => $base->copy()->subMonths(1)->addDays(25)->toDateString(),
             'status' => 'partial', 'partial_amount' => 200000, 'paid_at' => null],
            ['num' => 'INV-2026-0017', 'project' => 'Digital Lending Portal',
             'amount' => 320000, 'invoice_date' => $base->copy()->addDays(5)->toDateString(),
             'due_date' => $base->copy()->addDays(30)->toDateString(),
             'status' => 'sent', 'partial_amount' => null, 'paid_at' => null],

            // Factory HRMS Integration (completed)
            ['num' => 'INV-2025-0018', 'project' => 'Factory HRMS Integration',
             'amount' => 270000, 'invoice_date' => $base->copy()->subMonths(9)->addDays(5)->toDateString(),
             'due_date' => $base->copy()->subMonths(9)->addDays(20)->toDateString(),
             'status' => 'paid', 'partial_amount' => 270000,
             'paid_at' => $base->copy()->subMonths(9)->addDays(18)],
            ['num' => 'INV-2025-0019', 'project' => 'Factory HRMS Integration',
             'amount' => 270000, 'invoice_date' => $base->copy()->subMonths(5)->addDays(5)->toDateString(),
             'due_date' => $base->copy()->subMonths(5)->addDays(20)->toDateString(),
             'status' => 'paid', 'partial_amount' => 270000,
             'paid_at' => $base->copy()->subMonths(5)->addDays(18)],

            // ERP Phase 2 (active - just started)
            ['num' => 'INV-2026-0020', 'project' => 'ERP Phase 2 – Finance Module',
             'amount' => 200000, 'invoice_date' => $base->copy()->addDays(3)->toDateString(),
             'due_date' => $base->copy()->addDays(28)->toDateString(),
             'status' => 'draft', 'partial_amount' => null, 'paid_at' => null],
        ];

        foreach ($invoiceData as $row) {
            $invoice = Invoice::query()->updateOrCreate(
                ['invoice_number' => $row['num']],
                [
                    'project_id'           => $projects[$row['project']]->id,
                    'invoice_date'         => $row['invoice_date'],
                    'amount'               => $row['amount'],
                    'due_date'             => $row['due_date'],
                    'status'               => $row['status'],
                    'partial_amount'       => $row['partial_amount'] ?? null,
                    'payment_completed_at' => isset($row['paid_at']) ? $row['paid_at'] : null,
                    'notes'                => 'Seeded invoice for ' . $row['project'],
                ]
            );

            // ── Project Payments for paid/partial invoices ───────────────
            if ($row['status'] === 'paid' && isset($row['paid_at']) && $row['paid_at'] !== null) {
                ProjectPayment::query()->updateOrCreate(
                    ['invoice_id' => $invoice->id],
                    [
                        'project_id'       => $projects[$row['project']]->id,
                        'recorded_by'      => $admin->id,
                        'payment_date'     => Carbon::parse($row['paid_at'])->toDateString(),
                        'amount'           => $row['partial_amount'],
                        'payment_method'   => 'bank_transfer',
                        'reference_number' => 'TXN-' . substr($row['num'], -4),
                        'notes'            => 'Full payment received for invoice ' . $row['num'],
                    ]
                );
            }

            if ($row['status'] === 'partial' && ! empty($row['partial_amount'])) {
                ProjectPayment::query()->updateOrCreate(
                    ['invoice_id' => $invoice->id],
                    [
                        'project_id'       => $projects[$row['project']]->id,
                        'recorded_by'      => $admin->id,
                        'payment_date'     => Carbon::now()->subDays(5)->toDateString(),
                        'amount'           => $row['partial_amount'],
                        'payment_method'   => 'bank_transfer',
                        'reference_number' => 'TXN-PART-' . substr($row['num'], -4),
                        'notes'            => 'Partial payment received for invoice ' . $row['num'],
                    ]
                );
            }
        }
    }
}
