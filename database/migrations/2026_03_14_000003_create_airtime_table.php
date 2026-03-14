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
        Schema::create('airtime', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50);
            $table->string('transid', 100)->unique();
            $table->string('network', 50);
            $table->string('network_type', 20)->default('VTU');
            $table->string('plan_phone', 15);
            $table->decimal('amount', 15, 2);
            $table->decimal('oldbal', 15, 2)->default(0.00);
            $table->decimal('newbal', 15, 2)->default(0.00);
            $table->decimal('discount', 15, 2)->default(0.00);
            $table->integer('plan_status')->default(0); // 0=pending, 1=success, 2=failed
            $table->string('plan_date')->nullable();
            $table->string('system', 20)->default('WEB');
            $table->string('client_reference', 100)->nullable();
            $table->string('api_reference', 100)->nullable();
            $table->timestamps();

            // Indexes
            $table->index('username');
            $table->index('transid');
            $table->index('plan_status');
            $table->index(['username', 'plan_status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('airtime');
    }
};