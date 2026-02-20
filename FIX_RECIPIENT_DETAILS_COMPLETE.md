# Fix Recipient Details on Receipt - Complete Guide

## Issue
The receipt is showing "N/A" for Recipient Details even though:
- Virtual account data exists in database
- Code has been updated to fetch recipient details
- Sender details are showing correctly

## Root Cause
The receipt PDF is likely being cached by the browser or Laravel's view cache needs to be cleared.

## Solution

### Step 1: Verify Current Code is Deployed

The code has already been updated in these files:
- `app/Services/ReceiptService.php` - Fetches both sender and recipient data
- `resources/views/receipts/transaction.blade.php` - Displays both sections

### Step 2: Clear All Caches on Server

Run these commands to clear all caches:

```bash
# Clear Laravel caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear

# Clear OPcache (if enabled)
php artisan optimize:clear
```

### Step 3: Test Receipt Generation

After clearing caches, try generating a new receipt:

1. Go to the transaction in your dashboard
2. Click "View Receipt" or "Download Receipt"
3. The receipt should now show:
   - **SENDER DETAILS**: ABOKI TELECOMMUNICATION SERVICES, 7040540018, OPAY
   - **RECIPIENT DETAILS**: PointWave Business-Jamil Abubakar Bashir(PointWave), 6690945661, PalmPay

## What the Code Does

### For Deposits (va_deposit):
- **Sender**: The person who sent money (from metadata)
- **Recipient**: The virtual account that received the money (from virtual_accounts table)

### For Transfers (api_transfer):
- **Sender**: The company making the transfer
- **Recipient**: The beneficiary receiving the transfer (from transaction fields)

## Verification Script

Run this to verify the data is correct:

```bash
php check_virtual_account_data.php
```

Expected output:
```
✅ Virtual Account Found:
Account Number: 6690945661
Account Name: PointWave Business-Jamil Abubakar Bashir(PointWave)
Bank Name: PalmPay
```

## If Still Showing N/A

If the receipt still shows "N/A" after clearing caches:

1. Check browser cache - try in incognito/private mode
2. Check if PDF is being cached - add a timestamp to the filename
3. Verify the transaction has the correct data:

```bash
php artisan tinker
$txn = \App\Models\Transaction::where('transaction_id', 'txn_699861639a0ca24142')->first();
$txn->company->virtualAccounts()->first();
```

## Files Modified

1. `app/Services/ReceiptService.php` - Added recipient data extraction
2. `resources/views/receipts/transaction.blade.php` - Added RECIPIENT DETAILS section

## Next Steps

After clearing caches, the receipt should display correctly. If you create a NEW transaction, it will definitely show the correct recipient details.
