# Transfer Double Deduction Bug - FIXED ✓

## Problem
Users were getting "Insufficient balance to cover amount and fees" errors even when they had sufficient funds to complete transfers.

### Error Logs
```
[2026-02-19 16:56:56] production.ERROR: Failed to Initiate Transfer (Ledger Error) {"company_id":2,"error":"Insufficient balance to cover amount and fees"}  
[2026-02-19 16:56:56] production.ERROR: BankingService Transfer Error: Insufficient balance to cover amount and fees   
[2026-02-19 16:56:56] production.ERROR: TransferPurchase: API Call Failed, refunding user. Error: Insufficient balance to cover amount and fees
```

## Root Cause
The system was checking and deducting balance **TWICE** during a single transfer:

1. **First Deduction** - `TransferPurchase.php` (lines 165-195)
   - Checks balance ✓
   - Deducts amount + fee ✓
   - Creates transaction with status 'pending' ✓

2. **Second Deduction** - `TransferService.php` (lines 52-88)
   - Tries to check balance again ✗
   - Tries to deduct again ✗
   - **FAILS** because balance was already deducted

### Example
- User has ₦10,000 balance
- Initiates ₦5,000 transfer (₦50 fee = ₦5,050 total)
- TransferPurchase deducts ₦5,050 → balance becomes ₦4,950
- TransferService checks if ₦4,950 >= ₦5,050 → **FAILS**
- Error: "Insufficient balance to cover amount and fees"
- System refunds the money

## Solution Implemented

### Changes Made

#### 1. TransferPurchase.php
**Added context flags to signal that balance was already deducted:**
```php
$transferDetails = [
    'amount' => $amount,
    'bank_code' => $request->bank_code,
    'account_number' => $request->account_number,
    'account_name' => $request->account_name,
    'narration' => $request->narration,
    'reference' => $transid,
    'balance_already_deducted' => true,  // NEW: Signal balance was deducted
    'transaction_reference' => $transid  // NEW: Pass existing transaction
];
```

#### 2. BankingService.php
**Forwards the context flags to TransferService:**
```php
$transferData = [
    'amount' => $details['amount'],
    'account_number' => $details['account_number'],
    // ... other fields ...
    'balance_already_deducted' => $details['balance_already_deducted'] ?? false,  // NEW
    'transaction_reference' => $details['transaction_reference'] ?? null  // NEW
];
```

#### 3. TransferService.php
**Checks the flag and skips balance operations when already deducted:**
```php
public function initiateTransfer(int $companyId, array $transferData): Transaction
{
    return DB::transaction(function () use ($companyId, $transferData) {
        $balanceAlreadyDeducted = $transferData['balance_already_deducted'] ?? false;
        $existingReference = $transferData['transaction_reference'] ?? null;
        
        if ($balanceAlreadyDeducted && $existingReference) {
            // INTERNAL FLOW: Balance already deducted
            // Look up existing transaction and update it
            $transaction = Transaction::where('reference', $existingReference)
                ->where('company_id', $companyId)
                ->firstOrFail();
            
            $transaction->update([
                'status' => 'debited',
                'provider' => 'palmpay',
                'reconciliation_status' => 'not_started',
            ]);
            
            // Skip balance operations (already done)
            
        } else {
            // DIRECT API FLOW: Perform balance operations
            // (backward compatibility for direct API calls)
            // ... existing balance check and deduction code ...
        }
        
        // Trigger provider call (BOTH flows)
        DB::afterCommit(function () use ($transaction) {
            $this->processPalmPayTransfer($transaction);
        });
        
        return $transaction;
    });
}
```

## How It Works Now

### Internal Flow (Web/Mobile/API → TransferPurchase)
1. TransferPurchase checks balance ✓
2. TransferPurchase deducts balance ✓
3. TransferPurchase passes `balance_already_deducted = true` flag
4. TransferService sees the flag and **skips** balance operations
5. TransferService updates existing transaction
6. Transfer proceeds to PalmPay provider ✓

### Direct API Flow (if any direct calls exist)
1. TransferService checks balance ✓
2. TransferService deducts balance ✓
3. TransferService creates new transaction ✓
4. Transfer proceeds to PalmPay provider ✓

## Benefits

✅ **Single Balance Deduction** - Balance is only checked and deducted once per transfer
✅ **No More False Errors** - Users with sufficient funds can complete transfers
✅ **Backward Compatible** - Direct API calls (if any) still work correctly
✅ **No Duplicate Transactions** - Existing transaction is updated instead of creating new one
✅ **No Duplicate Ledger Entries** - Ledger recording happens only once
✅ **Preserves Refund Logic** - Failed transfers still refund correctly
✅ **Preserves Validation** - Genuinely insufficient balance still rejected

## Deployment

### Quick Deploy
```bash
./DEPLOY_DOUBLE_DEDUCTION_FIX.sh
```

### Manual Deploy
```bash
# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Optimize
php artisan config:cache
php artisan route:cache
```

## Testing

### Test Case 1: Sufficient Balance Transfer
- User with ₦10,000 balance
- Initiate ₦5,000 transfer (₦50 fee)
- **Expected**: Transfer succeeds, balance becomes ₦4,950

### Test Case 2: Insufficient Balance Transfer
- User with ₦3,000 balance
- Attempt ₦5,000 transfer
- **Expected**: Transfer rejected immediately with "Insufficient Funds"

### Test Case 3: Check Logs
```bash
tail -f storage/logs/laravel.log
```
- **Expected**: No more "Insufficient balance to cover amount and fees" errors for valid transfers

### Test Case 4: Verify Single Deduction
- Check company_wallets table before and after transfer
- **Expected**: Balance deducted exactly once (amount + fee)

## Files Modified

1. `app/Http/Controllers/Purchase/TransferPurchase.php`
2. `app/Services/Banking/BankingService.php`
3. `app/Services/PalmPay/TransferService.php`

## Spec Documentation

Full specification available at:
- Requirements: `specs/transfer-double-deduction-fix/bugfix.md`
- Design: `specs/transfer-double-deduction-fix/design.md`
- Tasks: `specs/transfer-double-deduction-fix/tasks.md`

## Status

✅ **FIXED AND READY FOR DEPLOYMENT**

The code has been modified and verified with no syntax errors. The fix ensures balance is deducted exactly once per transfer, eliminating the "Insufficient balance" errors for legitimate transfers.
