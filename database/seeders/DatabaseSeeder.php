<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Execution order is critical – each seeder depends on the previous.
     *
     * 1. CoreUsersSeeder          → users, departments, employees
     * 2. SettingsAndEquitySeeder  → settings, public_holidays, business_owners, owner_equity_entries
     * 3. AttendanceLeaveSeeder    → attendances (3-month history), leaves
     * 4. LoanSeeder               → loans (all 5 statuses), loan_repayments
     * 5. SalarySeeder             → salary_months (6-month history, math-consistent)
     * 6. ClientProjectFinanceSeeder → clients, projects, invoices, project_payments
     * 7. ExpenseAssetLiabilitySeeder → expenses, expense_payments, liabilities, assets
     * 8. MessagesSnapshotSeeder   → employee_messages, message_reads, company_snapshots, audit_logs
     */
    public function run(): void
    {
        $this->call([
            CoreUsersSeeder::class,
            SettingsAndEquitySeeder::class,
            AttendanceLeaveSeeder::class,
            LoanSeeder::class,
            SalarySeeder::class,
            ClientProjectFinanceSeeder::class,
            ExpenseAssetLiabilitySeeder::class,
            MessagesSnapshotSeeder::class,
        ]);
    }
}
