<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // PalmPay Master Wallet (for receiving funds from customers)
            $table->string('palmpay_account_number')->nullable()->after('bank_code');
            $table->string('palmpay_account_name')->nullable()->after('palmpay_account_number');
            $table->string('palmpay_bank_name')->default('PalmPay')->after('palmpay_account_name');
            $table->string('palmpay_bank_code')->default('100033')->after('palmpay_bank_name');
            
            // Note: account_number, bank_name, account_name, bank_code are for SETTLEMENT (withdrawal) account
            // palmpay_* fields are for MASTER WALLET (funding/collection) account
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'palmpay_account_number',
                'palmpay_account_name',
                'palmpay_bank_name',
                'palmpay_bank_code'
            ]);
        });
    }
};
