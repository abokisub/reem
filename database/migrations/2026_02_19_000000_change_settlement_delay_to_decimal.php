<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Changes settlement_delay_hours from integer to decimal to support fractional hours
     * (e.g., 0.0167 for 1 minute, 0.5 for 30 minutes, 1.5 for 90 minutes)
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('settlement_delay_hours', 8, 4)->default(24)->change()
                ->comment('Hours to delay settlement (supports decimals: 0.0167=1min, 0.5=30min, 1=1h, 24=1day)');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('custom_settlement_delay_hours', 8, 4)->nullable()->change()
                ->comment('Custom settlement delay for this company (supports decimals)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->integer('settlement_delay_hours')->default(24)->change()
                ->comment('Hours to delay settlement (1, 7, 24, etc.)');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->integer('custom_settlement_delay_hours')->nullable()->change()
                ->comment('Custom settlement delay for this company');
        });
    }
};
