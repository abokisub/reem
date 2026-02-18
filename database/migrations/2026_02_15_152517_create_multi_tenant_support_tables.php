<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Company Users (End Users)
        Schema::create('company_users', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique(); // PWV_CUS_xxx
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('external_customer_id')->nullable(); // Merchant's ID
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('kyc_status', ['unverified', 'pending', 'verified', 'rejected', 'partial'])->default('unverified');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamps();

            $table->index(['company_id', 'external_customer_id']);
            $table->index('email');
            $table->index('phone');
        });

        // 2. Company Fee Settings
        Schema::create('company_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->enum('fee_model', ['flat', 'percentage', 'hybrid'])->default('percentage');
            $table->decimal('flat_fee', 10, 2)->default(0); // For 'flat' or hybrid 'capped' logic
            $table->decimal('percentage_fee', 5, 2)->default(0); // e.g., 1.5
            $table->decimal('cap_amount', 10, 2)->nullable(); // e.g., 2000 for hybrid
            $table->timestamps();

            $table->unique('company_id');
        });

        // 3. Add UUID and settlement fields to companies table
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'uuid')) {
                $table->string('uuid')->unique()->nullable()->after('id');
            }
            if (!Schema::hasColumn('companies', 'api_public_key')) {
                $table->string('api_public_key')->unique()->nullable()->after('api_key');
            }
            if (!Schema::hasColumn('companies', 'api_secret_key')) {
                $table->string('api_secret_key')->unique()->nullable()->after('api_public_key');
            }
            if (!Schema::hasColumn('companies', 'settlement_bank_name')) {
                $table->string('settlement_bank_name')->nullable();
                $table->string('settlement_account_number')->nullable();
                $table->string('settlement_account_name')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'api_public_key', 'api_secret_key', 'settlement_bank_name', 'settlement_account_number', 'settlement_account_name']);
        });
        Schema::dropIfExists('company_fee_settings');
        Schema::dropIfExists('company_users');
    }
};
