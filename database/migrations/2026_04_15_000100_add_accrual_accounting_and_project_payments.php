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
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('invoice_date')->nullable()->after('amount');
            $table->index('invoice_date');
        });

        DB::table('invoices')
            ->whereNull('invoice_date')
            ->update([
                'invoice_date' => DB::raw('COALESCE(DATE(created_at), due_date)'),
            ]);

        Schema::table('company_snapshots', function (Blueprint $table) {
            $table->decimal('total_cash_collected', 16, 2)->default(0)->after('total_revenue');
        });

        Schema::create('project_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 40)->default('bank_transfer');
            $table->string('reference_number', 80)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'payment_date']);
            $table->index('invoice_id');
        });

        $legacyInvoices = DB::table('invoices')
            ->select(['id', 'project_id', 'partial_amount', 'payment_completed_at', 'created_at', 'updated_at'])
            ->whereNotNull('partial_amount')
            ->where('partial_amount', '>', 0)
            ->get();

        foreach ($legacyInvoices as $invoice) {
            $paymentDate = substr((string) ($invoice->payment_completed_at ?? $invoice->updated_at ?? $invoice->created_at), 0, 10);

            DB::table('project_payments')->insert([
                'project_id' => $invoice->project_id,
                'invoice_id' => $invoice->id,
                'recorded_by' => null,
                'payment_date' => $paymentDate ?: now()->toDateString(),
                'amount' => $invoice->partial_amount,
                'payment_method' => 'legacy_migration',
                'reference_number' => 'LEGACY-INV-'.$invoice->id,
                'notes' => 'Auto-migrated from invoice.partial_amount',
                'created_at' => $invoice->created_at ?? now(),
                'updated_at' => $invoice->updated_at ?? now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_payments');

        Schema::table('company_snapshots', function (Blueprint $table) {
            $table->dropColumn('total_cash_collected');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['invoice_date']);
            $table->dropColumn('invoice_date');
        });
    }
};
