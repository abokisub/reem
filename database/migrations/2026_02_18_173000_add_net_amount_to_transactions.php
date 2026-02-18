<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNetAmountToTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Add net_amount column if it doesn't exist
            if (!Schema::hasColumn('transactions', 'net_amount')) {
                $table->decimal('net_amount', 15, 2)->after('fee')->default(0)->comment('Amount after deducting fees');
            }
            
            // Add total_amount column if it doesn't exist
            if (!Schema::hasColumn('transactions', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->after('net_amount')->default(0)->comment('Total transaction amount');
            }
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
            if (Schema::hasColumn('transactions', 'net_amount')) {
                $table->dropColumn('net_amount');
            }
            
            if (Schema::hasColumn('transactions', 'total_amount')) {
                $table->dropColumn('total_amount');
            }
        });
    }
}
