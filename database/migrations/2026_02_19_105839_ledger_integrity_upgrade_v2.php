<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Enforce non-nullable financial columns in transactions using RAW SQL to bypass Doctrine/DBAL
        DB::statement("ALTER TABLE transactions MODIFY amount DECIMAL(15,2) NOT NULL DEFAULT 0.00");
        DB::statement("ALTER TABLE transactions MODIFY fee DECIMAL(15,2) NOT NULL DEFAULT 0.00");
        DB::statement("ALTER TABLE transactions MODIFY net_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00");
        DB::statement("ALTER TABLE transactions MODIFY total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00");
        DB::statement("ALTER TABLE transactions MODIFY reference VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE transactions MODIFY transaction_id VARCHAR(255) NOT NULL");

        // 2. Add Platform Revenue to ledger_accounts
        DB::table('ledger_accounts')->insertOrIgnore([
            [
                'uuid' => 'PWV_ACC_REV_PLATFORM',
                'name' => 'Platform Revenue',
                'account_type' => 'revenue',
                'company_id' => null,
                'balance' => 0.00,
                'currency' => 'NGN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => 'PW_CLEAN_PALM_V1',
                'name' => 'PalmPay Clearing (V1)',
                'account_type' => 'bank_clearing',
                'company_id' => null,
                'balance' => 0.00,
                'currency' => 'NGN',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->nullable()->change();
            $table->decimal('fee', 15, 2)->nullable()->change();
            $table->decimal('net_amount', 15, 2)->nullable()->change();
            $table->decimal('total_amount', 15, 2)->nullable()->change();
            $table->string('reference')->nullable()->change();
            $table->string('transaction_id')->nullable()->change();
        });
    }
};
