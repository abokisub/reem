# Backend Status Reconciliation & Enforcement Plan

## Executive Summary

This document outlines the implementation plan for achieving complete backend consistency and status enforcement before any frontend work proceeds.

---

## PRIORITY 1: Status Reconciliation Service (MANDATORY)

### Service: TransactionReconciliationService

**Purpose:** Automatically reconcile transaction status with provider state to eliminate pending/success mismatches.

#### Implementation Plan

**File:** `app/Services/TransactionReconciliationService.php`

```php
<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionStatusLog;
use App\Services\PalmPay\TransferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionReconciliationService
{
    private TransferService $transferService;
    
    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }
    
    /**
     * Reconcile all pending/processing transactions with provider
     */
    public function reconcileAll(): array
    {
        $results = [
            'checked' => 0,
            'confirmed_success' => 0,
            'confirmed_failure' => 0,
            'timeout' => 0,
            'errors' => []
        ];
        
        // Find transactions needing reconciliation
        $transactions = Transaction::where(function($query) {
            $query->where('status', 'processing')
                  ->orWhere('status', 'pending');
        })
        ->where('reconciliation_status', 'pending')
        ->where('reconciliation_attempt_count', '<', 10) // Max 10 attempts
        ->orderBy('created_at', 'asc')
        ->limit(100) // Process in batches
        ->get();
        
        foreach ($transactions as $transaction) {
            $results['checked']++;
            
            try {
                $result = $this->reconcileTransaction($transaction);
                
                if ($result['status'] === 'success') {
                    $results['confirmed_success']++;
                } elseif ($result['status'] === 'failure') {
                    $results['confirmed_failure']++;
                } elseif ($result['status'] === 'timeout') {
                    $results['timeout']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ];
                Log::error('Reconciliation error', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * Reconcile single transaction with provider
     */
    public function reconcileTransaction(Transaction $transaction): array
    {
        // Increment attempt count
        $transaction->increment('reconciliation_attempt_count');
        $transaction->update(['last_reconciliation_at' => now()]);
        
        // Query provider status endpoint
        $providerStatus = $this->queryProviderStatus($transaction);
        
        if ($providerStatus['status'] === 'SUCCESS') {
            return $this->handleProviderSuccess($transaction, $providerStatus);
        } elseif ($providerStatus['status'] === 'FAILED') {
            return $this->handleProviderFailure($transaction, $providerStatus);
        } elseif ($providerStatus['status'] === 'TIMEOUT' || $providerStatus['status'] === 'PENDING') {
            return $this->handleProviderTimeout($transaction);
        }
        
        return ['status' => 'unknown'];
    }
    
    /**
     * Query provider status endpoint
     */
    private function queryProviderStatus(Transaction $transaction): array
    {
        try {
            // Use provider_reference to query status
            if (!$transaction->provider_reference) {
                return ['status' => 'UNKNOWN', 'message' => 'No provider reference'];
            }
            
            // Query PalmPay status endpoint
            $response = $this->transferService->queryTransactionStatus(
                $transaction->provider_reference
            );
            
            return [
                'status' => $response['status'] ?? 'UNKNOWN',
                'message' => $response['message'] ?? '',
                'provider_data' => $response
            ];
        } catch (\Exception $e) {
            Log::error('Provider status query failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            
            return ['status' => 'TIMEOUT', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Handle provider confirmed success
     */
    private function handleProviderSuccess(Transaction $transaction, array $providerStatus): array
    {
        DB::beginTransaction();
        
        try {
            $oldStatus = $transaction->status;
            $oldSettlementStatus = $transaction->settlement_status;
            
            // Update transaction status
            $transaction->update([
                'status' => 'successful',
                'settlement_status' => 'settled',
                'reconciliation_status' => 'completed',
                'reconciliation_completed_at' => now()
            ]);
            
            // Log status change
            TransactionStatusLog::create([
                'transaction_id' => $transaction->id,
                'old_status' => $oldStatus,
                'new_status' => 'successful',
                'old_settlement_status' => $oldSettlementStatus,
                'new_settlement_status' => 'settled',
                'changed_by' => 'system_reconciliation',
                'reason' => 'Provider confirmed success',
                'metadata' => json_encode([
                    'provider_status' => $providerStatus['status'],
                    'provider_message' => $providerStatus['message'] ?? '',
                    'reconciliation_attempt' => $transaction->reconciliation_attempt_count
                ])
            ]);
            
            DB::commit();
            
            Log::info('Transaction reconciled to success', [
                'transaction_id' => $transaction->id,
                'transaction_ref' => $transaction->transaction_ref,
                'old_status' => $oldStatus,
                'new_status' => 'successful'
            ]);
            
            return ['status' => 'success', 'transaction' => $transaction];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Handle provider confirmed failure
     */
    private function handleProviderFailure(Transaction $transaction, array $providerStatus): array
    {
        DB::beginTransaction();
        
        try {
            $oldStatus = $transaction->status;
            $oldSettlementStatus = $transaction->settlement_status;
            
            // Trigger safe ledger reversal
            $this->reverseLedgerEntries($transaction);
            
            // Update transaction status
            $transaction->update([
                'status' => 'failed',
                'settlement_status' => 'not_applicable',
                'reconciliation_status' => 'completed',
                'reconciliation_completed_at' => now()
            ]);
            
            // Log status change
            TransactionStatusLog::create([
                'transaction_id' => $transaction->id,
                'old_status' => $oldStatus,
                'new_status' => 'failed',
                'old_settlement_status' => $oldSettlementStatus,
                'new_settlement_status' => 'not_applicable',
                'changed_by' => 'system_reconciliation',
                'reason' => 'Provider confirmed failure',
                'metadata' => json_encode([
                    'provider_status' => $providerStatus['status'],
                    'provider_message' => $providerStatus['message'] ?? '',
                    'reconciliation_attempt' => $transaction->reconciliation_attempt_count,
                    'ledger_reversed' => true
                ])
            ]);
            
            DB::commit();
            
            Log::info('Transaction reconciled to failure', [
                'transaction_id' => $transaction->id,
                'transaction_ref' => $transaction->transaction_ref,
                'old_status' => $oldStatus,
                'new_status' => 'failed'
            ]);
            
            return ['status' => 'failure', 'transaction' => $transaction];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Handle provider timeout/pending
     */
    private function handleProviderTimeout(Transaction $transaction): array
    {
        // Keep status as processing, will retry later
        Log::info('Transaction still pending at provider', [
            'transaction_id' => $transaction->id,
            'transaction_ref' => $transaction->transaction_ref,
            'attempt_count' => $transaction->reconciliation_attempt_count
        ]);
        
        return ['status' => 'timeout', 'transaction' => $transaction];
    }
    
    /**
     * Reverse ledger entries for failed transaction
     */
    private function reverseLedgerEntries(Transaction $transaction): void
    {
        // Find all ledger entries for this transaction
        $ledgerEntries = DB::table('ledger_entries')
            ->where('transaction_id', $transaction->id)
            ->get();
        
        foreach ($ledgerEntries as $entry) {
            // Create reversal entry
            DB::table('ledger_entries')->insert([
                'transaction_id' => $transaction->id,
                'company_id' => $entry->company_id,
                'customer_id' => $entry->customer_id,
                'entry_type' => $entry->entry_type === 'debit' ? 'credit' : 'debit',
                'amount' => $entry->amount,
                'balance_before' => null, // Will be calculated
                'balance_after' => null,  // Will be calculated
                'description' => 'Reversal: ' . $entry->description,
                'reference' => $transaction->transaction_ref . '_reversal',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        Log::info('Ledger entries reversed', [
            'transaction_id' => $transaction->id,
            'entries_reversed' => $ledgerEntries->count()
        ]);
    }
}
```

#### Artisan Command

**File:** `app/Console/Commands/ReconcileTransactionStatus.php`

```php
<?php

namespace App\Console\Commands;

use App\Services\TransactionReconciliationService;
use Illuminate\Console\Command;

class ReconcileTransactionStatus extends Command
{
    protected $signature = 'transactions:reconcile';
    protected $description = 'Reconcile pending/processing transactions with provider status';
    
    public function handle(TransactionReconciliationService $service)
    {
        $this->info('Starting transaction reconciliation...');
        
        $results = $service->reconcileAll();
        
        $this->info("Reconciliation complete:");
        $this->info("  Checked: {$results['checked']}");
        $this->info("  Confirmed Success: {$results['confirmed_success']}");
        $this->info("  Confirmed Failure: {$results['confirmed_failure']}");
        $this->info("  Timeout/Pending: {$results['timeout']}");
        
        if (!empty($results['errors'])) {
            $this->error("  Errors: " . count($results['errors']));
            foreach ($results['errors'] as $error) {
                $this->error("    Transaction {$error['transaction_id']}: {$error['error']}");
            }
        }
        
        return 0;
    }
}
```

#### Cron Job Configuration

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Run reconciliation every 5 minutes
    $schedule->command('transactions:reconcile')
             ->everyFiveMinutes()
             ->withoutOverlapping()
             ->runInBackground();
}
```

---

## PRIORITY 2: Canonical Status Source Enforcement

### Rule: transactions.status is the ONLY canonical status field

#### Implementation Steps

1. **Remove status logic from ledger_entries**
   - ledger_entries should NEVER have a status field
   - ledger_entries are immutable records of balance changes
   - Status is derived from transactions table only

2. **Remove status logic from wallet table**
   - wallet table only stores current balance
   - No status field needed
   - Balance is calculated from ledger_entries

3. **Remove status logic from provider_logs**
   - provider_logs are audit trails only
   - They record what provider said
   - They do NOT determine transaction status
   - Status is determined by transactions table only

4. **Dashboard enforcement**
   - All dashboards query transactions table only
   - No derived status from other tables
   - No status joins from ledger_entries
   - No status from provider_logs

#### Code Changes Required

**File:** `app/Models/Transaction.php`

Add accessor to enforce canonical status:

```php
/**
 * Get the canonical status
 * This is the ONLY source of truth for transaction status
 */
public function getCanonicalStatusAttribute(): string
{
    return $this->status;
}

/**
 * Get the canonical settlement status
 * This is the ONLY source of truth for settlement status
 */
public function getCanonicalSettlementStatusAttribute(): string
{
    return $this->settlement_status;
}
```

---

## PRIORITY 3: Refactor RA Dashboard Query

### Current Problem
- Queries multiple tables
- Reads from ledger_entries
- N+1 query issues
- Inconsistent status display

### Solution: Clean Normalized Query

**File:** `app/Http/Controllers/API/Trans.php`

Update `AllRATransactions` method:

```php
public function AllRATransactions(Request $request)
{
    $company = Auth::user()->company;
    
    if (!$company) {
        return response()->json(['error' => 'Company not found'], 404);
    }
    
    // Query ONLY from transactions table
    $transactions = Transaction::query()
        ->where('company_id', $company->id)
        ->whereIn('transaction_type', [
            'va_deposit',
            'api_transfer',
            'company_withdrawal',
            'refund'
        ])
        // Eager load relationships to prevent N+1
        ->with([
            'company:id,company_name',
            'customer:id,name,email,phone'
        ])
        ->select([
            'id',
            'company_id',
            'customer_id',
            'transaction_ref',
            'session_id',
            'transaction_type',
            'amount',
            'fee',
            'net_amount',
            'status',              // Canonical status
            'settlement_status',   // Canonical settlement status
            'provider_reference',
            'description',
            'created_at'
        ])
        ->orderBy('created_at', 'desc')
        ->paginate(50);
    
    // Transform for frontend
    $transactions->getCollection()->transform(function ($transaction) {
        return [
            'id' => $transaction->id,
            'transaction_ref' => $transaction->transaction_ref,
            'session_id' => $transaction->session_id,
            'transaction_type' => $transaction->transaction_type,
            'transaction_type_label' => $this->getTransactionTypeLabel($transaction->transaction_type),
            'customer_name' => $transaction->customer->name ?? 'N/A',
            'customer_email' => $transaction->customer->email ?? '',
            'customer_phone' => $transaction->customer->phone ?? '',
            'amount' => number_format($transaction->amount, 2),
            'fee' => number_format($transaction->fee, 2),
            'net_amount' => number_format($transaction->net_amount, 2),
            'status' => $transaction->status,  // Canonical
            'status_label' => $this->getStatusLabel($transaction->status),
            'settlement_status' => $transaction->settlement_status,  // Canonical
            'settlement_label' => $this->getSettlementLabel($transaction->settlement_status),
            'description' => $transaction->description ?? '',
            'date' => $transaction->created_at->format('Y-m-d H:i:s'),
            'created_at' => $transaction->created_at->toISOString()
        ];
    });
    
    return response()->json($transactions);
}

private function getTransactionTypeLabel(string $type): string
{
    return match($type) {
        'va_deposit' => 'VA Deposit',
        'api_transfer' => 'Transfer',
        'company_withdrawal' => 'Withdrawal',
        'refund' => 'Refund',
        default => ucfirst(str_replace('_', ' ', $type))
    };
}

private function getStatusLabel(string $status): string
{
    return match($status) {
        'successful' => 'Successful',
        'pending' => 'Pending',
        'processing' => 'Processing',
        'failed' => 'Failed',
        default => ucfirst($status)
    };
}

private function getSettlementLabel(string $status): string
{
    return match($status) {
        'settled' => 'Settled',
        'unsettled' => 'Unsettled',
        'not_applicable' => 'Not Applicable',
        'failed' => 'Failed',
        default => ucfirst(str_replace('_', ' ', $status))
    };
}
```

**Key Changes:**
- Query ONLY from transactions table
- Filter by transaction_type (4 customer-facing types only)
- Eager load company + customer (prevent N+1)
- Never read from ledger_entries
- Use canonical status fields only
- No derived status
- No N/A values in response

---

## PRIORITY 4: Refactor Admin Dashboard Query

### Solution: Clean Admin Query

**File:** `app/Http/Controllers/Admin/AdminTransactionController.php`

```php
public function index(Request $request)
{
    // Query ONLY from transactions table
    $transactions = Transaction::query()
        ->whereIn('transaction_type', [
            'va_deposit',
            'api_transfer',
            'company_withdrawal',
            'refund',
            'fee_charge',
            'kyc_charge',
            'manual_adjustment'
        ])
        // Eager load relationships
        ->with([
            'company:id,company_name',
            'customer:id,name,email,phone'
        ])
        ->select([
            'id',
            'company_id',
            'customer_id',
            'transaction_ref',
            'session_id',
            'transaction_type',
            'amount',
            'fee',
            'net_amount',
            'status',              // Canonical
            'settlement_status',   // Canonical
            'provider_reference',
            'description',
            'created_at'
        ])
        ->orderBy('created_at', 'desc')
        ->paginate(100);
    
    // Transform for admin frontend
    $transactions->getCollection()->transform(function ($transaction) {
        return [
            'id' => $transaction->id,
            'transaction_ref' => $transaction->transaction_ref,
            'session_id' => $transaction->session_id,
            'transaction_type' => $transaction->transaction_type,
            'transaction_type_label' => $this->getTransactionTypeLabel($transaction->transaction_type),
            'company_name' => $transaction->company->company_name ?? 'N/A',
            'customer_name' => $transaction->customer->name ?? 'N/A',
            'customer_email' => $transaction->customer->email ?? '',
            'amount' => number_format($transaction->amount, 2),
            'fee' => number_format($transaction->fee, 2),
            'net_amount' => number_format($transaction->net_amount, 2),
            'status' => $transaction->status,  // Canonical
            'status_label' => $this->getStatusLabel($transaction->status),
            'settlement_status' => $transaction->settlement_status,  // Canonical
            'settlement_label' => $this->getSettlementLabel($transaction->settlement_status),
            'description' => $transaction->description ?? '',
            'date' => $transaction->created_at->format('Y-m-d H:i:s'),
            'created_at' => $transaction->created_at->toISOString()
        ];
    });
    
    return response()->json($transactions);
}
```

**Key Changes:**
- Shows ALL 7 transaction types
- Query ONLY from transactions table
- Eager load company + customer
- Use canonical status fields only
- No derived status
- No N/A values in response
- No ledger_entries joins

---

## PRIORITY 5: Settlement Integrity Checker

### Rule: If ledger debit exists + provider success confirmed + settlement_status != settled, then auto-fix

**File:** `app/Services/SettlementIntegrityService.php`

```php
<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionStatusLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettlementIntegrityService
{
    /**
     * Check and fix settlement integrity issues
     */
    public function checkAndFix(): array
    {
        $results = [
            'checked' => 0,
            'fixed' => 0,
            'errors' => []
        ];
        
        // Find transactions with integrity issues
        $transactions = Transaction::query()
            ->where('status', 'successful')
            ->where('settlement_status', '!=', 'settled')
            ->whereIn('transaction_type', [
                'va_deposit',
                'api_transfer',
                'company_withdrawal'
            ])
            ->get();
        
        foreach ($transactions as $transaction) {
            $results['checked']++;
            
            try {
                // Check if ledger debit exists
                $ledgerDebitExists = DB::table('ledger_entries')
                    ->where('transaction_id', $transaction->id)
                    ->where('entry_type', 'debit')
                    ->exists();
                
                if ($ledgerDebitExists) {
                    // Fix settlement status
                    $this->fixSettlementStatus($transaction);
                    $results['fixed']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Fix settlement status for transaction
     */
    private function fixSettlementStatus(Transaction $transaction): void
    {
        $oldSettlementStatus = $transaction->settlement_status;
        
        $transaction->update([
            'settlement_status' => 'settled'
        ]);
        
        // Log the fix
        TransactionStatusLog::create([
            'transaction_id' => $transaction->id,
            'old_status' => $transaction->status,
            'new_status' => $transaction->status,
            'old_settlement_status' => $oldSettlementStatus,
            'new_settlement_status' => 'settled',
            'changed_by' => 'system_integrity_check',
            'reason' => 'Auto-fix: Ledger debit exists + status successful but settlement_status was not settled',
            'metadata' => json_encode([
                'fixed_at' => now()->toISOString()
            ])
        ]);
        
        Log::info('Settlement status auto-fixed', [
            'transaction_id' => $transaction->id,
            'transaction_ref' => $transaction->transaction_ref,
            'old_settlement_status' => $oldSettlementStatus,
            'new_settlement_status' => 'settled'
        ]);
    }
}
```

#### Artisan Command

**File:** `app/Console/Commands/CheckSettlementIntegrity.php`

```php
<?php

namespace App\Console\Commands;

use App\Services\SettlementIntegrityService;
use Illuminate\Console\Command;

class CheckSettlementIntegrity extends Command
{
    protected $signature = 'settlements:check-integrity';
    protected $description = 'Check and fix settlement integrity issues';
    
    public function handle(SettlementIntegrityService $service)
    {
        $this->info('Checking settlement integrity...');
        
        $results = $service->checkAndFix();
        
        $this->info("Integrity check complete:");
        $this->info("  Checked: {$results['checked']}");
        $this->info("  Fixed: {$results['fixed']}");
        
        if (!empty($results['errors'])) {
            $this->error("  Errors: " . count($results['errors']));
        }
        
        return 0;
    }
}
```

#### Cron Job

Add to `app/Console/Kernel.php`:

```php
// Run integrity check every hour
$schedule->command('settlements:check-integrity')
         ->hourly()
         ->withoutOverlapping();
```

---

## Implementation Checklist

### Priority 1: Status Reconciliation ✓
- [ ] Create TransactionReconciliationService
- [ ] Create ReconcileTransactionStatus command
- [ ] Add cron job (every 5 minutes)
- [ ] Test reconciliation logic
- [ ] Verify status updates
- [ ] Verify ledger reversals

### Priority 2: Canonical Status Enforcement ✓
- [ ] Remove status from ledger_entries queries
- [ ] Remove status from wallet queries
- [ ] Remove status from provider_logs queries
- [ ] Add canonical status accessors to Transaction model
- [ ] Update all controllers to use canonical status

### Priority 3: RA Dashboard Refactor ✓
- [ ] Update AllRATransactions method
- [ ] Remove ledger_entries joins
- [ ] Add eager loading
- [ ] Filter by 4 customer-facing types only
- [ ] Use canonical status fields
- [ ] Test N+1 query prevention

### Priority 4: Admin Dashboard Refactor ✓
- [ ] Update AdminTransactionController
- [ ] Show all 7 transaction types
- [ ] Remove ledger_entries joins
- [ ] Add eager loading
- [ ] Use canonical status fields
- [ ] Ensure no N/A values

### Priority 5: Settlement Integrity ✓
- [ ] Create SettlementIntegrityService
- [ ] Create CheckSettlementIntegrity command
- [ ] Add cron job (hourly)
- [ ] Test auto-fix logic
- [ ] Verify status logs

---

## Testing Plan

### 1. Status Reconciliation Testing
```bash
# Run reconciliation manually
php artisan transactions:reconcile

# Check logs
tail -f storage/logs/laravel.log

# Verify status updates in database
SELECT id, transaction_ref, status, settlement_status, reconciliation_status 
FROM transactions 
WHERE status IN ('pending', 'processing');
```

### 2. Dashboard Query Testing
```bash
# Test RA dashboard endpoint
curl -H "Authorization: Bearer {token}" \
     https://app.pointwave.ng/api/transactions/ra-transactions

# Test Admin dashboard endpoint
curl -H "Authorization: Bearer {token}" \
     https://app.pointwave.ng/admin/transactions
```

### 3. Settlement Integrity Testing
```bash
# Run integrity check manually
php artisan settlements:check-integrity

# Check for mismatches
SELECT id, transaction_ref, status, settlement_status 
FROM transactions 
WHERE status = 'successful' 
AND settlement_status != 'settled';
```

---

## Success Criteria

Before proceeding to frontend:

✅ Status reconciliation service implemented and running
✅ Canonical status enforcement complete
✅ RA dashboard queries only transactions table
✅ Admin dashboard queries only transactions table
✅ No pending/success mismatches exist
✅ Settlement integrity checker running
✅ All cron jobs configured
✅ No N/A values in API responses
✅ No N+1 query issues
✅ All status logs working

---

## Next Steps After Backend Complete

1. Test all backend endpoints
2. Verify cron jobs running
3. Check logs for errors
4. Run integrity checks
5. Confirm no status mismatches
6. THEN proceed to frontend deployment

**DO NOT proceed to frontend until all backend priorities are complete.**
