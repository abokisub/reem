<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingAdminColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_image')->nullable()->after('status');
            $table->string('id_card_path')->nullable()->after('kyc_documents');
            $table->string('utility_bill_path')->nullable()->after('id_card_path');
            $table->text('xixapay_kyc_data')->nullable()->after('kyc_submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profile_image', 'id_card_path', 'utility_bill_path', 'xixapay_kyc_data']);
        });
    }
}
