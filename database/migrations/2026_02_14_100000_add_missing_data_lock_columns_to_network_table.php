<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('network', function (Blueprint $table) {
            if (!Schema::hasColumn('network', 'network_sme2')) {
                $table->boolean('network_sme2')->default(1);
            }
            if (!Schema::hasColumn('network', 'network_datashare')) {
                $table->boolean('network_datashare')->default(1);
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
        Schema::table('network', function (Blueprint $table) {
            if (Schema::hasColumn('network', 'network_sme2')) {
                $table->dropColumn('network_sme2');
            }
            if (Schema::hasColumn('network', 'network_datashare')) {
                $table->dropColumn('network_datashare');
            }
        });
    }
};
