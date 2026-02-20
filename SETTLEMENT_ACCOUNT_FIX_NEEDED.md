# Settlement Withdrawal Receipt - Account N/A Fix

## Current Status

✅ Settlement status fixed - now showing "settled" correctly  
❌ Sender account still showing "N/A" on receipt

## Issue

Settlement withdrawal receipts show "N/A" for the sender account number instead of the company's settlement account.

## Diagnostic Steps

Run this on the server to check the data:

```bash
cd /home/aboksdfs/app.pointwave.ng
php check_settlement_account_data.php
```

This will show:
- Company settlement account details
- Virtual account details  
- Transaction metadata
- What data is available for the receipt

## Expected Output

The script should reveal which field has the settlement account number:
- `company.settlement_account_number` (preferred)
- `company.account_number` (fallback)
- Virtual account number (last resort)

## Next Steps

1. **Pull latest code:**
   ```bash
   git pull origin main
   ```

2. **Run diagnostic:**
   ```bash
   php check_settlement_account_data.php
   ```

3. **Share the output** so I can see what data is available

4. **Fix the ReceiptService** based on what data exists

## Current Logic in ReceiptService.php

```php
if ($transaction->transaction_type === 'settlement_withdrawal') {
    $senderName = $companyName;
    $senderAccount = $company->settlement_account_number ?? $company->account_number ?? '';
    $senderBank = $company->settlement_bank_name ?? $company->bank_name ?? 'PalmPay';
}
```

The logic looks correct, so the issue is likely:
- The `settlement_account_number` field doesn't exist in the companies table
- OR the field exists but is NULL/empty
- OR we need to use a different field

## Frontend Changes (Already Deployed)

✅ Dynamic system name implementation complete
✅ All documentation components updated
✅ RATransactionDetails updated
✅ depositinvoice updated
✅ No hardcoded "PointPay" or "PointWave" text

Frontend was manually built and uploaded by you.

## Summary

- Backend: Pushed to GitHub (commit 78d3d0d)
- Frontend: Already manually deployed by you
- Remaining: Fix settlement account N/A issue after diagnostic
