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
        Schema::table('cable_plan', function (Blueprint $table) {
            if (!Schema::hasColumn('cable_plan', 'added_by')) {
                $table->string('added_by', 100)->nullable()->after('plan_name');
            }
        });

        Schema::table('bill_plan', function (Blueprint $table) {
            if (!Schema::hasColumn('bill_plan', 'added_by')) {
                $table->string('added_by', 100)->nullable()->after('provider_name');
            }
        });

        Schema::table('stock_result_pin', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_result_pin', 'added_by')) {
                $table->string('added_by', 100)->nullable()->after('exam_serial');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cable_plan', function (Blueprint $table) {
            $table->dropColumn('added_by');
        });
        Schema::table('bill_plan', function (Blueprint $table) {
            $table->dropColumn('added_by');
        });
        Schema::table('stock_result_pin', function (Blueprint $table) {
            $table->dropColumn('added_by');
        });
    }
};
