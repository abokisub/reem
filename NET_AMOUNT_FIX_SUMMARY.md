# Net Amount Calculation Fix - Complete Summary

## Problem

For withdrawals/transfers, the `net_amount` field was showing the wrong value.

### Example:
- Company withdraws **₦100**
- System charges **₦15** fee
- Total deducted from wallet: **₦115** (100 + 15)
- **OLD net_amount**: **₦85** (WRONG! - calculated as 100 - 15)
- **NEW net_amount**: **₦100** (CORRECT - what recipient receives)

## Root Cause

The Transaction model (`app/Models/Transaction.php`) was calculating `net_amount` the same way for both credits and debits:

```php
// OLD CODE (WRONG)
$transaction->net_amount = $transaction->amount - ($transaction->fee ?? 0);
```

This is correct for CREDIT transactions (deposits) but WRONG for DEBIT transactions (withdrawals/transfers).

## The Correct Logic

### For CREDIT Transactions (Deposits):
- **Amount**: What was deposited (₦100)
- **Fee**: What system charges (₦15)
- **Net Amount**: Amount - Fee = **₦85** (what company receives after fee)
- **Total**: Amount (₦100) - company gets ₦85 after ₦15 fee

### For DEBIT Transactions (Withdrawals/Transfers):
- **Amount**: What company wants to send (₦100)
- **Fee**: What system charges (₦15)
- **Net Amount**: Amount = **₦100** (what recipient receives)
- **Total Deduction**: Amount + Fee = **₦115** (deducted from wallet)

## The Fix

### Backend Changes (`app/Models/Transaction.php`):

**BEFORE:**
```php
// Calculate net_amount automatically
$transaction->net_amount = $transaction->amount - ($transaction->fee ?? 0);
```

**AFTER:**
```php
// Calculate net_amount automatically based on transaction type
// For CREDIT (deposits): net_amount = amount - fee (what company receives after fee)
// For DEBIT (withdrawals/transfers): net_amount = amount (what recipient receives, fee is separate)
if (!isset($transaction->net_amount)) {
    if ($transaction->type === 'credit') {
        // Deposit: Company receives amount minus fee
        $transaction->net_amount = $transaction->amount - ($transaction->fee ?? 0);
    } else {
        // Withdrawal/Transfer: Recipient receives the full amount, fee is added to total
        $transaction->net_amount = $transaction->amount;
    }
}
```

### Fix Script (`fix_net_amount_for_debits.php`):

This script updates all existing debit transactions to have the correct `net_amount`:

```php
// For all debit transactions
net_amount = amount (not amount - fee)
```

## Deployment Instructions

### On Server (cPanel):

```bash
bash FIX_NET_AMOUNT_CALCULATION.sh
```

This will:
1. Pull latest code from GitHub
2. Fix all existing debit transactions
3. Clear Laravel caches

## Expected Result

### Before Fix:
```
Withdrawal of ₦100 with ₦15 fee:
- Amount: ₦100
- Fee: ₦15
- Net Amount: ₦85 ❌ (WRONG!)
- Total Deducted: ₦115
```

### After Fix:
```
Withdrawal of ₦100 with ₦15 fee:
- Amount: ₦100
- Fee: ₦15
- Net Amount: ₦100 ✅ (CORRECT!)
- Total Deducted: ₦115
```

## What This Means

### For Receipts/Statements:
- **Gross Amount**: ₦100 (what was sent)
- **Fee**: -₦15 (what was charged)
- **Net Amount**: ₦100 (what recipient receives)
- **Total Deducted**: ₦115 (from company wallet)

### For Accounting:
- Company wallet debited: ₦115
- Recipient receives: ₦100
- System earns: ₦15 (fee)

## Files Changed

- `app/Models/Transaction.php` - Fixed net_amount calculation logic
- `fix_net_amount_for_debits.php` - Script to fix existing transactions
- Commit: `d99a61a` - "Fix net_amount calculation: For debits, net_amount should equal amount (not amount - fee)"

## Status

✅ Backend changes pushed to GitHub
✅ Fix script created for existing transactions
⏳ Awaiting deployment on server
⏳ Awaiting user to test

---

**Date:** February 20, 2026
**Developer:** Kiro AI Assistant
