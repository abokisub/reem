<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSupportUrlsToGeneralTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general', function (Blueprint $table) {
            $table->string('app_whatsapp')->nullable()->after('app_phone');
            $table->string('wa_group')->nullable()->after('tiktok');
            $table->string('help_url')->nullable()->after('wa_group');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('general', function (Blueprint $table) {
            $table->dropColumn(['app_whatsapp', 'wa_group', 'help_url']);
        });
    }
}
