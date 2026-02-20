# Diagnose and Fix Wallet/Webhook Empty Pages

## Problem
- `/dashboard/wallet` page not showing transfer/withdrawal transactions
- `/dashboard/webhook` page showing empty

## Diagnostic Steps

### Step 1: Run Diagnostic Script
```bash
php diagnose_wallet_issue.php
```

This will check:
1. All debit transactions in the database
2. Status values being used
3. What the AllHistoryUser query returns
4. Webhook events count

### Step 2: Check Transfer Transactions
```bash
php check_transfer_transactions.php
```

This will show recent transfer transactions and their fields.

## Possible Issues and Fixes

### Issue 1: Status Mismatch
**Problem**: Transactions might be stored with status `'success'` but the query looks for `'successful'`

**Check**: Look at the diagnostic output for status values

**Fix**: If status is `'success'` instead of `'successful'`, update the AllHistoryUser query:

Change line 377 in `app/Http/Controllers/API/Trans.php`:
```php
// FROM:
DB::raw("CASE WHEN status = 'successful' THEN 1 WHEN status = 'failed' THEN 2 ELSE 0 END as plan_status"),

// TO:
DB::raw("CASE WHEN status IN ('successful', 'success') THEN 1 WHEN status = 'failed' THEN 2 ELSE 0 END as plan_status"),
```

### Issue 2: Missing transaction_type
**Problem**: Old transactions don't have `transaction_type` set

**Fix**: Run the migration:
```bash
php artisan migrate
```

This will run the `2026_02_19_192000_add_transaction_type_to_transactions.php` migration that updates existing records.

### Issue 3: Webhook Events Not Created
**Problem**: Transfer transactions don't create webhook events (they're internal operations, not external webhooks)

**Explanation**: The webhook page shows webhook events from external providers (like PalmPay deposit notifications). Transfer/withdrawal transactions are initiated by the company, so they don't generate webhook events. This is expected behavior.

**Solution**: The webhook page should remain empty for transfers. If you want to see transfer logs, use:
- `/dashboard/ra-transactions` - Shows all transactions (deposits + transfers)
- `/dashboard/wallet` - Shows wallet history (should show transfers after fixing status issue)

## Expected Behavior After Fix

### Wallet Page (`/dashboard/wallet`)
Should show:
- **Deposits tab**: All deposit transactions
- **Payments tab**: All transfer/withdrawal transactions
- **All tab**: Both deposits and transfers

### Webhook Page (`/dashboard/webhook`)
- Shows webhook events from external providers (PalmPay deposit notifications)
- Will be empty if no external webhooks have been received
- Transfers don't create webhook events (this is correct)

## Deployment Steps

1. Run diagnostic scripts to identify the issue
2. Apply the fix based on diagnostic results
3. Commit and push changes
4. Pull on live server
5. Test wallet page

## Quick Fix Script

If the issue is status mismatch, here's a quick fix:

```php
<?php
// fix_transaction_status.php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Update any 'success' status to 'successful' for consistency
$updated = DB::table('transactions')
    ->where('status', 'success')
    ->update(['status' => 'successful']);

echo "Updated {$updated} transactions from 'success' to 'successful'\n";
```

Run with: `php fix_transaction_status.php`
