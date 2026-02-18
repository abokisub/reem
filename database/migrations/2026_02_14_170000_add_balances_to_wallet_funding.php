<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wallet_funding', function (Blueprint $table) {
            $columns = [
                'mtn_cg_bal',
                'mtn_g_bal',
                'mtn_sme_bal',
                'airtel_cg_bal',
                'airtel_g_bal',
                'airtel_sme_bal',
                'glo_cg_bal',
                'glo_g_bal',
                'glo_sme_bal',
                'mobile_cg_bal',
                'mobile_g_bal',
                'mobile_sme_bal'
            ];

            foreach ($columns as $column) {
                if (!Schema::hasColumn('wallet_funding', $column)) {
                    $table->decimal($column, 20, 2)->default(0.00)->nullable();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_funding', function (Blueprint $table) {
            $columns = [
                'mtn_cg_bal',
                'mtn_g_bal',
                'mtn_sme_bal',
                'airtel_cg_bal',
                'airtel_g_bal',
                'airtel_sme_bal',
                'glo_cg_bal',
                'glo_g_bal',
                'glo_sme_bal',
                'mobile_cg_bal',
                'mobile_g_bal',
                'mobile_sme_bal'
            ];

            $table->dropColumn($columns);
        });
    }
};
