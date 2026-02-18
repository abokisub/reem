<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Force drop tables to ensure correct schema recreation
        Schema::dropIfExists('habukhan_key');
        Schema::dropIfExists('card_settings');
        Schema::dropIfExists('general');
        Schema::dropIfExists('feature');
        Schema::dropIfExists('cash_discount');
        Schema::dropIfExists('network');
        Schema::dropIfExists('airtime_discount');
        Schema::dropIfExists('request');
        Schema::dropIfExists('message');
        Schema::dropIfExists('wallet_funding');

        // 1. Drop the incorrect key-value 'settings' table and recreate it as a column-based table
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'key')) {
            Schema::drop('settings');
        }

        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                // Feature Toggles
                $table->integer('monnify_atm')->default(1);
                $table->integer('monnify')->default(1);
                $table->integer('referral')->default(1);
                $table->integer('is_verify_email')->default(0);
                $table->integer('is_feature')->default(1);
                $table->integer('wema')->default(1);
                $table->integer('kolomoni_mfb')->default(1);
                $table->integer('fed')->default(1);
                $table->integer('str')->default(1);
                $table->integer('bulksms')->default(1);
                $table->integer('allow_pin')->default(1);
                $table->integer('bill')->default(1);
                $table->integer('bank_transfer')->default(1);
                $table->integer('paystack')->default(1);
                $table->integer('allow_limit')->default(1);
                $table->integer('stock')->default(1);
                $table->integer('card_ngn_lock')->default(0);
                $table->integer('card_usd_lock')->default(0);

                // Charges
                $table->decimal('monnify_charge', 20, 2)->default(0);
                $table->decimal('xixapay_charge', 20, 2)->default(0);
                $table->decimal('paystack_charge', 20, 2)->default(0);
                $table->string('transfer_charge_type')->default('FLAT');
                $table->decimal('transfer_charge_value', 20, 2)->default(0);
                $table->decimal('transfer_charge_cap', 20, 2)->default(0);

                // App Info
                $table->string('version')->default('1.0.0');
                $table->string('update_url')->nullable();
                $table->string('playstore_url')->nullable();
                $table->string('appstore_url')->nullable();
                $table->string('app_update_title')->nullable();
                $table->text('app_update_desc')->nullable();
                $table->boolean('maintenance')->default(false);

                // Content
                $table->text('notif_message')->nullable();

                // Prices
                $table->decimal('affliate_price', 20, 2)->default(0);
                $table->decimal('awuf_price', 20, 2)->default(0);
                $table->decimal('agent_price', 20, 2)->default(0);
                $table->decimal('api_price', 20, 2)->default(0);

                // Gateway Status
                $table->boolean('palmpay_enabled')->default(true);
                $table->boolean('monnify_enabled')->default(true);
                $table->boolean('wema_enabled')->default(true);
                $table->boolean('xixapay_enabled')->default(true);
                $table->string('default_virtual_account')->default('palmpay');

                $table->timestamps();
            });

            DB::table('settings')->insert([
                'id' => 1,
                'notif_message' => 'Welcome to PointPay!',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Create feature table
        Schema::create('feature', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
        });

        // 3. Create cash_discount table
        Schema::create('cash_discount', function (Blueprint $table) {
            $table->id();
            $table->decimal('mtn', 10, 2)->default(80);
            $table->decimal('glo', 10, 2)->default(70);
            $table->decimal('airtel', 10, 2)->default(70);
            $table->decimal('mobile', 10, 2)->default(70);
            $table->integer('mtn_status')->default(1);
            $table->integer('glo_status')->default(1);
            $table->integer('airtel_status')->default(1);
            $table->integer('mobile_status')->default(1);
            $table->timestamps();
        });
        DB::table('cash_discount')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        // 4. Create network table
        Schema::create('network', function (Blueprint $table) {
            $table->id();
            $table->string('network')->nullable();
            $table->string('network_vtu')->nullable();
            $table->string('network_share')->nullable();
            $table->string('network_sme')->nullable();
            $table->string('network_cg')->nullable();
            $table->string('network_g')->nullable();
            $table->string('plan_id')->nullable();
            $table->integer('cash')->default(1);
            $table->integer('data_card')->default(1);
            $table->integer('recharge_card')->default(1);
            $table->timestamps();
        });

        // 5. Create airtime_discount table
        Schema::create('airtime_discount', function (Blueprint $table) {
            $table->id();
            // VTU
            $table->decimal('mtn_vtu_smart', 10, 2)->default(0);
            $table->decimal('mtn_vtu_awuf', 10, 2)->default(0);
            $table->decimal('mtn_vtu_agent', 10, 2)->default(0);
            $table->decimal('mtn_vtu_api', 10, 2)->default(0);
            $table->decimal('mtn_vtu_special', 10, 2)->default(0);
            $table->decimal('airtel_vtu_smart', 10, 2)->default(0);
            $table->decimal('airtel_vtu_awuf', 10, 2)->default(0);
            $table->decimal('airtel_vtu_agent', 10, 2)->default(0);
            $table->decimal('airtel_vtu_api', 10, 2)->default(0);
            $table->decimal('airtel_vtu_special', 10, 2)->default(0);
            $table->decimal('glo_vtu_smart', 10, 2)->default(0);
            $table->decimal('glo_vtu_awuf', 10, 2)->default(0);
            $table->decimal('glo_vtu_agent', 10, 2)->default(0);
            $table->decimal('glo_vtu_api', 10, 2)->default(0);
            $table->decimal('glo_vtu_special', 10, 2)->default(0);
            $table->decimal('mobile_vtu_smart', 10, 2)->default(0);
            $table->decimal('mobile_vtu_awuf', 10, 2)->default(0);
            $table->decimal('mobile_vtu_agent', 10, 2)->default(0);
            $table->decimal('mobile_vtu_api', 10, 2)->default(0);
            $table->decimal('mobile_vtu_special', 10, 2)->default(0);
            // Share
            $table->decimal('mtn_share_smart', 10, 2)->default(0);
            $table->decimal('mtn_share_awuf', 10, 2)->default(0);
            $table->decimal('mtn_share_agent', 10, 2)->default(0);
            $table->decimal('mtn_share_api', 10, 2)->default(0);
            $table->decimal('mtn_share_special', 10, 2)->default(0);
            $table->decimal('airtel_share_smart', 10, 2)->default(0);
            $table->decimal('airtel_share_awuf', 10, 2)->default(0);
            $table->decimal('airtel_share_agent', 10, 2)->default(0);
            $table->decimal('airtel_share_api', 10, 2)->default(0);
            $table->decimal('airtel_share_special', 10, 2)->default(0);
            $table->decimal('glo_share_smart', 10, 2)->default(0);
            $table->decimal('glo_share_awuf', 10, 2)->default(0);
            $table->decimal('glo_share_agent', 10, 2)->default(0);
            $table->decimal('glo_share_api', 10, 2)->default(0);
            $table->decimal('glo_share_special', 10, 2)->default(0);
            $table->decimal('mobile_share_smart', 10, 2)->default(0);
            $table->decimal('mobile_share_awuf', 10, 2)->default(0);
            $table->decimal('mobile_share_agent', 10, 2)->default(0);
            $table->decimal('mobile_share_api', 10, 2)->default(0);
            $table->decimal('mobile_share_special', 10, 2)->default(0);
            // Limits
            $table->decimal('max_airtime', 20, 2)->default(50000);
            $table->decimal('min_airtime', 20, 2)->default(100);
            $table->timestamps();
        });
        DB::table('airtime_discount')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        // 6. Create request table
        Schema::create('request', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable();
            $table->string('date')->nullable();
            $table->string('transid')->nullable();
            $table->string('status')->default('pending');
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });

        // 7. Create message table
        Schema::create('message', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->text('message')->nullable();
            $table->decimal('oldbal', 20, 2)->default(0);
            $table->decimal('newbal', 20, 2)->default(0);
            $table->string('habukhan_date')->nullable();
            $table->string('transid')->nullable();
            $table->integer('plan_status')->default(0);
            $table->string('role')->nullable();
            $table->timestamps();
        });

        // 8. Create wallet_funding table
        Schema::create('wallet_funding', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable();
            $table->timestamps();
        });

        // 9. Add 'ref' column to users table
        if (!Schema::hasColumn('users', 'ref')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('ref')->nullable()->after('email');
            });
        }

        // 10. Create habukhan_key table (Consolidated)
        Schema::create('habukhan_key', function (Blueprint $table) {
            $table->id();
            $table->string('autopilot_key')->nullable();
            $table->string('mon_app_key')->nullable();
            $table->string('mon_sk_key')->nullable();
            $table->string('mon_con_num')->nullable();
            $table->decimal('default_limit', 20, 2)->default(1000.00);
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->decimal('min', 20, 2)->default(0);
            $table->decimal('max', 20, 2)->default(0);
            $table->timestamps();
        });
        DB::table('habukhan_key')->insert([
            'id' => 1,
            'autopilot_key' => 'test_key',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 11. Create card_settings table (Restored)
        Schema::create('card_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('ngn_creation_fee', 20, 2)->default(0);
            $table->decimal('usd_creation_fee', 20, 2)->default(0);
            $table->decimal('ngn_rate', 20, 2)->default(1);
            $table->decimal('funding_fee_percent', 5, 2)->default(0);
            $table->decimal('usd_failed_tx_fee', 20, 2)->default(0);
            $table->decimal('ngn_funding_fee_percent', 5, 2)->default(0);
            $table->decimal('usd_funding_fee_percent', 5, 2)->default(0);
            $table->decimal('ngn_failed_tx_fee', 20, 2)->default(0);
            $table->timestamps();
        });
        DB::table('card_settings')->insert([
            'id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 12. Add missing columns to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'date')) {
                $table->dateTime('date')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('users', 'kyc')) {
                $table->string('kyc')->default('0')->after('status');
            }
            if (!Schema::hasColumn('users', 'user_limit')) {
                $table->decimal('user_limit', 20, 2)->default(50000)->after('status');
            }
            if (!Schema::hasColumn('users', 'pin')) {
                $table->string('pin')->nullable()->after('user_limit');
            }
        });

        // 13. Create general table
        Schema::create('general', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('PointPay');
            $table->string('app_phone')->nullable();
            $table->string('app_email')->nullable();
            $table->text('app_address')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('tiktok')->nullable();
            $table->timestamps();
        });
        DB::table('general')->insert([
            'app_name' => 'PointPay',
            'app_phone' => '08000000000',
            'app_email' => 'support@pointpay.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('habukhan_key');
        Schema::dropIfExists('card_settings');
        Schema::dropIfExists('general');
        Schema::dropIfExists('feature');
        Schema::dropIfExists('cash_discount');
        Schema::dropIfExists('network');
        Schema::dropIfExists('airtime_discount');
        Schema::dropIfExists('request');
        Schema::dropIfExists('message');
        Schema::dropIfExists('wallet_funding');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ref', 'date', 'kyc']);
        });
    }
};
