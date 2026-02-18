<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPreferenceColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_on_payment')->default(false);
            $table->boolean('email_customer_on_success')->default(false);
            $table->boolean('resend_failed_webhook')->default(true);
            $table->integer('resend_failed_webhook_count')->default(3);
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
            $table->dropColumn([
                'email_on_payment',
                'email_customer_on_success',
                'resend_failed_webhook',
                'resend_failed_webhook_count'
            ]);
        });
    }
}
