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
        Schema::table('cable_plan', function (Blueprint $table) {
            // Modify amount column to be nullable
            // Using raw SQL for MySQL/MariaDB to avoid doctrine/dbal dependency for modifications
            if (Schema::hasColumn('cable_plan', 'amount')) {
                DB::statement("ALTER TABLE cable_plan MODIFY amount VARCHAR(255) NULL");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cable_plan', function (Blueprint $table) {
            // Revert to not null (caution: this might fail if null values exist)
            if (Schema::hasColumn('cable_plan', 'amount')) {
                DB::statement("ALTER TABLE cable_plan MODIFY amount VARCHAR(255) NOT NULL");
            }
        });
    }
};
