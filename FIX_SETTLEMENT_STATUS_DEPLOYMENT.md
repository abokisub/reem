# Fix Settlement Status for Client Deposits - Deployment Guide

## Problem Fixed
The webhook handler was marking ALL deposits as `settlement_status = 'settled'` immediately, even for client deposits that should be held for T+1 settlement (24 hours).

## What Changed
Updated `app/Services/PalmPay/WebhookHandler.php` to:
- Check if deposit is from company master account (self-funding) or client account
- Set `settlement_status = 'settled'` for company self-funding (instant credit)
- Set `settlement_status = 'unsettled'` for client deposits (T+1 settlement via queue)

## Deployment Steps

### On Server:

```bash
# 1. Pull latest code
cd /home/aboksdfs/app.pointwave.ng
git pull origin main

# 2. Clear Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# 3. Restart PHP-FPM (if needed)
sudo systemctl restart php-fpm
# OR
sudo systemctl restart php8.2-fpm
```

## Testing

### 1. Check Current Transactions
```bash
php check_all_transactions.php
```

### 2. Make a Test Deposit
- Deposit ₦100 to a CLIENT virtual account (not master account)
- Check the transaction:

```bash
php check_all_transactions.php
```

Expected result:
- `settlement_status` should be `'unsettled'`
- Transaction should appear in `settlement_queue` table
- Company dashboard should show "Pending Settlement: ₦XX.XX"

### 3. Check Settlement Queue
```bash
php check_settlement_queue.php
```

Expected result:
- Should show the new transaction with `status = 'pending'`
- `scheduled_settlement_date` should be tomorrow at 3am Nigerian time

### 4. Check Admin Pending Settlements Page
- Go to: https://kobopoint.com/secure/pending-settlements
- Click "Today" filter
- Should see the unsettled transaction

## Fix Existing Transactions (Optional)

If you want to fix the 3 existing transactions that were incorrectly marked as "settled":

```bash
php fix_existing_settlement_status.php
```

This will:
- Find transactions from today that are marked as "settled" but should be "unsettled"
- Update their `settlement_status` to 'unsettled'
- Add them to the `settlement_queue` table

## Verification

After deployment, verify:
1. ✅ New client deposits have `settlement_status = 'unsettled'`
2. ✅ Company self-funding deposits have `settlement_status = 'settled'`
3. ✅ Unsettled transactions appear in settlement_queue
4. ✅ Company dashboard shows correct "Pending Settlement" amount
5. ✅ Admin pending settlements page shows unsettled transactions

## Rollback (if needed)

If something goes wrong:
```bash
git reset --hard a6dbcca
php artisan cache:clear
```

## Notes

- This fix only affects NEW deposits going forward
- Existing transactions remain as-is unless you run the fix script
- The T+1 settlement cron job will process unsettled transactions at 3am Nigerian time
