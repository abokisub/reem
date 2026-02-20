<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Phase 2: Backfill Historical Data
     * 
     * This migration populates the new normalized columns with data for existing transactions.
     * It should be run during a low-traffic window to minimize performance impact.
     * 
     * Operations:
     * 1. Generate session_id for all transactions
     * 2. Generate transaction_ref for all transactions
     * 3. Map old transaction types to new transaction_type enum
     * 4. Calculate net_amount = amount - fee
     * 5. Set settlement_status based on status and transaction_type
     * 6. Normalize status values to 5-state model
     */
    public function up(): void
    {
        DB::transaction(function () {
            Log::info('Phase 2 Migration: Starting transaction data backfill');
            
            // Step 1: Generate session_id for existing transactions
            Log::info('Phase 2: Generating session_id values');
            DB::statement("
                UPDATE transactions 
                SET session_id = CONCAT('sess_', UUID())
                WHERE session_id IS NULL
            ");
            
            // Step 2: Generate transaction_ref for existing transactions
            // Format: TXN + 12 character hash based on id and created_at
            Log::info('Phase 2: Generating transaction_ref values');
            DB::statement("
                UPDATE transactions 
                SET transaction_ref = CONCAT('TXN', UPPER(SUBSTRING(MD5(CONCAT(id, created_at)), 1, 12)))
                WHERE transaction_ref IS NULL
            ");
            
            // Step 3: Map old transaction types to new transaction_type enum
            Log::info('Phase 2: Mapping transaction types');
            
            // Virtual account deposits (credit + virtual_account_credit category)
            DB::statement("
                UPDATE transactions 
                SET transaction_type = 'va_deposit'
                WHERE transaction_type IS NULL
                AND type = 'credit'
                AND category = 'virtual_account_credit'
            ");
            
            // Company withdrawals (debit + transfer_out to company's own account)
            DB::statement("
                UPDATE transactions 
                SET transaction_type = 'company_withdrawal'
                WHERE transaction_type IS NULL
                AND type = 'debit'
                AND category = 'transfer_out'
                AND (description LIKE '%withdrawal%' OR description LIKE '%settlement%')
            ");
            
            // API transfers (debit + transfer_out or vtu_purchase)
            DB::statement("
                UPDATE transactions 
                SET transaction_type = 'api_transfer'
                WHERE transaction_type IS NULL
                AND type = 'debit'
                AND category IN ('transfer_out', 'vtu_purchase')
            ");
            
            // Fee charges
            DB::statement("
                UPDATE transactions 
                SET transaction_type = 'fee_charge'
                WHERE transaction_type IS NULL
                AND type = 'fee'
                AND category = 'fee'
            ");
            
            // Refunds
            DB::statement("
                UPDATE transactions 
                SET transaction_type = 'refund'
                WHERE transaction_type IS NULL
                AND (type = 'refund' OR category = 'refund')
            ");
            
            // Manual adjustments (reversals and other adjustments)
            DB::statement("
                UPDATE transactions 
                SET transaction_type = 'manual_adjustment'
                WHERE transaction_type IS NULL
                AND (type = 'reversal' OR category = 'other')
            ");
            
            // Default any remaining to manual_adjustment
            DB::statement("
                UPDATE transactions 
                SET transaction_type = 'manual_adjustment'
                WHERE transaction_type IS NULL
            ");
            
            // Step 4: Calculate net_amount = amount - fee
            Log::info('Phase 2: Calculating net_amount');
            DB::statement("
                UPDATE transactions 
                SET net_amount = amount - fee
                WHERE net_amount IS NULL
            ");
            
            // Step 5: Set settlement_status based on status and transaction_type
            Log::info('Phase 2: Setting settlement_status');
            
            // Internal accounting entries don't require settlement
            DB::statement("
                UPDATE transactions 
                SET settlement_status = 'not_applicable'
                WHERE settlement_status IS NULL
                AND transaction_type IN ('fee_charge', 'kyc_charge', 'manual_adjustment')
            ");
            
            // Successful transactions are settled
            DB::statement("
                UPDATE transactions 
                SET settlement_status = 'settled'
                WHERE settlement_status IS NULL
                AND status IN ('success', 'successful')
            ");
            
            // Failed/reversed transactions don't settle
            DB::statement("
                UPDATE transactions 
                SET settlement_status = 'not_applicable'
                WHERE settlement_status IS NULL
                AND status IN ('failed', 'reversed')
            ");
            
            // Default to unsettled for pending/processing
            DB::statement("
                UPDATE transactions 
                SET settlement_status = 'unsettled'
                WHERE settlement_status IS NULL
            ");
            
            // Step 6: Normalize status values to 5-state model
            Log::info('Phase 2: Normalizing status values');
            
            // Map 'success' to 'successful'
            DB::statement("
                UPDATE transactions 
                SET status = 'successful'
                WHERE status = 'success'
            ");
            
            // Map any invalid status to 'failed'
            DB::statement("
                UPDATE transactions 
                SET status = 'failed'
                WHERE status NOT IN ('pending', 'processing', 'successful', 'failed', 'reversed')
            ");
            
            // Log completion statistics
            $totalTransactions = DB::table('transactions')->count();
            $withSessionId = DB::table('transactions')->whereNotNull('session_id')->count();
            $withTransactionRef = DB::table('transactions')->whereNotNull('transaction_ref')->count();
            $withTransactionType = DB::table('transactions')->whereNotNull('transaction_type')->count();
            $withSettlementStatus = DB::table('transactions')->whereNotNull('settlement_status')->count();
            $withNetAmount = DB::table('transactions')->whereNotNull('net_amount')->count();
            
            Log::info('Phase 2 Migration: Backfill completed', [
                'total_transactions' => $totalTransactions,
                'with_session_id' => $withSessionId,
                'with_transaction_ref' => $withTransactionRef,
                'with_transaction_type' => $withTransactionType,
                'with_settlement_status' => $withSettlementStatus,
                'with_net_amount' => $withNetAmount,
            ]);
        });
    }

    /**
     * Reverse the migration
     * 
     * This clears the backfilled data, returning columns to NULL state
     */
    public function down(): void
    {
        DB::transaction(function () {
            Log::info('Phase 2 Migration: Rolling back transaction data backfill');
            
            // Clear all backfilled data
            DB::statement("
                UPDATE transactions 
                SET 
                    session_id = NULL,
                    transaction_ref = NULL,
                    transaction_type = NULL,
                    settlement_status = NULL,
                    net_amount = NULL
                WHERE session_id LIKE 'sess_%'
            ");
            
            // Revert status normalization (success -> successful)
            DB::statement("
                UPDATE transactions 
                SET status = 'success'
                WHERE status = 'successful'
            ");
            
            Log::info('Phase 2 Migration: Rollback completed');
        });
    }
};
