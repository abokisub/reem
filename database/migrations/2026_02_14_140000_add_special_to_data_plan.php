<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpecialToDataPlan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_plan', function (Blueprint $table) {
            if (!Schema::hasColumn('data_plan', 'special')) {
                $table->string('special')->nullable()->after('agent');
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
        Schema::table('data_plan', function (Blueprint $table) {
            if (Schema::hasColumn('data_plan', 'special')) {
                $table->dropColumn('special');
            }
        });
    }
}
