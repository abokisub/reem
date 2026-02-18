<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - SAFE: Only adds columns, never drops data
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Check and add each column individually to avoid errors if they already exist
            
            if (!Schema::hasColumn('settings', 'auto_settlement_enabled')) {
                $table->boolean('auto_settlement_enabled')->default(true)->after('payout_palmpay_charge_cap');
            }
            
            if (!Schema::hasColumn('settings', 'settlement_delay_hours')) {
                $table->integer('settlement_delay_hours')->default(24)->after('auto_settlement_enabled');
            }
            
            if (!Schema::hasColumn('settings', 'settlement_skip_weekends')) {
                $table->boolean('settlement_skip_weekends')->default(true)->after('settlement_delay_hours');
            }
            
            if (!Schema::hasColumn('settings', 'settlement_skip_holidays')) {
                $table->boolean('settlement_skip_holidays')->default(true)->after('settlement_skip_weekends');
            }
            
            if (!Schema::hasColumn('settings', 'settlement_time')) {
                $table->time('settlement_time')->default('02:00:00')->after('settlement_skip_holidays');
            }
            
            if (!Schema::hasColumn('settings', 'settlement_minimum_amount')) {
                $table->decimal('settlement_minimum_amount', 20, 2)->default(100.00)->after('settlement_time');
            }
        });
        
        // Set default values for existing records
        DB::table('settings')->update([
            'auto_settlement_enabled' => true,
            'settlement_delay_hours' => 24,
            'settlement_skip_weekends' => true,
            'settlement_skip_holidays' => true,
            'settlement_time' => '02:00:00',
            'settlement_minimum_amount' => 100.00,
        ]);
    }

    /**
     * Reverse the migrations - SAFE: Only removes columns we added
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $columns = [
                'auto_settlement_enabled',
                'settlement_delay_hours',
                'settlement_skip_weekends',
                'settlement_skip_holidays',
                'settlement_time',
                'settlement_minimum_amount',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
