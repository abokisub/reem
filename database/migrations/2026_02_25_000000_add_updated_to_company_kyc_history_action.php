<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddUpdatedToCompanyKycHistoryAction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE company_kyc_history MODIFY COLUMN action ENUM('submitted','approved','rejected','resubmitted','credentials_regenerated','api_status_updated','webhook_updated','activated','suspended','updated') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE company_kyc_history MODIFY COLUMN action ENUM('submitted','approved','rejected','resubmitted','credentials_regenerated','api_status_updated','webhook_updated','activated','suspended') NOT NULL");
        }
    }
}
