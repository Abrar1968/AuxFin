<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->index(['status', 'id'], 'idx_projects_status_id');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['status', 'invoice_date'], 'idx_invoices_status_invoice_date');
        });

        Schema::table('project_payments', function (Blueprint $table): void {
            $table->index('payment_date', 'idx_project_payments_date');
            $table->index(['invoice_id', 'payment_date'], 'idx_project_payments_invoice_date');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->index(['expense_date', 'category'], 'idx_expenses_date_category');
        });

        Schema::table('liabilities', function (Blueprint $table): void {
            $table->index(['status', 'start_date', 'end_date'], 'idx_liabilities_status_period');
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->index('name', 'idx_clients_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropIndex('idx_clients_name');
        });

        Schema::table('liabilities', function (Blueprint $table): void {
            $table->dropIndex('idx_liabilities_status_period');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex('idx_expenses_date_category');
        });

        Schema::table('project_payments', function (Blueprint $table): void {
            $table->dropIndex('idx_project_payments_invoice_date');
            $table->dropIndex('idx_project_payments_date');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('idx_invoices_status_invoice_date');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropIndex('idx_projects_status_id');
        });
    }
};
