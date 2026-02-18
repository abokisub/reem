<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class IncreasePlanDayLengthInDataPlan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_plan', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement('ALTER TABLE data_plan MODIFY plan_day VARCHAR(50) NULL');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No easy revert without knowing original length, assuming it was shorter.
    }
}
