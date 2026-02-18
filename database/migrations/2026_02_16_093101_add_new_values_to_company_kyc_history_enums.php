<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewValuesToCompanyKycHistoryEnums extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE company_kyc_history MODIFY COLUMN section ENUM('business_info','account_info','bvn_info','documents','board_members','all') NOT NULL");
            DB::statement("ALTER TABLE company_kyc_history MODIFY COLUMN action ENUM('submitted','approved','rejected','resubmitted','credentials_regenerated','api_status_updated','webhook_updated','activated','suspended') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE company_kyc_history MODIFY COLUMN section ENUM('business_info','account_info','bvn_info','documents','board_members') NOT NULL");
        DB::statement("ALTER TABLE company_kyc_history MODIFY COLUMN action ENUM('submitted','approved','rejected','resubmitted') NOT NULL");
    }
}
