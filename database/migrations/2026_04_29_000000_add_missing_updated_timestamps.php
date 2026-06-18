<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['loan_repayments', 'company_snapshots', 'public_holidays', 'audit_logs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                }
            });
        }

        Schema::table('message_reads', function (Blueprint $table): void {
            if (! Schema::hasColumn('message_reads', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('read_at');
            }

            if (! Schema::hasColumn('message_reads', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    public function down(): void
    {
        foreach (['loan_repayments', 'company_snapshots', 'public_holidays', 'audit_logs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
            });
        }

        Schema::table('message_reads', function (Blueprint $table): void {
            if (Schema::hasColumn('message_reads', 'updated_at')) {
                $table->dropColumn('updated_at');
            }

            if (Schema::hasColumn('message_reads', 'created_at')) {
                $table->dropColumn('created_at');
            }
        });
    }
};
