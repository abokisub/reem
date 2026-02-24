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
            if (!Schema::hasColumn('virtual_accounts', 'is_master')) {
                $table->boolean('is_master')->default(false)->after('status');
            }
            
            // Add provider to track which provider created the account (if not exists)
            // pointwave = our PalmPay master account
            // xixapay, monnify, paystack = third-party providers
            if (!Schema::hasColumn('virtual_accounts', 'provider')) {
                $table->string('provider')->default('pointwave')->after('is_master');
            }
        });
        
        // Add indexes separately to avoid conflicts
        Schema::table('virtual_accounts', function (Blueprint $table) {
            if (!$this->indexExists('virtual_accounts', 'virtual_accounts_company_id_is_master_index')) {
                $table->index(['company_id', 'is_master']);
            }
            if (!$this->indexExists('virtual_accounts', 'virtual_accounts_provider_index')) {
                $table->index('provider');
            }
        });
    }
    
    /**
     * Check if an index exists on a table.
     */
    private function indexExists($table, $index)
    {
        $connection = Schema::getConnection();
        $indexes = $connection->getDoctrineSchemaManager()
            ->listTableIndexes($table);
        return array_key_exists($index, $indexes);
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
