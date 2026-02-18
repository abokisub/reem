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
        Schema::table('settings', function (Blueprint $table) {
            // Pay with Wallet
            $table->string('wallet_charge_type')->default('PERCENT')->after('transfer_charge_cap');
            $table->decimal('wallet_charge_value', 20, 2)->default(1.2)->after('wallet_charge_type');
            $table->decimal('wallet_charge_cap', 20, 2)->default(1000)->after('wallet_charge_value');

            // Payout to Bank
            $table->string('payout_bank_charge_type')->default('FLAT')->after('wallet_charge_cap');
            $table->decimal('payout_bank_charge_value', 20, 2)->default(30)->after('payout_bank_charge_type');
            $table->decimal('payout_bank_charge_cap', 20, 2)->nullable()->after('payout_bank_charge_value');

            // Payout to PalmPay
            $table->string('payout_palmpay_charge_type')->default('FLAT')->after('payout_bank_charge_cap');
            $table->decimal('payout_palmpay_charge_value', 20, 2)->default(15)->after('payout_palmpay_charge_type');
            $table->decimal('payout_palmpay_charge_cap', 20, 2)->nullable()->after('payout_palmpay_charge_value');
        });

        // Update existing settings record with default values
        DB::table('settings')->where('id', 1)->update([
            'wallet_charge_type' => 'PERCENT',
            'wallet_charge_value' => 1.2,
            'wallet_charge_cap' => 1000,
            'payout_bank_charge_type' => 'FLAT',
            'payout_bank_charge_value' => 30,
            'payout_bank_charge_cap' => null,
            'payout_palmpay_charge_type' => 'FLAT',
            'payout_palmpay_charge_value' => 15,
            'payout_palmpay_charge_cap' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'wallet_charge_type',
                'wallet_charge_value',
                'wallet_charge_cap',
                'payout_bank_charge_type',
                'payout_bank_charge_value',
                'payout_bank_charge_cap',
                'payout_palmpay_charge_type',
                'payout_palmpay_charge_value',
                'payout_palmpay_charge_cap',
            ]);
        });
    }
};
