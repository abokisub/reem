<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('other_api', function (Blueprint $table) {
            // Add KoboPoint credentials columns at the end
            $table->string('kobopoint_username')->nullable();
            $table->string('kobopoint_password')->nullable();
            $table->string('kobopoint_url')->default('https://kobopoint.com/api');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('other_api', function (Blueprint $table) {
            $table->dropColumn(['kobopoint_username', 'kobopoint_password', 'kobopoint_url']);
        });
    }
};