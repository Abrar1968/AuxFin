<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('owner_equity_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->enum('entry_type', ['capital_contribution', 'drawing']);
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('entry_date');
            $table->index('entry_type');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->enum('accounting_mode', ['cash', 'payable', 'prepaid'])
                ->default('cash')
                ->after('amount');
            $table->date('payable_due_date')->nullable()->after('expense_date');
            $table->date('prepaid_start_date')->nullable()->after('payable_due_date');
            $table->unsignedInteger('prepaid_months')->nullable()->after('prepaid_start_date');
        });

        Schema::create('expense_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 40)->default('bank_transfer');
            $table->string('reference_number', 80)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['expense_id', 'payment_date']);
        });

        DB::table('expenses')->update([
            'accounting_mode' => 'cash',
        ]);

        $now = now();
        $legacyExpenses = DB::table('expenses')
            ->select(['id', 'amount', 'expense_date', 'created_at', 'updated_at'])
            ->where('amount', '>', 0)
            ->get();

        foreach ($legacyExpenses as $expense) {
            DB::table('expense_payments')->insert([
                'expense_id' => $expense->id,
                'recorded_by' => null,
                'payment_date' => $expense->expense_date,
                'amount' => $expense->amount,
                'payment_method' => 'legacy_migration',
                'reference_number' => 'LEGACY-EXP-'.$expense->id,
                'notes' => 'Auto-created from existing cash expense records',
                'created_at' => $expense->created_at ?? $now,
                'updated_at' => $expense->updated_at ?? $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_payments');

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn([
                'accounting_mode',
                'payable_due_date',
                'prepaid_start_date',
                'prepaid_months',
            ]);
        });

        Schema::dropIfExists('owner_equity_entries');
    }
};
