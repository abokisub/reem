# Fresh Testing Guide - Settlement Status Fix

## Step 1: Deploy the Fix to Server

```bash
# On server
cd /home/aboksdfs/app.pointwave.ng
git pull origin main
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

## Step 2: Reset System for Fresh Testing

```bash
# This will clear all transactions and reset balances
php reset_for_fresh_testing.php
```

When prompted, type `YES` to confirm.

This will:
- Delete all transactions
- Clear settlement queue
- Clear all webhook logs
- Reset company wallet balances to ₦199.00
- Reset system wallet balances to ₦0.00

## Step 3: Make a Test Deposit

### Option A: Deposit to CLIENT Virtual Account (Should be UNSETTLED)

1. Get a client virtual account number:
```bash
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); \$va = DB::table('virtual_accounts')->whereNotNull('company_user_id')->first(); echo 'Client VA: ' . \$va->palmpay_account_number . PHP_EOL;"
```

2. Send ₦100 to that account number

3. Check the result:
```bash
php check_all_transactions.php
```

Expected result:
- `settlement_status` should be `'unsettled'`
- Transaction should appear in settlement_queue
- Company dashboard should show "Pending Settlement: ₦99.50" (after ₦0.50 fee)

### Option B: Deposit to MASTER Virtual Account (Should be SETTLED)

1. Get the master virtual account number:
```bash
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); \$va = DB::table('virtual_accounts')->whereNull('company_user_id')->first(); echo 'Master VA: ' . \$va->palmpay_account_number . PHP_EOL;"
```

2. Send ₦100 to that account number

3. Check the result:
```bash
php check_all_transactions.php
```

Expected result:
- `settlement_status` should be `'settled'`
- Transaction should NOT appear in settlement_queue
- Company wallet should be credited immediately
- Company dashboard should show balance increased by ₦99.50

## Step 4: Verify Settlement Queue

```bash
php check_settlement_queue.php
```

Expected result:
- Should show client deposits with `status = 'pending'`
- Should show `scheduled_settlement_date` = tomorrow at 3am Nigerian time
- Should NOT show master account deposits

## Step 5: Check Admin Pending Settlements Page

1. Go to: https://kobopoint.com/secure/pending-settlements
2. Click "Today" filter
3. Should see:
   - All transactions from today (both settled and unsettled)
   - Unsettled transactions highlighted or marked
   - Correct totals and summaries

## Step 6: Test Manual Settlement (Optional)

1. On admin pending settlements page
2. Click "Process Settlements" button
3. Should process only UNSETTLED transactions
4. Check result:
```bash
php check_all_transactions.php
```

Expected result:
- Previously unsettled transactions now marked as `'settled'`
- Company wallet balance increased
- Removed from settlement_queue

## Verification Checklist

After testing, verify:

✅ Client deposits have `settlement_status = 'unsettled'`
✅ Master account deposits have `settlement_status = 'settled'`
✅ Unsettled transactions appear in settlement_queue
✅ Company dashboard shows correct "Pending Settlement" amount
✅ Admin pending settlements page shows correct data
✅ Manual settlement processing works correctly
✅ Automatic settlement will process at 3am tomorrow

## Troubleshooting

### If transaction is marked as 'settled' when it should be 'unsettled':

1. Check if the virtual account has `company_user_id`:
```bash
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); \$va = DB::table('virtual_accounts')->where('palmpay_account_number', 'ACCOUNT_NUMBER')->first(); var_dump(\$va);"
```

2. If `company_user_id` is NULL, it's a master account (instant settlement is correct)
3. If `company_user_id` has a value, it's a client account (should be unsettled)

### If webhook is not being received:

1. Check webhook logs:
```bash
tail -f storage/logs/laravel.log
```

2. Check PalmPay webhooks table:
```bash
php check_palmpay_webhooks.php
```

### If settlement queue is empty:

1. Check if auto settlement is enabled:
```bash
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); \$settings = DB::table('settings')->first(); echo 'Auto Settlement Enabled: ' . (\$settings->auto_settlement_enabled ? 'YES' : 'NO') . PHP_EOL;"
```

2. If disabled, transactions will be settled immediately

## Clean Up After Testing

If you want to reset again:
```bash
php reset_for_fresh_testing.php
```

## Notes

- The reset script is SAFE - it only deletes transaction data, not user accounts or virtual accounts
- Company wallets are reset to ₦199.00 (initial balance)
- You can run the reset script multiple times
- Always test on a non-production environment first if possible
