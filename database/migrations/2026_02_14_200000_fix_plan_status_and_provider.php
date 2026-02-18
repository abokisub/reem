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
        // 1. Add plan_status to cable_plan
        Schema::table('cable_plan', function (Blueprint $table) {
            if (!Schema::hasColumn('cable_plan', 'plan_status')) {
                $table->boolean('plan_status')->default(1)->after('plan_price');
            }
        });

        // 2. Make provider and provider_name nullable in bill_plan
        Schema::table('bill_plan', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE bill_plan MODIFY provider VARCHAR(50) NULL");
                DB::statement("ALTER TABLE bill_plan MODIFY provider_name VARCHAR(100) NULL");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cable_plan', function (Blueprint $table) {
            if (Schema::hasColumn('cable_plan', 'plan_status')) {
                $table->dropColumn('plan_status');
            }
        });

        Schema::table('bill_plan', function (Blueprint $table) {
            // Revert to not null (caution: this might fail if null values exist)
            DB::statement("ALTER TABLE bill_plan MODIFY provider VARCHAR(50) NOT NULL");
            DB::statement("ALTER TABLE bill_plan MODIFY provider_name VARCHAR(100) NOT NULL");
        });
    }
};
