<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add indexes to transactions table for faster queries
        Schema::table('transactions', function (Blueprint $table) {
            // Check if indexes don't exist before adding
            if (!$this->indexExists('transactions', 'transactions_company_id_created_at_index')) {
                $table->index(['company_id', 'created_at'], 'transactions_company_id_created_at_index');
            }
            
            if (!$this->indexExists('transactions', 'transactions_status_created_at_index')) {
                $table->index(['status', 'created_at'], 'transactions_status_created_at_index');
            }
            
            if (!$this->indexExists('transactions', 'transactions_reference_index')) {
                $table->index('reference', 'transactions_reference_index');
            }
            
            if (!$this->indexExists('transactions', 'transactions_transaction_type_index')) {
                $table->index('transaction_type', 'transactions_transaction_type_index');
            }
        });

        // Add indexes to virtual_accounts table
        Schema::table('virtual_accounts', function (Blueprint $table) {
            if (!$this->indexExists('virtual_accounts', 'virtual_accounts_company_id_status_index')) {
                $table->index(['company_id', 'status'], 'virtual_accounts_company_id_status_index');
            }
            
            if (!$this->indexExists('virtual_accounts', 'virtual_accounts_account_number_index')) {
                $table->index('account_number', 'virtual_accounts_account_number_index');
            }
            
            if (!$this->indexExists('virtual_accounts', 'virtual_accounts_customer_email_index')) {
                $table->index('customer_email', 'virtual_accounts_customer_email_index');
            }
        });

        // Add indexes to company_wallets table
        Schema::table('company_wallets', function (Blueprint $table) {
            if (!$this->indexExists('company_wallets', 'company_wallets_company_id_index')) {
                $table->index('company_id', 'company_wallets_company_id_index');
            }
        });

        // Add indexes to company_webhook_logs table
        Schema::table('company_webhook_logs', function (Blueprint $table) {
            if (!$this->indexExists('company_webhook_logs', 'company_webhook_logs_company_id_created_at_index')) {
                $table->index(['company_id', 'created_at'], 'company_webhook_logs_company_id_created_at_index');
            }
            
            if (!$this->indexExists('company_webhook_logs', 'company_webhook_logs_status_index')) {
                $table->index('status', 'company_webhook_logs_status_index');
            }
        });

        // Add indexes to settlement_queue table
        Schema::table('settlement_queue', function (Blueprint $table) {
            if (!$this->indexExists('settlement_queue', 'settlement_queue_company_id_status_index')) {
                $table->index(['company_id', 'status'], 'settlement_queue_company_id_status_index');
            }
            
            if (!$this->indexExists('settlement_queue', 'settlement_queue_settlement_date_index')) {
                $table->index('settlement_date', 'settlement_queue_settlement_date_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_company_id_created_at_index');
            $table->dropIndex('transactions_status_created_at_index');
            $table->dropIndex('transactions_reference_index');
            $table->dropIndex('transactions_transaction_type_index');
        });

        Schema::table('virtual_accounts', function (Blueprint $table) {
            $table->dropIndex('virtual_accounts_company_id_status_index');
            $table->dropIndex('virtual_accounts_account_number_index');
            $table->dropIndex('virtual_accounts_customer_email_index');
        });

        Schema::table('company_wallets', function (Blueprint $table) {
            $table->dropIndex('company_wallets_company_id_index');
        });

        Schema::table('company_webhook_logs', function (Blueprint $table) {
            $table->dropIndex('company_webhook_logs_company_id_created_at_index');
            $table->dropIndex('company_webhook_logs_status_index');
        });

        Schema::table('settlement_queue', function (Blueprint $table) {
            $table->dropIndex('settlement_queue_company_id_status_index');
            $table->dropIndex('settlement_queue_settlement_date_index');
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists($table, $index)
    {
        $connection = Schema::getConnection();
        $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
        $doctrineTable = $doctrineSchemaManager->listTableDetails($table);
        
        return $doctrineTable->hasIndex($index);
    }
};
