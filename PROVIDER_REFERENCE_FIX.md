# Provider Reference Column Fix

## Problem
Transfers were completing successfully with PalmPay but returning errors to users because the database update was failing:

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'provider_reference' in 'SET'
```

## Root Cause
The `transactions` table was missing the `provider_reference` column that the TransferService was trying to update.

## Solution
Created migration to add the missing columns:
- `provider_reference` - Stores the payment provider's transaction reference
- `provider` - Stores which provider was used (palmpay, etc.)
- `reconciliation_status` - Tracks reconciliation state (pending, reconciled, mismatched)
- `reconciled_at` - Timestamp when reconciliation occurred

## Deployment

Run on your production server:

```bash
./FIX_PROVIDER_REFERENCE.sh
```

Or manually:

```bash
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
```

## What This Fixes
- ✓ Transfers will complete without errors
- ✓ Provider references will be properly stored
- ✓ Reconciliation tracking will work correctly
- ✓ Users will see success messages instead of errors

## Testing
After deployment, test a transfer. It should:
1. Complete successfully
2. Show success message to user
3. Store the PalmPay reference in `provider_reference` column
4. Not show any database errors in logs
