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
        // Create service_lock table
        if (!Schema::hasTable('service_lock')) {
            Schema::create('service_lock', function (Blueprint $table) {
                $table->id();
                $table->boolean('airtime')->default(false); // false = unlocked, true = locked
                $table->boolean('data')->default(false);
                $table->boolean('cable')->default(false);
                $table->boolean('bill')->default(false); // electricity
                $table->boolean('result')->default(false); // exam pins
                $table->boolean('data_card')->default(false);
                $table->boolean('recharge_card')->default(false);
                $table->boolean('virtual_accounts')->default(false); // Palmpay
                $table->boolean('bulksms')->default(false);
                $table->boolean('cash')->default(false);
                $table->timestamps();
            });

            // Seed initial row (all services unlocked)
            DB::table('service_lock')->insert([
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Add missing selection tables if they don't exist

        // Result/Exam selection table
        if (!Schema::hasTable('result_sel')) {
            Schema::create('result_sel', function (Blueprint $table) {
                $table->id();
                $table->string('waec')->default('Habukhan1');
                $table->string('neco')->default('Habukhan1');
                $table->string('nabteb')->default('Habukhan1');
                $table->timestamps();
            });
            DB::table('result_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Data card selection table
        if (!Schema::hasTable('data_card_sel')) {
            Schema::create('data_card_sel', function (Blueprint $table) {
                $table->id();
                $table->string('data_card')->default('Habukhan1');
                $table->timestamps();
            });
            DB::table('data_card_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Recharge card selection table
        if (!Schema::hasTable('recharge_card_sel')) {
            Schema::create('recharge_card_sel', function (Blueprint $table) {
                $table->id();
                $table->string('recharge_card')->default('Habukhan1');
                $table->timestamps();
            });
            DB::table('recharge_card_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Virtual account selection table
        if (!Schema::hasTable('virtual_account_sel')) {
            Schema::create('virtual_account_sel', function (Blueprint $table) {
                $table->id();
                $table->string('virtual_account')->default('palmpay');
                $table->timestamps();
            });
            DB::table('virtual_account_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Cash selection table
        if (!Schema::hasTable('cash_sel')) {
            Schema::create('cash_sel', function (Blueprint $table) {
                $table->id();
                $table->string('cash')->default('Habukhan1');
                $table->timestamps();
            });
            DB::table('cash_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_lock');
        Schema::dropIfExists('result_sel');
        Schema::dropIfExists('data_card_sel');
        Schema::dropIfExists('recharge_card_sel');
        Schema::dropIfExists('virtual_account_sel');
        Schema::dropIfExists('cash_sel');
    }
};
