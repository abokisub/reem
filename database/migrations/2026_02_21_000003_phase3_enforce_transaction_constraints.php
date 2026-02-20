<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Phase 3: Enforce NOT NULL Constraints (Breaking Migration)
     * 
     * This migration enforces data integrity constraints on the normalized columns.
     * It requires a maintenance window as it will reject any transaction creation
     * attempts with missing required fields.
     * 
     * IMPORTANT: This migration should only be run after:
     * 1. Phase 1 migration has been deployed
     * 2. Phase 2 migration has successfully backfilled all data
     * 3. Application code has been updated to populate new fields
     * 4. Verification that all transactions have non-null values in required fields
     * 
     * Operations:
     * 1. Make required fields NOT NULL
     * 2. Add UNIQUE constraint on transaction_ref
     * 3. Add CHECK constraint for amount > 0
     */
    public function up(): void
    {
        // Pre-flight check: Verify all transactions have required fields populated
        $this->verifyDataIntegrity();
        
        // Note: ALTER TABLE statements in MySQL are implicitly committed
        // So we don't wrap them in a transaction
        Log::info('Phase 3 Migration: Enforcing transaction constraints');
        
        // Step 1: Make session_id NOT NULL
        Log::info('Phase 3: Making session_id NOT NULL');
        DB::statement("
            ALTER TABLE transactions 
            MODIFY COLUMN session_id VARCHAR(255) NOT NULL
        ");
        
        // Step 2: Make transaction_ref NOT NULL and add UNIQUE constraint
        Log::info('Phase 3: Making transaction_ref NOT NULL and UNIQUE');
        DB::statement("
            ALTER TABLE transactions 
            MODIFY COLUMN transaction_ref VARCHAR(255) NOT NULL
        ");
        
        // Add unique constraint on transaction_ref if it doesn't exist
        if (!$this->constraintExists('transactions', 'transactions_transaction_ref_unique')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unique('transaction_ref', 'transactions_transaction_ref_unique');
            });
        }
        
        // Step 3: Make transaction_type NOT NULL
        Log::info('Phase 3: Making transaction_type NOT NULL');
        DB::statement("
            ALTER TABLE transactions 
            MODIFY COLUMN transaction_type ENUM(
                'va_deposit',
                'company_withdrawal',
                'api_transfer',
                'kyc_charge',
                'refund',
                'fee_charge',
                'manual_adjustment'
            ) NOT NULL
        ");
        
        // Step 4: Make settlement_status NOT NULL with default
        Log::info('Phase 3: Making settlement_status NOT NULL');
        DB::statement("
            ALTER TABLE transactions 
            MODIFY COLUMN settlement_status ENUM(
                'settled',
                'unsettled',
                'not_applicable'
            ) NOT NULL DEFAULT 'unsettled'
        ");
        
        // Step 5: Make net_amount NOT NULL
        Log::info('Phase 3: Making net_amount NOT NULL');
        DB::statement("
            ALTER TABLE transactions 
            MODIFY COLUMN net_amount DECIMAL(15, 2) NOT NULL
        ");
        
        // Step 6: Add CHECK constraint for amount > 0
        Log::info('Phase 3: Adding CHECK constraint for amount > 0');
        if (!$this->constraintExists('transactions', 'chk_amount_positive')) {
            DB::statement("
                ALTER TABLE transactions 
                ADD CONSTRAINT chk_amount_positive CHECK (amount > 0)
            ");
        }
        
        Log::info('Phase 3 Migration: Constraints enforced successfully');
    }

    /**
     * Reverse the migration
     * 
     * This removes constraints and makes columns nullable again
     */
    public function down(): void
    {
        Log::info('Phase 3 Migration: Rolling back transaction constraints');
        
        // Remove CHECK constraint
        if ($this->constraintExists('transactions', 'chk_amount_positive')) {
            DB::statement("
                ALTER TABLE transactions 
                DROP CONSTRAINT chk_amount_positive
            ");
        }
        
        // Remove UNIQUE constraint on transaction_ref
        if ($this->constraintExists('transactions', 'transactions_transaction_ref_unique')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropUnique('transactions_transaction_ref_unique');
            });
        }
        
        // Make columns nullable again
        DB::statement("
            ALTER TABLE transactions 
            MODIFY COLUMN session_id VARCHAR(255) NULL
        ");
        
        DB::statement("
            ALTER TABLE transactions 
            MODIFY COLUMN transaction_ref VARCHAR(255) NULL
        ");
        
        DB::statement("
            ALTER TABLE transactions 
            MODIFY COLUMN transaction_type ENUM(
                'va_deposit',
                'company_withdrawal',
                'api_transfer',
                'kyc_charge',
                'refund',
                'fee_charge',
                'manual_adjustment'
            ) NULL
        ");
        
        DB::statement("
            ALTER TABLE transactions 
            MODIFY COLUMN settlement_status ENUM(
                'settled',
                'unsettled',
                'not_applicable'
            ) NULL
        ");
        
        DB::statement("
            ALTER TABLE transactions 
            MODIFY COLUMN net_amount DECIMAL(15, 2) NULL
        ");
        
        Log::info('Phase 3 Migration: Rollback completed');
    }
    
    /**
     * Verify data integrity before enforcing constraints
     * 
     * @throws \Exception if data integrity check fails
     */
    private function verifyDataIntegrity(): void
    {
        Log::info('Phase 3: Verifying data integrity before enforcing constraints');
        
        $nullSessionId = DB::table('transactions')->whereNull('session_id')->count();
        $nullTransactionRef = DB::table('transactions')->whereNull('transaction_ref')->count();
        $nullTransactionType = DB::table('transactions')->whereNull('transaction_type')->count();
        $nullSettlementStatus = DB::table('transactions')->whereNull('settlement_status')->count();
        $nullNetAmount = DB::table('transactions')->whereNull('net_amount')->count();
        $invalidAmount = DB::table('transactions')->where('amount', '<=', 0)->count();
        
        $errors = [];
        
        if ($nullSessionId > 0) {
            $errors[] = "{$nullSessionId} transactions have NULL session_id";
        }
        
        if ($nullTransactionRef > 0) {
            $errors[] = "{$nullTransactionRef} transactions have NULL transaction_ref";
        }
        
        if ($nullTransactionType > 0) {
            $errors[] = "{$nullTransactionType} transactions have NULL transaction_type";
        }
        
        if ($nullSettlementStatus > 0) {
            $errors[] = "{$nullSettlementStatus} transactions have NULL settlement_status";
        }
        
        if ($nullNetAmount > 0) {
            $errors[] = "{$nullNetAmount} transactions have NULL net_amount";
        }
        
        if ($invalidAmount > 0) {
            $errors[] = "{$invalidAmount} transactions have amount <= 0";
        }
        
        if (!empty($errors)) {
            $errorMessage = "Data integrity check failed:\n" . implode("\n", $errors);
            Log::error('Phase 3 Migration: Data integrity check failed', ['errors' => $errors]);
            throw new \Exception($errorMessage . "\n\nPlease run Phase 2 migration first to backfill data.");
        }
        
        // Check for duplicate transaction_ref values
        $duplicates = DB::select("
            SELECT transaction_ref, COUNT(*) as count 
            FROM transactions 
            WHERE transaction_ref IS NOT NULL
            GROUP BY transaction_ref 
            HAVING count > 1
        ");
        
        if (!empty($duplicates)) {
            $duplicateRefs = array_map(fn($d) => $d->transaction_ref, $duplicates);
            Log::error('Phase 3 Migration: Duplicate transaction_ref values found', [
                'duplicates' => $duplicateRefs
            ]);
            throw new \Exception(
                "Found duplicate transaction_ref values: " . implode(', ', $duplicateRefs) . 
                "\n\nPlease resolve duplicates before enforcing UNIQUE constraint."
            );
        }
        
        Log::info('Phase 3: Data integrity verification passed');
    }
    
    /**
     * Check if a constraint exists on a table
     */
    private function constraintExists(string $table, string $constraint): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.table_constraints 
            WHERE table_schema = ? 
            AND table_name = ? 
            AND constraint_name = ?
        ", [$databaseName, $table, $constraint]);
        
        return $result[0]->count > 0;
    }
};
