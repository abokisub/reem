<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            // High-volume indexes for reports and balance computation
            $table->index('debit_account_id');
            $table->index('credit_account_id');
            $table->index('created_at');

            // Composite index for audit trails per account
            $table->index(['debit_account_id', 'created_at']);
            $table->index(['credit_account_id', 'created_at']);
        });

        Schema::table('ledger_accounts', function (Blueprint $table) {
            $table->index('uuid'); // PWM_ACC_xxx
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropIndex(['debit_account_id']);
            $table->dropIndex(['credit_account_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['debit_account_id', 'created_at']);
            $table->dropIndex(['credit_account_id', 'created_at']);
        });

        Schema::table('ledger_accounts', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
        });
    }
};
