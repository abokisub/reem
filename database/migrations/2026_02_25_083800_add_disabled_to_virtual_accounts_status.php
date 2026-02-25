<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddDisabledToVirtualAccountsStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE virtual_accounts MODIFY COLUMN status ENUM('active', 'inactive', 'suspended', 'disabled') NOT NULL DEFAULT 'active'");
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
            // Update any 'disabled' status to 'inactive' before removing from ENUM
            DB::table('virtual_accounts')->where('status', 'disabled')->update(['status' => 'inactive']);
            DB::statement("ALTER TABLE virtual_accounts MODIFY COLUMN status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active'");
        }
    }
}
