# Settlement Withdrawal Fee - Complete Fix

## Problem Identified

When companies withdraw funds to their settlement account, the system was charging ₦0.50 (0.5% external transfer fee) instead of ₦15 (settlement withdrawal flat fee).

### Root Cause

The `bank_code` field in the `companies` table was NULL, preventing the system from detecting settlement withdrawals.

The detection logic in `TransferPurchase.php` checks:
```php
if ($company->settlement_account_number == $accountNumber && 
    $company->bank_code == $bankCode) {
    $isSettlementWithdrawal = true;
}
```

Since `bank_code` was NULL, this condition never matched, so all transfers were treated as "External Transfer".

## Fixes Applied

### 1. Fix Existing Company (Immediate Fix)
**File**: `FIX_SETTLEMENT_BANK_CODE.php`

Run on live server to update your existing company:
```bash
cd app.pointwave.ng
git pull origin main
php FIX_SETTLEMENT_BANK_CODE.php
```

This sets `bank_code = '100004'` for the company with settlement account `7040540018`.

### 2. Fix Future Companies (Permanent Fix)
**File**: `app/Http/Controllers/API/AuthController.php`

Added `bank_code` to the company data when companies register:
```php
'bank_code' => $user->paystack_bank_code, // CRITICAL: Save bank code for settlement withdrawal fee detection
```

Now when new companies register and provide their settlement account details, the `bank_code` will be automatically saved.

## Fee Configuration

Your current live server settings:
- **Settlement Withdrawal Fee**: ₦15 FLAT
- **External Transfer Fee**: 0.5% PERCENTAGE (cap: ₦500)

## How It Works Now

### Settlement Withdrawal (to registered settlement account)
- Account: 7040540018
- Bank Code: 100004
- Fee: ₦15 (flat)
- Example: Withdraw ₦100 → Deduct ₦115 from wallet → Send ₦100 to settlement account

### External Transfer (to any other account)
- Any other account number or bank code
- Fee: 0.5% of amount (max ₦500)
- Example: Transfer ₦100 → Deduct ₦100.50 from wallet → Send ₦100 to recipient

## Testing

After running the fix script, test by:
1. Making a transfer to your settlement account (7040540018, bank code 100004)
2. Check the fee charged - should be ₦15
3. Check transaction record - `Is Settlement Withdrawal: YES`

## Deployment Steps

1. Pull latest code on live server:
```bash
cd app.pointwave.ng
git pull origin main
```

2. Run the fix script for existing company:
```bash
php FIX_SETTLEMENT_BANK_CODE.php
```

3. Verify the fix:
```bash
php LIVE_SERVER_CHECK_FEES.php
```

You should see:
- `Bank Code: 100004` (not "NOT SET")
- `Is Settlement Withdrawal: YES` for transfers to 7040540018

## Files Modified

1. `app/Http/Controllers/API/AuthController.php` - Added bank_code to company registration
2. `FIX_SETTLEMENT_BANK_CODE.php` - Script to fix existing company
3. `LIVE_SERVER_CHECK_FEES.php` - Verification script

## Summary

- **Immediate fix**: Run `FIX_SETTLEMENT_BANK_CODE.php` to fix your existing company
- **Permanent fix**: New companies will automatically have `bank_code` saved during registration
- **Result**: Settlement withdrawals now correctly charge ₦15 fee instead of ₦0.50
