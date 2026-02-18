<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            // Track KYC source: 'director_bvn', 'customer_bvn', 'customer_nin'
            $table->string('kyc_source')->default('director_bvn')->after('identity_type');
            
            // Track if customer upgraded their KYC
            $table->boolean('kyc_upgraded')->default(false)->after('kyc_source');
            $table->timestamp('kyc_upgraded_at')->nullable()->after('kyc_upgraded');
            
            // Store original director BVN used (for reference)
            $table->string('director_bvn', 11)->nullable()->after('kyc_upgraded_at');
        });
        
        Schema::table('companies', function (Blueprint $table) {
            // Add director BVN to companies table
            $table->string('director_bvn', 11)->nullable()->after('bvn');
            $table->string('director_nin', 11)->nullable()->after('director_bvn');
        });
    }

    public function down(): void
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            $table->dropColumn(['kyc_source', 'kyc_upgraded', 'kyc_upgraded_at', 'director_bvn']);
        });
        
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['director_bvn', 'director_nin']);
        });
    }
};
