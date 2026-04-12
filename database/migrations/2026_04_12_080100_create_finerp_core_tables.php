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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->unsignedBigInteger('head_id')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_code', 20)->unique();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('designation', 150);
            $table->date('date_of_joining');
            $table->string('bank_account_number', 30)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('house_rent', 12, 2)->default(0);
            $table->decimal('conveyance', 12, 2)->default(0);
            $table->decimal('medical_allowance', 12, 2)->default(0);
            $table->decimal('pf_rate', 5, 2)->default(0);
            $table->decimal('tds_rate', 5, 2)->default(0);
            $table->decimal('professional_tax', 10, 2)->default(0);
            $table->integer('late_threshold_days')->default(3);
            $table->enum('late_penalty_type', ['half_day', 'full_day'])->default('half_day');
            $table->tinyInteger('working_days_per_week')->default(5);
            $table->json('weekly_off_days')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('salary_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('month');
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('house_rent', 12, 2)->default(0);
            $table->decimal('conveyance', 12, 2)->default(0);
            $table->decimal('medical_allowance', 12, 2)->default(0);
            $table->decimal('performance_bonus', 12, 2)->default(0);
            $table->decimal('festival_bonus', 12, 2)->default(0);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('other_bonus', 12, 2)->default(0);
            $table->decimal('gross_earnings', 12, 2)->default(0);
            $table->decimal('tds_deduction', 12, 2)->default(0);
            $table->decimal('pf_deduction', 12, 2)->default(0);
            $table->decimal('professional_tax', 10, 2)->default(0);
            $table->decimal('unpaid_leave_deduction', 12, 2)->default(0);
            $table->decimal('late_penalty_deduction', 12, 2)->default(0);
            $table->decimal('loan_emi_deduction', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_payable', 12, 2)->default(0);
            $table->integer('days_present')->default(0);
            $table->integer('unpaid_leave_days')->default(0);
            $table->integer('late_entries')->default(0);
            $table->integer('expected_working_days')->default(0);
            $table->enum('status', ['draft', 'processed', 'paid'])->default('draft');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'month']);
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('loan_reference', 30)->unique();
            $table->decimal('amount_requested', 12, 2);
            $table->decimal('amount_approved', 12, 2)->nullable();
            $table->tinyInteger('repayment_months')->nullable();
            $table->decimal('emi_amount', 12, 2)->nullable();
            $table->date('start_month')->nullable();
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'completed'])->default('pending');
            $table->decimal('amount_remaining', 12, 2)->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->date('month');
            $table->decimal('amount_paid', 12, 2);
            $table->timestamp('created_at')->nullable();
            $table->unique(['loan_id', 'month']);
        });

        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('leave_type', ['casual', 'sick', 'earned', 'unpaid']);
            $table->date('from_date');
            $table->date('to_date');
            $table->integer('days');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'weekly_off', 'holiday']);
            $table->boolean('is_late')->default(false);
            $table->integer('late_minutes')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'date']);
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('email', 200)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->decimal('contract_amount', 14, 2)->default(0);
            $table->enum('status', ['active', 'completed', 'on_hold', 'cancelled'])->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 30)->unique();
            $table->decimal('amount', 14, 2);
            $table->date('due_date');
            $table->enum('status', ['draft', 'sent', 'partial', 'paid', 'overdue'])->default('draft');
            $table->decimal('partial_amount', 14, 2)->nullable();
            $table->timestamp('payment_completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100);
            $table->text('description');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence', ['monthly', 'quarterly', 'yearly'])->nullable();
            $table->date('next_due_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('liabilities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->decimal('principal_amount', 14, 2);
            $table->decimal('outstanding', 14, 2);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->decimal('monthly_payment', 12, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_due_date')->nullable();
            $table->enum('status', ['active', 'completed', 'defaulted'])->default('active');
            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('category', 100);
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 14, 2);
            $table->decimal('current_book_value', 14, 2);
            $table->integer('useful_life_months');
            $table->decimal('monthly_depreciation', 12, 2);
            $table->enum('status', ['active', 'disposed', 'fully_depreciated'])->default('active');
            $table->timestamps();
        });

        Schema::create('company_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_month')->unique();
            $table->decimal('total_revenue', 16, 2)->default(0);
            $table->decimal('total_payroll', 16, 2)->default(0);
            $table->decimal('total_opex', 16, 2)->default(0);
            $table->decimal('gross_profit', 16, 2)->default(0);
            $table->decimal('net_profit', 16, 2)->default(0);
            $table->decimal('burn_rate', 16, 2)->default(0);
            $table->decimal('cash_runway_months', 8, 2)->default(0);
            $table->integer('headcount')->default(0);
            $table->decimal('total_ar', 16, 2)->default(0);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('employee_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('thread_id')->nullable()->index();
            $table->enum('type', ['late_appeal', 'deduction_dispute', 'leave_clarification', 'salary_query', 'loan_query', 'general_hr']);
            $table->string('subject', 300);
            $table->text('body');
            $table->date('reference_date')->nullable();
            $table->date('reference_month')->nullable();
            $table->json('attachments')->nullable();
            $table->enum('status', ['open', 'under_review', 'resolved', 'rejected'])->default('open');
            $table->enum('priority', ['normal', 'high'])->default('normal');
            $table->text('admin_reply')->nullable();
            $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable();
            $table->enum('action_taken', ['none', 'deduction_reversed', 'mark_excused', 'salary_adjusted', 'noted'])->default('none');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('employee_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['message_id', 'user_id']);
        });

        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->date('date')->unique();
            $table->boolean('is_optional')->default(false);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('model_type', 100)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('public_holidays');
        Schema::dropIfExists('message_reads');
        Schema::dropIfExists('employee_messages');
        Schema::dropIfExists('company_snapshots');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('liabilities');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('salary_months');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('departments');
    }
};
