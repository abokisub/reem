<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_fee_settings', function (Blueprint $table) {
            $table->decimal('minimum_fee', 10, 2)->nullable()->after('cap_amount');
            $table->string('notes', 200)->nullable()->after('minimum_fee');
        });
    }

    public function down(): void
    {
        Schema::table('company_fee_settings', function (Blueprint $table) {
            $table->dropColumn(['minimum_fee', 'notes']);
        });
    }
};
