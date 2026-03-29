<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUiSupportToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('settlement_batch_no')->nullable()->after('settlement_status');
            $table->timestamp('settlement_time')->nullable()->after('settlement_batch_no');
            $table->enum('refund_status', ['not_refunded', 'pending', 'successful', 'failed'])->default('not_refunded')->after('is_refunded');
            $table->enum('dispute_status', ['none', 'open', 'resolved', 'rejected'])->default('none')->after('refund_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['settlement_batch_no', 'settlement_time', 'refund_status', 'dispute_status']);
        });
    }
}
