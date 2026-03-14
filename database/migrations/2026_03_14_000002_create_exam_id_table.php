<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exam_id', function (Blueprint $table) {
            $table->id();
            $table->string('exam_name');
            $table->string('plan_id');
            $table->timestamps();
        });

        // Insert default exam data
        DB::table('exam_id')->insert([
            [
                'exam_name' => 'WAEC',
                'plan_id' => 'waec',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'exam_name' => 'NECO',
                'plan_id' => 'neco',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'exam_name' => 'NABTEB',
                'plan_id' => 'nabteb',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exam_id');
    }
};