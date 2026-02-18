<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakePalmpayFieldsNullableInVirtualAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE virtual_accounts MODIFY palmpay_account_number VARCHAR(255) NULL');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE virtual_accounts MODIFY palmpay_account_name VARCHAR(255) NULL');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE virtual_accounts MODIFY customer_name VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            //
        });
    }
}
