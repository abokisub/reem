<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();

            // API Keys (Gateway customers)
            $table->string('public_key')->unique(); // pk_live_xxx
            $table->string('secret_key')->unique(); // sk_live_xxx
            $table->string('api_key')->unique(); // ak_live_xxx

            // Webhook Configuration
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->boolean('webhook_enabled')->default(true);

            // Business Settings
            $table->enum('status', ['active', 'suspended', 'pending'])->default('pending');
            $table->decimal('transaction_fee_percentage', 5, 2)->default(0);
            $table->decimal('minimum_balance', 15, 2)->default(0);

            // Limits
            $table->decimal('daily_limit', 15, 2)->nullable();
            $table->decimal('monthly_limit', 15, 2)->nullable();
            $table->decimal('single_transaction_limit', 15, 2)->nullable();

            // KYC
            $table->enum('kyc_status', ['unverified', 'pending', 'verified', 'rejected'])->default('unverified');
            $table->string('business_registration_number')->nullable();
            $table->json('kyc_documents')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('email');
            $table->index(['public_key', 'secret_key', 'api_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};