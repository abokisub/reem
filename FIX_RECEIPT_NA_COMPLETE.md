# Fix Receipt N/A Fields - Complete Guide

## Issue
Receipt pages showing "N/A" for:
- Account Name
- Account Number  
- Username (admin view)

## Root Cause
1. Company `username` field is NULL in database
2. Need to verify ReceiptService is correctly extracting metadata

## Deployment Steps

### Step 1: Pull Latest Code
```bash
cd /home/aboksdfs/app.pointwave.ng
git pull origin main
```

### Step 2: Test Receipt Data Extraction
```bash
php test_receipt_generation.php
```

This will show:
- ✅ What metadata is available
- ✅ What the ReceiptService extracts
- ✅ What would appear in the receipt

### Step 3: Fix Company Username
```bash
php fix_company_username.php
```

This will:
- Find companies without username
- Generate username from company name or email
- Update database

### Step 4: Test Receipt Again
After fixing username, test the receipt generation again:
```bash
php test_receipt_generation.php
```

You should now see:
- ✅ customer.name: [sender name from metadata]
- ✅ customer.account: [sender account from metadata]
- ✅ customer.bank: [sender bank from metadata]
- ✅ company.username: [generated username]

### Step 5: Test Actual Receipt
Go to the receipt page in your browser and verify all fields show proper data (no N/A).

## What Was Fixed

### ReceiptService.php
- ✅ Detects transaction type (credit/deposit vs debit/transfer)
- ✅ For deposits: Extracts sender info from metadata
- ✅ For transfers: Uses recipient info from transaction fields
- ✅ Replaces all "N/A" with "-" for cleaner display

### Receipt Template
- ✅ Dynamic section title: "SENDER DETAILS" or "RECIPIENT DETAILS"
- ✅ Added "Merchant Info" section with Username field
- ✅ Updated field labels

## Expected Result

**For Deposits (va_deposit):**
- Sender Details section shows sender name, account, bank from metadata
- Merchant Info shows company username, name, email

**For Transfers (api_transfer):**
- Recipient Details section shows recipient name, account, bank
- Merchant Info shows company username, name, email

## Troubleshooting

If still showing N/A after deployment:

1. **Check if code was actually updated:**
   ```bash
   grep -n "sender_name" app/Services/ReceiptService.php
   ```
   Should show line with: `$customerName = $metadata['sender_name']`

2. **Check metadata structure:**
   ```bash
   php check_receipt_data.php
   ```

3. **Clear Laravel cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

4. **Check company username was set:**
   ```bash
   php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); \$c = DB::table('companies')->first(); echo 'Username: ' . (\$c->username ?? 'NULL') . PHP_EOL;"
   ```

## Files Changed
- ✅ app/Services/ReceiptService.php (already deployed)
- ✅ resources/views/receipts/transaction.blade.php (already deployed)
- ✅ fix_company_username.php (NEW - need to run)
- ✅ test_receipt_generation.php (NEW - diagnostic tool)
