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
        Schema::create('business_owners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->decimal('ownership_percentage', 5, 2);
            $table->decimal('initial_investment', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'ownership_percentage']);
        });

        Schema::table('owner_equity_entries', function (Blueprint $table) {
            $table->foreignId('business_owner_id')
                ->nullable()
                ->after('id')
                ->constrained('business_owners')
                ->nullOnDelete();

            $table->index(['business_owner_id', 'entry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owner_equity_entries', function (Blueprint $table) {
            $table->dropForeign(['business_owner_id']);
            $table->dropIndex(['business_owner_id', 'entry_date']);
            $table->dropColumn('business_owner_id');
        });

        Schema::dropIfExists('business_owners');
    }
};
