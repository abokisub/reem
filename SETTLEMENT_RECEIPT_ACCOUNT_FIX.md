# Settlement Receipt Account Number Fix

## Problem
Settlement withdrawal receipts were showing "N/A" for the sender account number, even though the `settlement_account_number` field exists in the database with the correct value (7040540018).

## Root Cause
The issue was in `app/Services/ReceiptService.php`. The service was loading the company via the Eloquent relationship:

```php
$company = $transaction->company;
```

This could result in a cached or incomplete model instance that doesn't have all attributes loaded, particularly the `settlement_account_number` field.

## Solution
Changed the ReceiptService to explicitly reload the company from the database:

```php
$company = \App\Models\Company::find($transaction->company_id);
```

This ensures we always get a fresh copy of the company with all attributes properly loaded from the database.

## Changes Made

### File: `app/Services/ReceiptService.php`
- Changed company loading from relationship to explicit database query
- Added null check for company
- Changed `??` operator to `?:` for better empty string handling

## Deployment Steps

Run this on the server:

```bash
bash DEPLOY_RECEIPT_ACCOUNT_FIX.sh
```

Or manually:

```bash
# Pull latest code
git pull origin main

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Test the fix
php debug_settlement_receipt_final.php
```

## Verification

After deployment:
1. Visit the settlement withdrawal receipt URL
2. Check the "SENDER DETAILS" section
3. The "Account Number" should now show: 7040540018
4. The "Bank" should show: OPay

## Technical Details

The `settlement_account_number` column was added to the `companies` table and is in the `$fillable` array of the Company model. The data exists in the database, but Laravel's Eloquent relationship loading was not consistently loading this attribute.

By using `Company::find()` instead of `$transaction->company`, we bypass any relationship caching and ensure a fresh query that loads all attributes.

## Status
✅ Fix deployed and ready for testing
