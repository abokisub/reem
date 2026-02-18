<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // Nigerian bank code (e.g., 044 for Access Bank)
            $table->string('palmpay_code')->nullable(); // PalmPay's bank code (if different)
            $table->boolean('active')->default(true);
            $table->boolean('supports_transfers')->default(true);
            $table->boolean('supports_account_verification')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};