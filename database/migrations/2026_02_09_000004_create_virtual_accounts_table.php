<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_id')->unique(); // Our internal ID (va_xxx)
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('user_id'); // Company's customer ID (their internal reference)

            // PalmPay Account Details
            $table->string('palmpay_account_number')->unique();
            $table->string('palmpay_account_name');
            $table->string('palmpay_bank_name')->default('PalmPay');
            $table->string('palmpay_customer_id')->nullable();
            $table->string('palmpay_reference')->nullable(); // PalmPay's internal reference

            // Customer Information
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('bvn')->nullable();

            // Status
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('activated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('company_id');
            $table->index('palmpay_account_number');
            $table->index(['company_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_accounts');
    }
};