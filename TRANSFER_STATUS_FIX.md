# Transfer Status Enum Fix

## Problem

The production server is experiencing transfer failures with the error:
```
SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'status'
```

### Root Cause

The `transactions` table has a `status` column defined as an ENUM with limited values:
```php
enum('status', ['pending', 'processing', 'success', 'failed', 'reversed'])
```

However, the `TransferService` and other parts of the codebase are trying to use additional status values:
- `'debited'` - Used when wallet is debited before provider processing
- `'initiated'` - Used for initial transaction state
- `'successful'` - Alternative to 'success'
- `'settled'` - Used for settlement completion

When the code tries to insert `'debited'` into the database, MySQL rejects it because it's not in the allowed enum values, causing the transfer to fail.

## Solution

Created a migration to expand the `status` enum to include all possible values used in the codebase:

```php
enum('status', [
    'pending',
    'initiated',
    'debited',
    'processing',
    'success',
    'successful',
    'failed',
    'reversed',
    'settled'
])
```

## Files Changed

### New Files
1. `database/migrations/2026_02_19_120000_expand_transaction_status_enum.php` - Migration to fix enum
2. `FIX_TRANSFER_STATUS_ENUM.sh` - Deployment script for production
3. `TRANSFER_STATUS_FIX.md` - This documentation

## Deployment Instructions

### On Production Server

```bash
# SSH to production server
ssh your-user@app.pointwave.ng
cd /home/aboksdfs/app.pointwave.ng

# Run the fix script
bash FIX_TRANSFER_STATUS_ENUM.sh
```

### Manual Steps (if script fails)

```bash
# 1. Pull latest code
git pull origin main

# 2. Run migration
php artisan migrate --force

# 3. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 4. Test a transfer
# Use the dashboard to initiate a test transfer
```

## Verification

After deployment, verify:

1. ✓ No more "Data too long for column 'status'" errors
2. ✓ Transfers complete successfully
3. ✓ Transaction status transitions work correctly:
   - `pending` → `debited` → `processing` → `success`
4. ✓ Check logs for any errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

## Status Flow

### Transfer Lifecycle
```
pending → debited → processing → success → settled
                              ↓
                           failed (with refund)
```

### Status Meanings
- `pending` - Transaction created, awaiting processing
- `initiated` - Transaction initiated by user
- `debited` - Wallet debited, awaiting provider processing
- `processing` - Being processed by payment provider
- `success` / `successful` - Transfer completed successfully
- `failed` - Transfer failed (funds refunded)
- `reversed` - Transaction reversed by provider
- `settled` - Funds settled to final destination

## Impact

- **Before Fix**: Transfers fail immediately with SQL error, funds stuck in limbo
- **After Fix**: Transfers process normally through all status stages

## Testing Checklist

- [ ] Small transfer (₦100) completes successfully
- [ ] Large transfer completes successfully
- [ ] Failed transfer refunds correctly
- [ ] Transaction history shows correct status
- [ ] No SQL errors in logs
- [ ] Beneficiaries are saved correctly

## Rollback Plan

If issues occur after deployment:

```bash
# Rollback the migration
php artisan migrate:rollback --step=1

# This will revert the enum to original values
# Note: Any transactions with new status values will need manual cleanup
```

## Related Issues

This fix resolves:
- Transfer failures with "Data too long" error
- Transactions stuck in invalid states
- Refund processing issues
- Status transition errors

## Notes

- The migration uses raw SQL (`ALTER TABLE`) because Laravel's schema builder doesn't support modifying enums
- All existing transactions will remain unchanged
- The new enum values are backward compatible
- No data loss will occur during migration

