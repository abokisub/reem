<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotifTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notif', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->string('type')->default('info'); // info, success, warning, error
            $table->tinyInteger('adex')->default(0); // read status
            $table->tinyInteger('habukhan')->default(0); // custom flag
            $table->timestamps();

            $table->index('username');
            $table->index(['username', 'adex']);
            $table->index(['username', 'habukhan']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notif');
    }
}
