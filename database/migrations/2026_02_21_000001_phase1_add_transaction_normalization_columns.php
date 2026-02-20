<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Phase 1: Add new columns as nullable (Non-Breaking Migration)
     * 
     * This migration adds new normalized columns to the transactions table
     * without enforcing constraints. This allows the application to continue
     * operating normally while we prepare for data backfill.
     * 
     * New columns:
     * - session_id: Links related transaction events
     * - transaction_ref: Unique reference for external communication
     * - transaction_type: 7-type classification system
     * - settlement_status: Track settlement state
     * - net_amount: Calculated amount after fees
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Add session_id for tracking related transactions
            if (!Schema::hasColumn('transactions', 'session_id')) {
                $table->string('session_id', 255)->nullable()->after('transaction_id');
            }
            
            // Add transaction_ref for unique transaction reference
            if (!Schema::hasColumn('transactions', 'transaction_ref')) {
                $table->string('transaction_ref', 255)->nullable()->after('session_id');
            }
            
            // Add transaction_type enum with 7 types
            if (!Schema::hasColumn('transactions', 'transaction_type')) {
                $table->enum('transaction_type', [
                    'va_deposit',
                    'company_withdrawal',
                    'api_transfer',
                    'kyc_charge',
                    'refund',
                    'fee_charge',
                    'manual_adjustment'
                ])->nullable()->after('category');
            }
            
            // Add settlement_status enum with 3 states
            if (!Schema::hasColumn('transactions', 'settlement_status')) {
                $table->enum('settlement_status', [
                    'settled',
                    'unsettled',
                    'not_applicable'
                ])->nullable()->after('status');
            }
            
            // Add net_amount (amount - fee)
            if (!Schema::hasColumn('transactions', 'net_amount')) {
                $table->decimal('net_amount', 15, 2)->nullable()->after('fee');
            }
        });
        
        // Add indexes for performance (check if they don't exist first)
        if (!$this->indexExists('transactions', 'transactions_session_id_index')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('session_id', 'transactions_session_id_index');
            });
        }
        
        if (!$this->indexExists('transactions', 'transactions_transaction_ref_index')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('transaction_ref', 'transactions_transaction_ref_index');
            });
        }
        
        if (!$this->indexExists('transactions', 'transactions_type_status_index')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['transaction_type', 'status'], 'transactions_type_status_index');
            });
        }
        
        // Verify provider_reference exists (should already exist from previous migration)
        if (!Schema::hasColumn('transactions', 'provider_reference')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('provider_reference', 255)->nullable()->after('palmpay_reference');
            });
        }
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop indexes first
            if ($this->indexExists('transactions', 'transactions_type_status_index')) {
                $table->dropIndex('transactions_type_status_index');
            }
            
            if ($this->indexExists('transactions', 'transactions_transaction_ref_index')) {
                $table->dropIndex('transactions_transaction_ref_index');
            }
            
            if ($this->indexExists('transactions', 'transactions_session_id_index')) {
                $table->dropIndex('transactions_session_id_index');
            }
            
            // Drop columns
            if (Schema::hasColumn('transactions', 'net_amount')) {
                $table->dropColumn('net_amount');
            }
            
            if (Schema::hasColumn('transactions', 'settlement_status')) {
                $table->dropColumn('settlement_status');
            }
            
            if (Schema::hasColumn('transactions', 'transaction_type')) {
                $table->dropColumn('transaction_type');
            }
            
            if (Schema::hasColumn('transactions', 'transaction_ref')) {
                $table->dropColumn('transaction_ref');
            }
            
            if (Schema::hasColumn('transactions', 'session_id')) {
                $table->dropColumn('session_id');
            }
        });
    }
    
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$databaseName, $table, $index]
        );
        
        return $result[0]->count > 0;
    }
};
