<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBillFieldsToBillChargeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bill_charge', function (Blueprint $table) {
            $table->string('bill')->nullable();
            $table->string('bill_min')->nullable();
            $table->string('bill_max')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bill_charge', function (Blueprint $table) {
            //
        });
    }
}
