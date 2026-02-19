# Transaction Status ENUM Fix Guide

## Problem Summary

The system is experiencing a critical error where transfers fail with:
```
SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status' at row 1
```

### Root Cause
The `TransferService.php` uses the status value `'debited'` when creating transactions, but this value is not included in the database ENUM definition for the `status` column in the `transactions` table.

### Impact
- Transfers fail immediately when initiated
- Companies are debited twice (initial debit happens, then refund logic tries to run but also fails)
- No actual transfer is sent to the provider
- System shows "refund initiated" but refunds don't complete

## The Fix

### Step 1: Run the Migration
The migration `2026_02_19_120000_expand_transaction_status_enum.php` already exists but hasn't been executed. This migration adds the missing status values including `'debited'`.

```bash
bash FIX_TRANSACTION_STATUS_ENUM.sh
```

Or manually:
```bash
php artisan migrate --path=database/migrations/2026_02_19_120000_expand_transaction_status_enum.php --force
```

### Step 2: Refund Affected Transactions
After fixing the ENUM, process refunds for any transactions that failed due to this error:

```bash
php REFUND_FAILED_TRANSFERS.php
```

This script will:
1. Find all transactions from the last 24 hours that failed with ENUM errors
2. Check if the company was actually debited
3. Process refunds for debited transactions
4. Update transaction statuses appropriately
5. Create proper ledger reversal entries

### Step 3: Verify the Fix
Test a new transfer to ensure it works:

```bash
# Check the migration status
php artisan migrate:status | grep expand_transaction_status

# Should show "Yes" in the Ran? column
```

## Technical Details

### Original ENUM Values
```sql
ENUM('pending', 'processing', 'success', 'failed', 'reversed')
```

### Updated ENUM Values
```sql
ENUM('pending', 'initiated', 'debited', 'processing', 'success', 'successful', 'failed', 'reversed', 'settled')
```

### Transaction Flow
1. **debited** - Wallet debited, transaction recorded
2. **processing** - Sent to provider (PalmPay)
3. **successful** - Provider confirmed success
4. **failed** - Provider rejected or error occurred (triggers refund)

## Prevention

This issue occurred because:
1. The migration was created but not run on production
2. Code was deployed that used the new status values before the migration

### Best Practices
- Always run migrations before deploying code that depends on them
- Use a deployment checklist that includes migration verification
- Consider adding database schema validation tests

## Monitoring

After applying the fix, monitor:
1. Transfer success rate
2. Company wallet balances
3. Ledger balance reconciliation
4. Error logs for any remaining ENUM errors

## Rollback Plan

If issues persist after the fix:

1. Revert the migration:
```bash
php artisan migrate:rollback --step=1
```

2. Update `TransferService.php` to use `'pending'` instead of `'debited'`:
```php
// Line 107 in TransferService.php
'status' => 'pending', // Changed from 'debited'
```

3. Redeploy the backend

## Support

If you encounter issues:
1. Check `storage/logs/laravel.log` for detailed error messages
2. Verify migration status: `php artisan migrate:status`
3. Check company wallet balances match ledger balances
4. Review recent transactions in the database

## Files Modified
- `database/migrations/2026_02_19_120000_expand_transaction_status_enum.php` (already exists)
- `FIX_TRANSACTION_STATUS_ENUM.sh` (new - automated fix script)
- `REFUND_FAILED_TRANSFERS.php` (new - refund processing script)
