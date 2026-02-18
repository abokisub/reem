<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Add missing vendor columns to data_plan
        if (Schema::hasTable('data_plan')) {
            Schema::table('data_plan', function (Blueprint $table) {
                if (!Schema::hasColumn('data_plan', 'vtpass')) {
                    $table->string('vtpass', 100)->nullable()->after('autopilot');
                }
                if (!Schema::hasColumn('data_plan', 'habukhan')) {
                    $table->string('habukhan', 100)->nullable()->after('vtpass');
                }
            });
        }

        // 2. Add missing vendor columns to cable_plan
        if (Schema::hasTable('cable_plan')) {
            Schema::table('cable_plan', function (Blueprint $table) {
                if (!Schema::hasColumn('cable_plan', 'boltnet')) {
                    $table->string('boltnet', 100)->nullable()->after('smeplug');
                }
                if (!Schema::hasColumn('cable_plan', 'autopilot')) {
                    $table->string('autopilot', 100)->nullable()->after('boltnet');
                }
                if (!Schema::hasColumn('cable_plan', 'easyaccess')) {
                    $table->string('easyaccess', 100)->nullable()->after('autopilot');
                }
                if (!Schema::hasColumn('cable_plan', 'habukhan')) {
                    $table->string('habukhan', 100)->nullable()->after('easyaccess');
                }
            });
        }

        // 3. Add missing vendor columns to bill_plan
        if (Schema::hasTable('bill_plan')) {
            Schema::table('bill_plan', function (Blueprint $table) {
                if (!Schema::hasColumn('bill_plan', 'smeplug')) {
                    $table->string('smeplug', 100)->nullable()->after('vtpass');
                }
                if (!Schema::hasColumn('bill_plan', 'boltnet')) {
                    $table->string('boltnet', 100)->nullable()->after('smeplug');
                }
                if (!Schema::hasColumn('bill_plan', 'autopilot')) {
                    $table->string('autopilot', 100)->nullable()->after('boltnet');
                }
                if (!Schema::hasColumn('bill_plan', 'easyaccess')) {
                    $table->string('easyaccess', 100)->nullable()->after('autopilot');
                }
                if (!Schema::hasColumn('bill_plan', 'habukhan')) {
                    $table->string('habukhan', 100)->nullable()->after('easyaccess');
                }
            });
        }

        // 4. Add vendor columns to stock_result_pin (exam pins)
        if (Schema::hasTable('stock_result_pin')) {
            Schema::table('stock_result_pin', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_result_pin', 'vtpass')) {
                    $table->string('vtpass', 100)->nullable()->after('exam_name');
                }
                if (!Schema::hasColumn('stock_result_pin', 'easyaccess')) {
                    $table->string('easyaccess', 100)->nullable()->after('vtpass');
                }
                if (!Schema::hasColumn('stock_result_pin', 'habukhan')) {
                    $table->string('habukhan', 100)->nullable()->after('easyaccess');
                }
            });
        }

        // 5. Add vendor network ID mapping columns to network table
        if (Schema::hasTable('network')) {
            Schema::table('network', function (Blueprint $table) {
                if (!Schema::hasColumn('network', 'smeplug_id')) {
                    $table->integer('smeplug_id')->nullable();
                }
                if (!Schema::hasColumn('network', 'vtpass_id')) {
                    $table->string('vtpass_id', 50)->nullable();
                }
                if (!Schema::hasColumn('network', 'boltnet_id')) {
                    $table->integer('boltnet_id')->nullable();
                }
                if (!Schema::hasColumn('network', 'autopilot_id')) {
                    $table->integer('autopilot_id')->nullable();
                }
                if (!Schema::hasColumn('network', 'easyaccess_id')) {
                    $table->integer('easyaccess_id')->nullable();
                }
                if (!Schema::hasColumn('network', 'habukhan_id')) {
                    $table->integer('habukhan_id')->nullable();
                }
            });

            // Seed network vendor IDs
            DB::table('network')->where('network', 'MTN')->update([
                'smeplug_id' => 1,
                'vtpass_id' => 'mtn',
                'boltnet_id' => 1,
                'autopilot_id' => 1,
                'easyaccess_id' => 1,
                'habukhan_id' => 1,
            ]);

            DB::table('network')->where('network', 'AIRTEL')->update([
                'smeplug_id' => 4,
                'vtpass_id' => 'airtel',
                'boltnet_id' => 2,
                'autopilot_id' => 2,
                'easyaccess_id' => 2,
                'habukhan_id' => 2,
            ]);

            DB::table('network')->where('network', 'GLO')->update([
                'smeplug_id' => 2,
                'vtpass_id' => 'glo',
                'boltnet_id' => 3,
                'autopilot_id' => 3,
                'easyaccess_id' => 3,
                'habukhan_id' => 3,
            ]);

            DB::table('network')->where('network', '9MOBILE')->update([
                'smeplug_id' => 3,
                'vtpass_id' => '9mobile',
                'boltnet_id' => 4,
                'autopilot_id' => 4,
                'easyaccess_id' => 4,
                'habukhan_id' => 4,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove vendor columns from data_plan
        if (Schema::hasTable('data_plan')) {
            Schema::table('data_plan', function (Blueprint $table) {
                if (Schema::hasColumn('data_plan', 'vtpass')) {
                    $table->dropColumn('vtpass');
                }
                if (Schema::hasColumn('data_plan', 'habukhan')) {
                    $table->dropColumn('habukhan');
                }
            });
        }

        // Remove vendor columns from cable_plan
        if (Schema::hasTable('cable_plan')) {
            Schema::table('cable_plan', function (Blueprint $table) {
                if (Schema::hasColumn('cable_plan', 'boltnet')) {
                    $table->dropColumn('boltnet');
                }
                if (Schema::hasColumn('cable_plan', 'autopilot')) {
                    $table->dropColumn('autopilot');
                }
                if (Schema::hasColumn('cable_plan', 'easyaccess')) {
                    $table->dropColumn('easyaccess');
                }
                if (Schema::hasColumn('cable_plan', 'habukhan')) {
                    $table->dropColumn('habukhan');
                }
            });
        }

        // Remove vendor columns from bill_plan
        if (Schema::hasTable('bill_plan')) {
            Schema::table('bill_plan', function (Blueprint $table) {
                if (Schema::hasColumn('bill_plan', 'smeplug')) {
                    $table->dropColumn('smeplug');
                }
                if (Schema::hasColumn('bill_plan', 'boltnet')) {
                    $table->dropColumn('boltnet');
                }
                if (Schema::hasColumn('bill_plan', 'autopilot')) {
                    $table->dropColumn('autopilot');
                }
                if (Schema::hasColumn('bill_plan', 'easyaccess')) {
                    $table->dropColumn('easyaccess');
                }
                if (Schema::hasColumn('bill_plan', 'habukhan')) {
                    $table->dropColumn('habukhan');
                }
            });
        }

        // Remove vendor columns from stock_result_pin
        if (Schema::hasTable('stock_result_pin')) {
            Schema::table('stock_result_pin', function (Blueprint $table) {
                if (Schema::hasColumn('stock_result_pin', 'vtpass')) {
                    $table->dropColumn('vtpass');
                }
                if (Schema::hasColumn('stock_result_pin', 'easyaccess')) {
                    $table->dropColumn('easyaccess');
                }
                if (Schema::hasColumn('stock_result_pin', 'habukhan')) {
                    $table->dropColumn('habukhan');
                }
            });
        }

        // Remove vendor network ID columns from network
        if (Schema::hasTable('network')) {
            Schema::table('network', function (Blueprint $table) {
                if (Schema::hasColumn('network', 'smeplug_id')) {
                    $table->dropColumn('smeplug_id');
                }
                if (Schema::hasColumn('network', 'vtpass_id')) {
                    $table->dropColumn('vtpass_id');
                }
                if (Schema::hasColumn('network', 'boltnet_id')) {
                    $table->dropColumn('boltnet_id');
                }
                if (Schema::hasColumn('network', 'autopilot_id')) {
                    $table->dropColumn('autopilot_id');
                }
                if (Schema::hasColumn('network', 'easyaccess_id')) {
                    $table->dropColumn('easyaccess_id');
                }
                if (Schema::hasColumn('network', 'habukhan_id')) {
                    $table->dropColumn('habukhan_id');
                }
            });
        }
    }
};
