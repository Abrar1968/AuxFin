<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['status', 'due_date'], 'idx_invoices_status_due');
            $table->index('payment_completed_at', 'idx_invoices_paid_at');
        });

        Schema::table('salary_months', function (Blueprint $table): void {
            $table->index(['month', 'status'], 'idx_salary_month_status');
            $table->index('processed_at', 'idx_salary_processed_at');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->index(['is_recurring', 'next_due_date'], 'idx_expenses_recurring_due');
            $table->index('expense_date', 'idx_expenses_date');
        });

        Schema::table('liabilities', function (Blueprint $table): void {
            $table->index(['status', 'next_due_date'], 'idx_liabilities_status_due');
        });

        Schema::table('loans', function (Blueprint $table): void {
            $table->index(['employee_id', 'status'], 'idx_loans_employee_status');
            $table->index(['status', 'created_at'], 'idx_loans_status_created');
        });

        Schema::table('employee_messages', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'idx_messages_status_created');
            $table->index(['employee_id', 'updated_at'], 'idx_messages_employee_updated');
            $table->index('type', 'idx_messages_type');
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->index(['employee_id', 'is_late', 'date'], 'idx_attendance_employee_late_date');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropIndex('idx_attendance_employee_late_date');
        });

        Schema::table('employee_messages', function (Blueprint $table): void {
            $table->dropIndex('idx_messages_status_created');
            $table->dropIndex('idx_messages_employee_updated');
            $table->dropIndex('idx_messages_type');
        });

        Schema::table('loans', function (Blueprint $table): void {
            $table->dropIndex('idx_loans_employee_status');
            $table->dropIndex('idx_loans_status_created');
        });

        Schema::table('liabilities', function (Blueprint $table): void {
            $table->dropIndex('idx_liabilities_status_due');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex('idx_expenses_recurring_due');
            $table->dropIndex('idx_expenses_date');
        });

        Schema::table('salary_months', function (Blueprint $table): void {
            $table->dropIndex('idx_salary_month_status');
            $table->dropIndex('idx_salary_processed_at');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('idx_invoices_status_due');
            $table->dropIndex('idx_invoices_paid_at');
        });
    }
};
