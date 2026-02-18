<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMissingChargeTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('cable_charge')) {
            Schema::create('cable_charge', function (Blueprint $table) {
                $table->id();
                $table->decimal('dstv', 10, 2)->default(0);
                $table->decimal('gotv', 10, 2)->default(0);
                $table->decimal('startimes', 10, 2)->default(0);
                $table->decimal('showmax', 10, 2)->default(0);
                $table->integer('direct')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bill_charge')) {
            Schema::create('bill_charge', function (Blueprint $table) {
                $table->id();
                $table->integer('direct')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('result_charge')) {
            Schema::create('result_charge', function (Blueprint $table) {
                $table->id();
                $table->decimal('waec', 10, 2)->default(0);
                $table->decimal('neco', 10, 2)->default(0);
                $table->decimal('nabteb', 10, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cable_charge');
        Schema::dropIfExists('bill_charge');
        Schema::dropIfExists('result_charge');
    }
}
