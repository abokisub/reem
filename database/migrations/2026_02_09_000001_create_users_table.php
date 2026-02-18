<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->string('name');

            // Balance and wallet
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('referral_balance', 15, 2)->default(0);

            // Account type and status
            $table->enum('type', ['user', 'admin', 'reseller'])->default('user');
            $table->enum('status', ['active', 'suspended', 'pending'])->default('active');

            // KYC Information
            $table->enum('kyc_tier', ['tier1', 'tier2', 'tier3'])->default('tier1');
            $table->enum('kyc_status', ['unverified', 'pending', 'verified', 'rejected'])->default('unverified');
            $table->string('bvn')->nullable();
            $table->string('nin')->nullable();
            $table->date('dob')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->json('kyc_documents')->nullable();
            $table->timestamp('kyc_submitted_at')->nullable();

            // PalmPay Virtual Account
            $table->string('palmpay_account_number')->nullable();
            $table->string('palmpay_account_name')->nullable();
            $table->string('palmpay_bank_name')->default('PalmPay');
            $table->string('palmpay_customer_id')->nullable();

            // API Keys
            $table->string('api_key')->unique()->nullable();
            $table->string('api_secret')->nullable();

            // Referral
            $table->string('referral_code')->unique()->nullable();
            $table->string('referred_by')->nullable();

            // Settings
            $table->string('theme')->default('light');
            $table->boolean('email_verified')->default(false);
            $table->boolean('phone_verified')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('email');
            $table->index('phone');
            $table->index('palmpay_account_number');
            $table->index('referral_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};