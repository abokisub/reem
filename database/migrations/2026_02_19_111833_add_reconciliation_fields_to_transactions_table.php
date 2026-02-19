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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('category')->index();
            $table->string('reconciliation_status')->default('not_started')->after('status')->index(); // not_started, matched, mismatched, manually_resolved
            $table->timestamp('reconciled_at')->nullable()->after('reconciliation_status');
        });

        // Initialize existing PalmPay transactions
        \DB::table('transactions')->whereNotNull('palmpay_reference')->update(['provider' => 'palmpay']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['provider', 'reconciliation_status', 'reconciled_at']);
        });
    }
};
