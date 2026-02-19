<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    public function up(): void
    {
        // --- transactions table ---
        Schema::table('transactions', function (Blueprint $table) {
            if (!$this->indexExists('transactions', 'transactions_reference_unique')) {
                $table->unique('reference', 'transactions_reference_unique');
            }
            if (!$this->indexExists('transactions', 'transactions_status_index')) {
                $table->index('status', 'transactions_status_index');
            }
            if (!$this->indexExists('transactions', 'transactions_reconciliation_status_index')) {
                $table->index('reconciliation_status', 'transactions_reconciliation_status_index');
            }
            if (!$this->indexExists('transactions', 'transactions_provider_index')) {
                $table->index('provider', 'transactions_provider_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if ($this->indexExists('transactions', 'transactions_reference_unique')) {
                $table->dropUnique('transactions_reference_unique');
            }
        });
    }
};
