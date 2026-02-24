<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            // Add is_master flag to identify company master accounts
            $table->boolean('is_master')->default(false)->after('status');
            
            // Add provider to track which provider created the account
            // pointwave = our PalmPay master account
            // xixapay, monnify, paystack = third-party providers
            $table->string('provider')->default('pointwave')->after('is_master');
            
            // Add indexes for performance
            $table->index(['company_id', 'is_master']);
            $table->index('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'is_master']);
            $table->dropIndex(['provider']);
            $table->dropColumn(['is_master', 'provider']);
        });
    }
};
