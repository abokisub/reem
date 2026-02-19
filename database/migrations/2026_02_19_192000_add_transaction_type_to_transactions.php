<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'transaction_type')) {
                $table->string('transaction_type', 50)->nullable()->after('category');
            }
        });
        
        // Update existing records based on category
        DB::statement("
            UPDATE transactions 
            SET transaction_type = CASE 
                WHEN category = 'transfer_out' THEN 'transfer'
                WHEN category = 'payout' THEN 'withdrawal'
                WHEN category = 'settlement' THEN 'settlement_withdrawal'
                WHEN category = 'payment' THEN 'payment'
                WHEN category = 'refund' THEN 'refund'
                ELSE category
            END
            WHERE transaction_type IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'transaction_type')) {
                $table->dropColumn('transaction_type');
            }
        });
    }
};
