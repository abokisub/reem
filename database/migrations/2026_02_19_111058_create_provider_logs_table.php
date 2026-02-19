<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provider_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // palmpay, paystack, etc.
            $table->string('endpoint');
            $table->string('method')->default('POST');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->integer('status_code')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->string('transaction_reference')->nullable()->index();
            $table->text('error')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_logs');
    }
};
