<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            // Add virtual_funding columns for VA deposit fees
            $table->string('virtual_funding_type')->default('PERCENT')->after('transfer_charge_cap');
            $table->decimal('virtual_funding_value', 20, 2)->default(0.70)->after('virtual_funding_type');
            $table->decimal('virtual_funding_cap', 20, 2)->default(500.00)->after('virtual_funding_value');
        });

        // Copy current transfer_charge values to virtual_funding as initial values
        $settings = DB::table('settings')->first();
        if ($settings) {
            DB::table('settings')->update([
                'virtual_funding_type' => $settings->transfer_charge_type ?? 'PERCENT',
                'virtual_funding_value' => $settings->transfer_charge_value ?? 0.70,
                'virtual_funding_cap' => $settings->transfer_charge_cap ?? 500.00,
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
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['virtual_funding_type', 'virtual_funding_value', 'virtual_funding_cap']);
        });
    }
};
