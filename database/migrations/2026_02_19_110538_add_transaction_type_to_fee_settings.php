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
        Schema::table('company_fee_settings', function (Blueprint $table) {
            $table->string('transaction_type')->default('default')->after('company_id');
            // Remove unique constraint if it exists on company_id, and make it (company_id, transaction_type) unique
            $table->unique(['company_id', 'transaction_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_fee_settings', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'transaction_type']);
            $table->dropColumn('transaction_type');
        });
    }
};
