<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingBusinessFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rc_number')->nullable();
            $table->text('description')->nullable();
            $table->string('country')->nullable();
            $table->string('lga')->nullable();
            $table->string('website')->nullable();
            $table->string('facebook')->nullable();
            $table->string('x')->nullable();
            $table->string('instagram')->nullable();
            $table->string('linkedin')->nullable();
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
                'rc_number',
                'description',
                'country',
                'lga',
                'website',
                'facebook',
                'x',
                'instagram',
                'linkedin'
            ]);
        });
    }
}
