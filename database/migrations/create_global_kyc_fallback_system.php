<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Create Global KYC Fallback System
     * This creates a shared pool of KYC numbers that ALL companies can use as fallback
     */
    public function up()
    {
        // Global KYC Pool Table
        Schema::create('global_kyc_pool', function (Blueprint $table) {
            $table->id();
            $table->enum('kyc_type', ['bvn', 'nin'])->comment('Type of KYC: BVN or NIN');
            $table->string('kyc_number', 20)->unique()->comment('The actual BVN/NIN number');
            $table->boolean('is_active')->default(true)->comment('Whether this KYC is active and usable');
            $table->integer('usage_count')->default(0)->comment('Total times this KYC has been used');
            $table->integer('success_count')->default(0)->comment('Successful account creations');
            $table->integer('failure_count')->default(0)->comment('Failed account creation attempts');
            $table->timestamp('last_used_at')->nullable()->comment('When this KYC was last used');
            $table->timestamp('last_success_at')->nullable()->comment('When this KYC last succeeded');
            $table->timestamp('blacklisted_until')->nullable()->comment('Blacklisted until this time (auto-recovery)');
            $table->text('notes')->nullable()->comment('Admin notes about this KYC');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('kyc_type');
            $table->index('is_active');
            $table->index('usage_count');
            $table->index('blacklisted_until');
            $table->index(['kyc_type', 'is_active']);
        });
        
        // Global KYC Usage Log Table
        Schema::create('global_kyc_usage_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_kyc_id')->constrained('global_kyc_pool')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('virtual_account_id')->nullable()->constrained('virtual_accounts')->onDelete('set null');
            $table->string('kyc_number', 20)->comment('KYC number used (for quick reference)');
            $table->enum('kyc_type', ['bvn', 'nin'])->comment('Type of KYC used');
            $table->boolean('success')->comment('Whether the account creation succeeded');
            $table->text('error_message')->nullable()->comment('Error message if failed');
            $table->json('request_data')->nullable()->comment('Request data for debugging');
            $table->timestamps();
            
            // Indexes for analytics and performance
            $table->index('company_id');
            $table->index('success');
            $table->index('created_at');
            $table->index(['company_id', 'success']);
            $table->index(['global_kyc_id', 'success']);
        });
    }

    /**
     * Reverse the migrations
     */
    public function down()
    {
        Schema::dropIfExists('global_kyc_usage_log');
        Schema::dropIfExists('global_kyc_pool');
    }
};