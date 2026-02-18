<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRetryFieldsToWebhookLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->timestamp('last_attempt_at')->nullable()->after('sent_at');
            $table->text('error_message')->nullable()->after('response_body');
        });

        // Change status from enum to string for more flexibility
        \DB::statement("ALTER TABLE webhook_logs MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            //
        });
    }
}
