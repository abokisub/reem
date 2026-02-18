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
            if (!Schema::hasColumn('cable_plan', 'plan_price')) {
                // Using string to likely match flexibility of other price fields found in recent migrations
                // or decimal if strictly monetary. Given data_plan history, string/varchar is safer for now.
                $table->string('plan_price')->nullable();
            }
        });

        Schema::table('bill_plan', function (Blueprint $table) {
            if (!Schema::hasColumn('bill_plan', 'disco_name')) {
                $table->string('disco_name')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cable_plan', function (Blueprint $table) {
            if (Schema::hasColumn('cable_plan', 'plan_price')) {
                $table->dropColumn('plan_price');
            }
        });

        Schema::table('bill_plan', function (Blueprint $table) {
            if (Schema::hasColumn('bill_plan', 'disco_name')) {
                $table->dropColumn('disco_name');
            }
        });
    }
};
