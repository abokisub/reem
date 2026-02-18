<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNumbersToCashDiscountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cash_discount', function (Blueprint $table) {
            $table->string('mtn_number')->nullable();
            $table->string('glo_number')->nullable();
            $table->string('airtel_number')->nullable();
            $table->string('mobile_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cash_discount', function (Blueprint $table) {
            //
        });
    }
}
