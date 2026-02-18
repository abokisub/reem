<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKycRejectionReasonToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('kyc_rejection_reason')->nullable()->after('kyc_status');
            $table->timestamp('kyc_rejection_date')->nullable()->after('kyc_rejection_reason');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kyc_rejection_reason');
            $table->dropColumn('kyc_rejection_date');
        });
    }
}
