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
        // Add is_master column if it doesn't exist
        if (!Schema::hasColumn('virtual_accounts', 'is_master')) {
            Schema::table('virtual_accounts', function (Blueprint $table) {
                $table->boolean('is_master')->default(false)->after('status');
            });
        }
        
        // Add provider column if it doesn't exist
        if (!Schema::hasColumn('virtual_accounts', 'provider')) {
            Schema::table('virtual_accounts', function (Blueprint $table) {
                $table->string('provider')->default('pointwave')->after('is_master');
            });
        }
        
        // Add indexes using raw SQL to avoid conflicts
        try {
            DB::statement('CREATE INDEX virtual_accounts_company_id_is_master_index ON virtual_accounts(company_id, is_master)');
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }
        
        try {
            DB::statement('CREATE INDEX virtual_accounts_provider_index ON virtual_accounts(provider)');
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        try {
            DB::statement('DROP INDEX virtual_accounts_company_id_is_master_index ON virtual_accounts');
        } catch (\Exception $e) {
            // Ignore if doesn't exist
        }
        
        try {
            DB::statement('DROP INDEX virtual_accounts_provider_index ON virtual_accounts');
        } catch (\Exception $e) {
            // Ignore if doesn't exist
        }
        
        // Drop columns
        Schema::table('virtual_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('virtual_accounts', 'is_master')) {
                $table->dropColumn('is_master');
            }
            if (Schema::hasColumn('virtual_accounts', 'provider')) {
                $table->dropColumn('provider');
            }
        });
    }
};
