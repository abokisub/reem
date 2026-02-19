<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Only add columns if they don't exist
            if (!Schema::hasColumn('transactions', 'provider_reference')) {
                $table->string('provider_reference')->nullable()->after('palmpay_reference');
            }
            if (!Schema::hasColumn('transactions', 'provider')) {
                $table->string('provider')->nullable()->after('provider_reference');
            }
            if (!Schema::hasColumn('transactions', 'reconciliation_status')) {
                $table->enum('reconciliation_status', ['pending', 'reconciled', 'mismatched'])->default('pending')->after('status');
            }
            if (!Schema::hasColumn('transactions', 'reconciled_at')) {
                $table->timestamp('reconciled_at')->nullable()->after('reconciliation_status');
            }
        });
        
        // Add index separately to avoid conflicts
        Schema::table('transactions', function (Blueprint $table) {
            if (!$this->indexExists('transactions', 'transactions_provider_reference_index')) {
                $table->index('provider_reference', 'transactions_provider_reference_index');
            }
        });
    }
    
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if ($this->indexExists('transactions', 'transactions_provider_reference_index')) {
                $table->dropIndex('transactions_provider_reference_index');
            }
            if (Schema::hasColumn('transactions', 'provider_reference')) {
                $table->dropColumn('provider_reference');
            }
            if (Schema::hasColumn('transactions', 'provider')) {
                $table->dropColumn('provider');
            }
            if (Schema::hasColumn('transactions', 'reconciliation_status')) {
                $table->dropColumn('reconciliation_status');
            }
            if (Schema::hasColumn('transactions', 'reconciled_at')) {
                $table->dropColumn('reconciled_at');
            }
        });
    }
};
