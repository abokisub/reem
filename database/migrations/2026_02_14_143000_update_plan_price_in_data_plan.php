<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePlanPriceInDataPlan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_plan', function (Blueprint $table) {
            // Raw SQL because doctrine/dbal is missing
            DB::statement('ALTER TABLE data_plan MODIFY plan_price VARCHAR(255) NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Reverting to not null might fail if nulls were inserted, so we generally leave it or handle carefully.
        // For now, we won't strictly revert constraint to avoid data loss issues during rollback.
    }
}
