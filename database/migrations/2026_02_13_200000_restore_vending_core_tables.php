<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. data_plan
        if (!Schema::hasTable('data_plan')) {
            Schema::create('data_plan', function (Blueprint $table) {
                $table->id();
                $table->string('network', 20);
                $table->string('plan_id', 50)->unique();
                $table->string('plan_name', 100);
                $table->string('plan_size', 50);
                $table->integer('plan_day'); // validity
                $table->decimal('plan_price', 10, 2);
                $table->enum('plan_type', ['GIFTING', 'COOPERATE GIFTING', 'SME', 'DIRECT'])->default('DIRECT');
                $table->boolean('plan_status')->default(true);
                $table->decimal('smart', 10, 2)->default(0.00);
                $table->decimal('agent', 10, 2)->default(0.00);
                $table->decimal('awuf', 10, 2)->default(0.00);
                $table->decimal('api', 10, 2)->default(0.00);
                
                // Vendor IDs
                $table->string('smeplug', 100)->nullable();
                $table->string('msplug', 100)->nullable();
                $table->string('boltnet', 100)->nullable();
                $table->string('easyaccess', 100)->nullable();
                $table->string('autopilot', 100)->nullable();
                $table->string('simserver', 100)->nullable();
                
                // Pricing Slots (legacy support)
                $table->decimal('habukhan1', 10, 2)->nullable();
                $table->decimal('habukhan2', 10, 2)->nullable();
                $table->decimal('habukhan3', 10, 2)->nullable();
                $table->decimal('habukhan4', 10, 2)->nullable();
                $table->decimal('habukhan5', 10, 2)->nullable();
                
                $table->timestamps();
            });
        }

        // 2. other_api
        if (!Schema::hasTable('other_api')) {
            Schema::create('other_api', function (Blueprint $table) {
                $table->id();
                $table->text('smeplug')->nullable(); // Bearer Token
                $table->text('easy_access')->nullable(); // Token
                $table->string('vtpass_username')->nullable();
                $table->string('vtpass_password')->nullable();
                $table->string('simserver')->nullable(); // API Key?
                $table->string('hollatag')->nullable();
                $table->string('msplug')->nullable();
                $table->string('boltnet')->nullable();
                $table->timestamps();
            });
            // Seed initial row
            DB::table('other_api')->insert(['created_at' => now(), 'updated_at' => now()]);
        }

        // 3. cable (and related)
         if (!Schema::hasTable('cable')) {
            Schema::create('cable', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('username', 100);
                $table->string('transid', 100)->unique();
                $table->string('cable_name', 50); // DSTV, GOTV, STARTIMES
                $table->string('smart_card_number', 50);
                $table->string('plan_name', 100);
                $table->decimal('amount', 10, 2);
                $table->decimal('balance', 10, 2);
                $table->string('status', 20)->default('process'); // success, fail, process
                $table->timestamp('date');
                $table->string('token', 255)->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('cable_plan')) {
             Schema::create('cable_plan', function (Blueprint $table) {
                $table->id();
                $table->string('cable_name', 50);
                $table->string('plan_id', 50);
                $table->string('plan_name', 100);
                $table->decimal('amount', 10, 2);
                $table->boolean('status')->default(true);
                // Mappings
                $table->string('vtpass', 100)->nullable();
                $table->string('smeplug', 100)->nullable();
                $table->timestamps();
            });
        }
        
        // 4. bill (and related)
        if (!Schema::hasTable('bill')) {
            Schema::create('bill', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('username', 100);
                $table->string('transid', 100)->unique();
                $table->string('bill_type', 50); // NEPA
                $table->string('provider', 50); // IKEDC, EKEDC etc
                $table->string('meter_number', 50);
                $table->decimal('amount', 10, 2);
                $table->decimal('balance', 10, 2);
                $table->string('status', 20)->default('process');
                $table->string('token', 255)->nullable();
                $table->timestamp('date');
                 $table->timestamps();
            });
        }
         if (!Schema::hasTable('bill_plan')) {
             Schema::create('bill_plan', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 50);
                $table->string('provider_name', 100);
                 // Mappings
                $table->string('vtpass', 100)->nullable();
                $table->timestamps();
            });
        }

        // 5. virtual_cards (Inferred)
        // Note: There is a 'virtual_accounts' table but user asked for 'Virtual card'.
        // Checking for 'virtual_cards' specifically.
        if (!Schema::hasTable('virtual_cards')) {
            Schema::create('virtual_cards', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('card_id')->unique();
                $table->string('card_name');
                $table->string('card_number');
                $table->string('cvv');
                $table->string('expiry');
                $table->string('status')->default('active');
                $table->decimal('balance', 10, 2)->default(0.00);
                $table->timestamps();
            });
        }

        // 6. stock_result_pin (Inferred from ExamSend.php)
         if (!Schema::hasTable('stock_result_pin')) {
            Schema::create('stock_result_pin', function (Blueprint $table) {
                $table->id();
                $table->string('exam_name', 50); // WAEC, NECO
                $table->string('exam_pin', 100);
                $table->string('exam_serial', 100)->nullable();
                $table->integer('plan_status')->default(0); // 0=available, 1=sold
                $table->string('buyer_username', 100)->nullable();
                $table->timestamp('bought_date')->nullable();
                $table->timestamps();
            });
        }

        // 7. network
        if (!Schema::hasTable('network')) {
             Schema::create('network', function (Blueprint $table) {
                $table->id();
                $table->string('network', 20)->unique(); // MTN, GLO, AIRTEL, 9MOBILE
                $table->string('alias', 20)->nullable();
                $table->boolean('status')->default(true);
                 // Codes for Airtime
                $table->string('mtn_code', 10)->nullable();
                $table->string('glo_code', 10)->nullable();
                $table->string('airtel_code', 10)->nullable();
                $table->string('mobile_code', 10)->nullable();
                $table->timestamps();
            });
             // Seed defaults
             DB::table('network')->insertOrIgnore([
                 ['network' => 'MTN', 'status' => 1],
                 ['network' => 'GLO', 'status' => 1],
                 ['network' => 'AIRTEL', 'status' => 1],
                 ['network' => '9MOBILE', 'status' => 1],
             ]);
        }

        // 8. data_card_plan
         if (!Schema::hasTable('data_card_plan')) {
            Schema::create('data_card_plan', function (Blueprint $table) {
                $table->id();
                $table->string('network', 20);
                $table->string('plan_id', 50);
                $table->string('plan_name', 100);
                $table->decimal('amount', 10, 2);
                $table->integer('quantity_available')->default(0);
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }
        
        // 9. data_card (transaction table)
         if (!Schema::hasTable('data_card')) {
             Schema::create('data_card', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('username', 100);
                $table->string('transid', 100)->unique();
                $table->string('network', 20);
                $table->string('plan_name', 100);
                $table->integer('quantity');
                $table->decimal('amount', 10, 2);
                $table->decimal('newbal', 10, 2);
                $table->string('plan_status', 20)->default('process');
                $table->timestamp('date');
                $table->text('cards')->nullable(); // JSON or comma separated pins
                $table->timestamps();
            });
         }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_plan');
        Schema::dropIfExists('other_api');
        Schema::dropIfExists('cable');
        Schema::dropIfExists('cable_plan');
        Schema::dropIfExists('bill');
        Schema::dropIfExists('bill_plan');
        Schema::dropIfExists('virtual_cards');
        Schema::dropIfExists('stock_result_pin');
        Schema::dropIfExists('network');
        Schema::dropIfExists('data_card_plan');
        Schema::dropIfExists('data_card');
    }
};
