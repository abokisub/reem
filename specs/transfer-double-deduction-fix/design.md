# Transfer Double Deduction Bugfix Design

## Overview

This design addresses a critical double deduction bug in the transfer flow where balance is checked and deducted twice during a single transfer operation. The bug occurs because:

1. `TransferPurchase.php` deducts balance from the company wallet and creates a transaction record with status 'pending'
2. The request is routed through `TransferRouter` → `BankingService` → `TransferService`
3. `TransferService.php` performs a second balance check and deduction, which fails because the balance was already deducted

The fix will modify `TransferService.php` to skip balance checking and deduction when called from the internal transfer flow, since `TransferPurchase.php` has already handled this. The service will only perform balance operations when called directly via API (not through the internal flow).

## Glossary

- **Bug_Condition (C)**: The condition that triggers the bug - when a transfer is initiated through TransferPurchase.php (internal flow) and TransferService.php attempts to check/deduct balance again
- **Property (P)**: The desired behavior - balance should be checked and deducted exactly once per transfer, allowing transfers to succeed when users have sufficient funds
- **Preservation**: Existing transfer functionality, refund logic, transaction recording, and direct API calls to TransferService must remain unchanged
- **TransferPurchase**: Controller in `app/Http/Controllers/Purchase/TransferPurchase.php` that handles transfer requests from web/mobile/API, performs initial balance deduction
- **TransferService**: Service in `app/Services/PalmPay/TransferService.php` that handles PalmPay provider integration
- **TransferRouter**: Router in `app/Services/TransferRouter.php` that routes transfer requests to BankingService
- **BankingService**: Service in `app/Services/Banking/BankingService.php` that delegates to TransferService
- **Internal Flow**: Transfer initiated through TransferPurchase → TransferRouter → BankingService → TransferService (balance already deducted)
- **Direct API Flow**: Transfer initiated directly via TransferService API (balance not yet deducted)

## Bug Details

### Fault Condition

The bug manifests when a transfer is initiated through the internal flow (TransferPurchase → TransferRouter → BankingService → TransferService). The `initiateTransfer` method in TransferService performs a balance check and deduction even though TransferPurchase has already deducted the balance, causing the second check to fail with "Insufficient balance to cover amount and fees".

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type TransferRequest
  OUTPUT: boolean
  
  RETURN input.source == 'internal_flow'
         AND balanceAlreadyDeducted(input.reference)
         AND TransferService.initiateTransfer() attempts balance check/deduction
END FUNCTION
```

### Examples

- **Example 1**: User has ₦10,000 balance, initiates ₦5,000 transfer with ₦50 fee
  - TransferPurchase deducts ₦5,050, balance becomes ₦4,950
  - TransferService checks if ₦4,950 >= ₦5,050 → FAILS with "Insufficient balance"
  - Expected: Transfer should succeed since original balance was sufficient

- **Example 2**: User has ₦20,000 balance, initiates ₦15,000 transfer with ₦150 fee
  - TransferPurchase deducts ₦15,150, balance becomes ₦4,850
  - TransferService checks if ₦4,850 >= ₦15,150 → FAILS with "Insufficient balance"
  - Expected: Transfer should succeed since original balance was sufficient

- **Example 3**: User has ₦3,000 balance, attempts ₦5,000 transfer
  - TransferPurchase checks balance → FAILS immediately with "Insufficient Funds"
  - TransferService is never called
  - Expected: This should continue to fail (correct behavior)

- **Edge Case**: Direct API call to TransferService (not through internal flow)
  - TransferService should perform balance check and deduction
  - Expected: This flow should remain unchanged

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- Initial balance validation in TransferPurchase must continue to reject transfers with genuinely insufficient balance
- Transaction record creation with correct status values must remain unchanged
- Refund logic when transfers fail must continue to work correctly
- Direct API calls to TransferService (if any exist) must continue to perform balance operations
- Settlement processing and transaction status updates must remain unchanged
- Ledger accounting and double-entry bookkeeping must remain accurate

**Scope:**
All inputs that do NOT involve the internal transfer flow (TransferPurchase → TransferRouter → BankingService → TransferService) should be completely unaffected by this fix. This includes:
- Transfers that fail initial balance check in TransferPurchase (genuinely insufficient funds)
- Refund operations when transfers fail
- Direct API calls to TransferService methods
- Transaction status updates and webhook processing

## Hypothesized Root Cause

Based on the code analysis, the root cause is clear:

1. **Architectural Duplication**: The system has two layers performing the same balance operation:
   - **Layer 1 (TransferPurchase)**: Lines 165-195 perform balance check and deduction with pessimistic locking
   - **Layer 2 (TransferService)**: Lines 52-88 perform another balance check and deduction with pessimistic locking

2. **Missing Context Flag**: TransferService.initiateTransfer() does not know whether it's being called from the internal flow (where balance is already deducted) or from a direct API call (where balance needs to be deducted)

3. **Transaction Status Mismatch**: 
   - TransferPurchase creates transaction with status 'pending' after deducting balance
   - TransferService expects to create transaction with status 'debited' and perform the deduction itself

4. **Ledger Duplication**: Both layers attempt to record ledger entries, leading to potential double-accounting

## Correctness Properties

Property 1: Fault Condition - Single Balance Deduction Per Transfer

_For any_ transfer request initiated through the internal flow (TransferPurchase → TransferRouter → BankingService → TransferService), the system SHALL check and deduct the balance exactly once in TransferPurchase, and TransferService SHALL skip balance checking and deduction, allowing the transfer to proceed successfully when the user has sufficient funds.

**Validates: Requirements 2.1, 2.2, 2.3**

Property 2: Preservation - Insufficient Balance Rejection

_For any_ transfer request where the user has genuinely insufficient balance (before any deduction), the system SHALL continue to reject the transfer with an appropriate error message in TransferPurchase, preserving the existing validation behavior.

**Validates: Requirements 3.1**

Property 3: Preservation - Transaction Recording and Refunds

_For any_ transfer that is successfully initiated or fails after initiation, the system SHALL continue to maintain accurate transaction records with correct status values and handle refunds appropriately, preserving existing transaction management behavior.

**Validates: Requirements 3.2, 3.3, 3.4**

## Fix Implementation

### Changes Required

The fix involves modifying the transfer flow to eliminate the double deduction while maintaining backward compatibility and proper transaction management.

**File**: `app/Http/Controllers/Purchase/TransferPurchase.php`

**Function**: `TransferRequest`

**Specific Changes**:
1. **Pass Context Flag**: Add a flag to the transfer details indicating that balance has already been deducted
   - Add `'balance_already_deducted' => true` to the `$transferDetails` array (around line 260)
   - Add `'transaction_reference' => $transid` to pass the existing transaction reference

2. **Pass Transaction Reference**: Include the transaction reference so TransferService can update the existing transaction instead of creating a new one
   - This prevents duplicate transaction records

**File**: `app/Services/Banking/BankingService.php`

**Function**: `transfer`

**Specific Changes**:
1. **Forward Context Flag**: Pass the `balance_already_deducted` flag from details to TransferService
   - Add the flag to the `$transferData` array (around line 77)

**File**: `app/Services/PalmPay/TransferService.php`

**Function**: `initiateTransfer`

**Specific Changes**:
1. **Check Context Flag**: At the beginning of the method, check if `$transferData['balance_already_deducted']` is true
   - If true, skip balance checking and deduction (lines 52-88)
   - If false or not set, perform balance operations as before (backward compatibility)

2. **Handle Existing Transaction**: If `$transferData['transaction_reference']` is provided:
   - Look up the existing transaction instead of creating a new one
   - Update the existing transaction status from 'pending' to 'debited'
   - Skip ledger recording since it was already done in TransferPurchase
   - Use the existing transaction's balance_before and balance_after values

3. **Conditional Ledger Recording**: Only record ledger entries if balance was deducted in this method
   - If `balance_already_deducted` is true, skip lines 68-88 (ledger recording and wallet updates)
   - If false, perform ledger recording as before

4. **Transaction Creation Logic**: Modify transaction creation to handle both scenarios:
   - If transaction_reference provided: Update existing transaction
   - If not provided: Create new transaction (backward compatibility for direct API calls)

5. **Preserve Provider Call**: The `processPalmPayTransfer` call (line 127) should execute in both scenarios
   - This is the actual provider integration and must always happen

### Implementation Pseudocode

```php
// In TransferService.php initiateTransfer method

public function initiateTransfer(int $companyId, array $transferData): Transaction
{
    try {
        return DB::transaction(function () use ($companyId, $transferData) {
            $balanceAlreadyDeducted = $transferData['balance_already_deducted'] ?? false;
            $existingReference = $transferData['transaction_reference'] ?? null;
            
            if ($balanceAlreadyDeducted && $existingReference) {
                // INTERNAL FLOW: Balance already deducted by TransferPurchase
                
                // 1. Look up existing transaction
                $transaction = Transaction::where('reference', $existingReference)
                    ->where('company_id', $companyId)
                    ->firstOrFail();
                
                // 2. Update status from 'pending' to 'debited'
                $transaction->update([
                    'status' => 'debited',
                    'provider' => 'palmpay',
                    'reconciliation_status' => 'not_started',
                ]);
                
                // 3. Skip balance operations and ledger recording
                // (already done in TransferPurchase)
                
            } else {
                // DIRECT API FLOW: Perform balance operations
                
                // Get company wallet with lock
                $wallet = CompanyWallet::where('company_id', $companyId)
                    ->where('currency', 'NGN')
                    ->lockForUpdate()
                    ->firstOrFail();
                
                // Calculate fees
                $amount = $transferData['amount'];
                $feeResults = $this->feeService->calculateFee($companyId, $amount, 'transfer');
                $fee = (float) $feeResults['fee'];
                $totalAmount = $amount + $fee;
                
                // Balance check
                if ($wallet->balance < $totalAmount) {
                    throw new \Exception('Insufficient balance to cover amount and fees');
                }
                
                // Record ledger entries
                // ... (existing ledger code)
                
                // Deduct from wallet
                $wallet->decrement('balance', $totalAmount);
                // ... (existing wallet update code)
                
                // Create new transaction
                $transaction = Transaction::create([...]);
            }
            
            // 4. Trigger provider call (BOTH flows)
            DB::afterCommit(function () use ($transaction) {
                $this->processPalmPayTransfer($transaction);
            });
            
            return $transaction;
        });
        
    } catch (\Exception $e) {
        Log::error('Failed to Initiate Transfer (Ledger Error)', [
            'company_id' => $companyId,
            'error' => $e->getMessage()
        ]);
        
        throw $e;
    }
}
```

## Testing Strategy

### Validation Approach

The testing strategy follows a two-phase approach: first, surface counterexamples that demonstrate the bug on unfixed code, then verify the fix works correctly and preserves existing behavior.

### Exploratory Fault Condition Checking

**Goal**: Surface counterexamples that demonstrate the bug BEFORE implementing the fix. Confirm the root cause analysis by observing the double deduction behavior.

**Test Plan**: Write tests that simulate transfer requests through the internal flow with sufficient balance. Run these tests on the UNFIXED code to observe "Insufficient balance" failures and confirm the double deduction issue.

**Test Cases**:
1. **Sufficient Balance Transfer**: User with ₦10,000 initiates ₦5,000 transfer (will fail on unfixed code with "Insufficient balance")
2. **Large Balance Transfer**: User with ₦100,000 initiates ₦50,000 transfer (will fail on unfixed code)
3. **Minimum Balance Transfer**: User with exact amount needed (₦5,050 for ₦5,000 transfer + ₦50 fee) (will fail on unfixed code)
4. **Multiple Sequential Transfers**: User initiates two transfers in sequence (second will fail even if balance is sufficient)

**Expected Counterexamples**:
- TransferPurchase successfully deducts balance and creates 'pending' transaction
- TransferService balance check fails with "Insufficient balance to cover amount and fees"
- System logs "Failed to Initiate Transfer (Ledger Error)" and "BankingService Transfer Error"
- Refund process is triggered, returning funds to user

### Fix Checking

**Goal**: Verify that for all inputs where the bug condition holds (internal flow transfers with sufficient balance), the fixed function produces the expected behavior (single deduction, successful transfer).

**Pseudocode:**
```
FOR ALL input WHERE isBugCondition(input) DO
  result := processTransfer_fixed(input)
  ASSERT result.status IN ['debited', 'processing', 'successful']
  ASSERT balanceDeductedOnce(input.company_id, input.amount)
  ASSERT transactionRecordExists(input.reference)
  ASSERT providerCallInitiated(input.reference)
END FOR
```

**Test Cases**:
1. **Internal Flow with Sufficient Balance**: Verify balance deducted once, transfer succeeds
2. **Internal Flow with Multiple Transfers**: Verify each transfer deducts balance once
3. **Transaction Status Progression**: Verify status moves from 'pending' → 'debited' → 'processing'
4. **Ledger Accuracy**: Verify no duplicate ledger entries

### Preservation Checking

**Goal**: Verify that for all inputs where the bug condition does NOT hold, the fixed function produces the same result as the original function.

**Pseudocode:**
```
FOR ALL input WHERE NOT isBugCondition(input) DO
  ASSERT processTransfer_original(input) = processTransfer_fixed(input)
END FOR
```

**Testing Approach**: Property-based testing is recommended for preservation checking because:
- It generates many test cases automatically across the input domain
- It catches edge cases that manual unit tests might miss
- It provides strong guarantees that behavior is unchanged for all non-buggy inputs

**Test Plan**: Observe behavior on UNFIXED code first for insufficient balance cases and direct API calls, then write property-based tests capturing that behavior.

**Test Cases**:
1. **Insufficient Balance Rejection**: Observe that TransferPurchase rejects transfers with insufficient balance on unfixed code, verify this continues after fix
2. **Refund on Provider Failure**: Observe that refunds work correctly when provider calls fail on unfixed code, verify this continues after fix
3. **Transaction Status Updates**: Observe that transaction statuses update correctly on unfixed code, verify this continues after fix
4. **Direct API Calls**: If direct API calls to TransferService exist, verify they continue to work with balance operations

### Unit Tests

- Test internal flow with sufficient balance (should succeed after fix)
- Test internal flow with insufficient balance (should fail in TransferPurchase)
- Test that balance is deducted exactly once in internal flow
- Test that transaction status progresses correctly (pending → debited → processing)
- Test that existing transaction is updated rather than creating duplicate
- Test that ledger entries are not duplicated
- Test edge case: exact balance amount (amount + fee)

### Property-Based Tests

- Generate random transfer amounts and balances, verify single deduction for internal flow
- Generate random company configurations, verify balance operations work correctly
- Generate random transfer sequences, verify no race conditions or double deductions
- Test that all insufficient balance cases continue to be rejected appropriately

### Integration Tests

- Test full transfer flow from TransferPurchase through to PalmPay provider call
- Test that refunds work correctly when provider calls fail
- Test that transaction records are accurate and complete
- Test that settlements process correctly after successful transfers
- Test concurrent transfers from same company (race condition prevention)
- Test that webhook processing updates transaction status correctly

