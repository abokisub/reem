<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreDataCardAndRechargeCardTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_data_card', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('network')->nullable();
            $table->text('pin')->nullable();
            $table->text('serial')->nullable();
            $table->boolean('plan_status')->default(1);
            $table->string('buyer_username')->nullable();
            $table->string('added_date')->nullable();
            $table->string('bought_date')->nullable();
            $table->unsignedBigInteger('data_card_id')->nullable();
            $table->timestamps();
        });

        Schema::create('store_recharge_card', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('network')->nullable();
            $table->text('pin')->nullable();
            $table->text('serial')->nullable();
            $table->boolean('plan_status')->default(1);
            $table->string('buyer_username')->nullable();
            $table->string('added_date')->nullable();
            $table->string('bought_date')->nullable();
            $table->unsignedBigInteger('recharge_card_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('store_data_card');
        Schema::dropIfExists('store_recharge_card');
    }
}
