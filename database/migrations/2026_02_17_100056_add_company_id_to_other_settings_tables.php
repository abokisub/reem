<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyIdToOtherSettingsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('card_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
        });

        Schema::table('service_charges', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
        });

        // Initialize master records for existing company (default ID 1)
        DB::table('card_settings')->whereNull('company_id')->update(['company_id' => 1]);
        DB::table('service_charges')->whereNull('company_id')->update(['company_id' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('card_settings', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });

        Schema::table('service_charges', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
}
