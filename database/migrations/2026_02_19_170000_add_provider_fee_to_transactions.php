<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Add provider_fee column to track what the payment provider (PalmPay) charges us
            if (!Schema::hasColumn('transactions', 'provider_fee')) {
                $table->decimal('provider_fee', 15, 2)->default(0)->after('fee')->comment('Fee charged by payment provider (PalmPay)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'provider_fee')) {
                $table->dropColumn('provider_fee');
            }
        });
    }
};
