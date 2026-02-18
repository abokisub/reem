<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeSettingsMultiTenant extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tables = [
            'airtime_discount',
            'cable_charge',
            'bill_charge',
            'cash_discount',
            'result_charge',
            'settings'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                    $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                }
            });

            // Seed existing companies with global settings
            $globalSetting = DB::table($tableName)->first();
            if ($globalSetting) {
                unset($globalSetting->id);
                if (isset($globalSetting->created_at))
                    $globalSetting->created_at = now();
                if (isset($globalSetting->updated_at))
                    $globalSetting->updated_at = now();

                $companies = DB::table('companies')->get();
                foreach ($companies as $company) {
                    $exists = DB::table($tableName)->where('company_id', $company->id)->exists();
                    if (!$exists) {
                        $newSetting = (array) $globalSetting;
                        $newSetting['company_id'] = $company->id;
                        DB::table($tableName)->insert($newSetting);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tables = [
            'airtime_discount',
            'cable_charge',
            'bill_charge',
            'cash_discount',
            'result_charge',
            'settings'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('company_id');
            });
        }
    }
}
