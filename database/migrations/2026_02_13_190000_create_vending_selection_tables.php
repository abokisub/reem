<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Create 'sel' table for API providers
        Schema::create('sel', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->boolean('data')->default(0);
            $table->boolean('airtime')->default(0);
            $table->boolean('cable')->default(0);
            $table->boolean('bulksms')->default(0);
            $table->boolean('bill')->default(0);
            $table->boolean('result')->default(0);
            $table->boolean('data_card')->default(0);
            $table->boolean('recharge_card')->default(0);
            $table->boolean('cash')->default(0);
            $table->timestamps();
        });

        // 2. Populate 'sel' table
        $apis = [
            ['name' => 'Habukhan 1', 'key' => 'Habukhan1', 'all' => 1],
            ['name' => 'SME Plug', 'key' => 'smeplug', 'data' => 1, 'airtime' => 1, 'cable' => 1, 'bill' => 1],
            ['name' => 'MSPLUG', 'key' => 'msplug', 'data' => 1, 'airtime' => 1, 'cable' => 1, 'bill' => 1],
            ['name' => 'Vtpass', 'key' => 'vtpass', 'all' => 1],
            ['name' => 'Hollatag', 'key' => 'hollatag', 'bulksms' => 1],
            ['name' => 'Easy Access', 'key' => 'easyaccess', 'all' => 1],
            ['name' => 'Boltnet', 'key' => 'boltnet', 'data' => 1, 'airtime' => 1],
            ['name' => 'Autopilot', 'key' => 'autopilot', 'data' => 1, 'airtime' => 1],
        ];

        foreach ($apis as $api) {
            $all = $api['all'] ?? 0;
            DB::table('sel')->insert([
                'name' => $api['name'],
                'key' => $api['key'],
                'data' => $all || ($api['data'] ?? 0),
                'airtime' => $all || ($api['airtime'] ?? 0),
                'cable' => $all || ($api['cable'] ?? 0),
                'bulksms' => $all || ($api['bulksms'] ?? 0),
                'bill' => $all || ($api['bill'] ?? 0),
                'result' => $all || ($api['result'] ?? 0),
                'data_card' => $all || ($api['data_card'] ?? 0),
                'recharge_card' => $all || ($api['recharge_card'] ?? 0),
                'cash' => $all || ($api['cash'] ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Create specific selection tables
        Schema::create('data_sel', function (Blueprint $table) {
            $table->id();
            $table->string('mtn_sme')->default('Habukhan1');
            $table->string('airtel_sme')->default('Habukhan1');
            $table->string('glo_sme')->default('Habukhan1');
            $table->string('mobile_sme')->default('Habukhan1');
            $table->string('mtn_cg')->default('Habukhan1');
            $table->string('airtel_cg')->default('Habukhan1');
            $table->string('glo_cg')->default('Habukhan1');
            $table->string('mobile_cg')->default('Habukhan1');
            $table->string('mtn_g')->default('Habukhan1');
            $table->string('airtel_g')->default('Habukhan1');
            $table->string('glo_g')->default('Habukhan1');
            $table->string('mobile_g')->default('Habukhan1');
            $table->string('mtn_sme2')->default('Habukhan1');
            $table->string('airtel_sme2')->default('Habukhan1');
            $table->string('glo_sme2')->default('Habukhan1');
            $table->string('mobile_sme2')->default('Habukhan1');
            $table->string('mtn_datashare')->default('Habukhan1');
            $table->string('airtel_datashare')->default('Habukhan1');
            $table->string('glo_datashare')->default('Habukhan1');
            $table->string('mobile_datashare')->default('Habukhan1');
            $table->timestamps();
        });
        DB::table('data_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('airtime_sel', function (Blueprint $table) {
            $table->id();
            $table->string('mtn_vtu')->default('Habukhan1');
            $table->string('airtel_vtu')->default('Habukhan1');
            $table->string('glo_vtu')->default('Habukhan1');
            $table->string('mobile_vtu')->default('Habukhan1');
            $table->string('mtn_share')->default('Habukhan1');
            $table->string('airtel_share')->default('Habukhan1');
            $table->string('glo_share')->default('Habukhan1');
            $table->string('mobile_share')->default('Habukhan1');
            $table->timestamps();
        });
        DB::table('airtime_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('cable_sel', function (Blueprint $table) {
            $table->id();
            $table->string('dstv')->default('Habukhan1');
            $table->string('gotv')->default('Habukhan1');
            $table->string('startime')->default('Habukhan1');
            $table->string('showmax')->default('Habukhan1');
            $table->timestamps();
        });
        DB::table('cable_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('bill_sel', function (Blueprint $table) {
            $table->id();
            $table->string('bill')->default('Habukhan1');
            $table->timestamps();
        });
        DB::table('bill_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('bulksms_sel', function (Blueprint $table) {
            $table->id();
            $table->string('bulksms')->default('Habukhan1');
            $table->timestamps();
        });
        DB::table('bulksms_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('exam_sel', function (Blueprint $table) {
            $table->id();
            $table->string('waec')->default('Habukhan1');
            $table->string('neco')->default('Habukhan1');
            $table->string('nabteb')->default('Habukhan1');
            $table->timestamps();
        });
        DB::table('exam_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('data_card_sel', function (Blueprint $table) {
            $table->id();
            $table->string('mtn')->default('Habukhan1');
            $table->string('airtel')->default('Habukhan1');
            $table->string('glo')->default('Habukhan1');
            $table->string('mobile')->default('Habukhan1');
            $table->timestamps();
        });
        DB::table('data_card_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('recharge_card_sel', function (Blueprint $table) {
            $table->id();
            $table->string('mtn')->default('Habukhan1');
            $table->string('airtel')->default('Habukhan1');
            $table->string('glo')->default('Habukhan1');
            $table->string('mobile')->default('Habukhan1');
            $table->timestamps();
        });
        DB::table('recharge_card_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('cash_sel', function (Blueprint $table) {
            $table->id();
            $table->string('mtn')->default('Habukhan1');
            $table->string('airtel')->default('Habukhan1');
            $table->string('glo')->default('Habukhan1');
            $table->string('mobile')->default('Habukhan1');
            $table->timestamps();
        });
        DB::table('cash_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('bank_transfer_sel', function (Blueprint $table) {
            $table->id();
            $table->string('bank_transfer')->default('Habukhan1');
            $table->timestamps();
        });
        DB::table('bank_transfer_sel')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        // 4. Disable Monnify and Wema in settings
        DB::table('settings')->where('id', 1)->update([
            'monnify_enabled' => false,
            'wema_enabled' => false,
            'default_virtual_account' => 'palmpay'
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sel');
        Schema::dropIfExists('data_sel');
        Schema::dropIfExists('airtime_sel');
        Schema::dropIfExists('cable_sel');
        Schema::dropIfExists('bill_sel');
        Schema::dropIfExists('bulksms_sel');
        Schema::dropIfExists('exam_sel');
        Schema::dropIfExists('data_card_sel');
        Schema::dropIfExists('recharge_card_sel');
        Schema::dropIfExists('cash_sel');
        Schema::dropIfExists('bank_transfer_sel');
    }
};
