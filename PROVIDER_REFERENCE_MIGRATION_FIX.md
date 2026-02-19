# Provider Reference Migration Fix

## Problem
The transfer system was failing with this error:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'provider_reference' in 'SET'
```

And the migration was failing with:
```
SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'reconciliation_status'
```

## Root Cause
The migration file had column existence checks INSIDE the Schema::table closure, but Laravel builds the entire SQL statement before executing it, so the checks weren't preventing the duplicate column error.

## Solution
Modified the migration to check for column existence OUTSIDE the Schema::table closures, running each column addition in a separate migration block.

## Files Changed
- `database/migrations/2026_02_19_163500_add_provider_reference_to_transactions.php`

## Deployment Steps

### On Production Server:

```bash
# Navigate to project directory
cd /home/aboksdfs/app.pointwave.ng

# Run the fix script
bash FIX_PROVIDER_REFERENCE_MIGRATION.sh
```

Or manually:

```bash
cd /home/aboksdfs/app.pointwave.ng

# Rollback the failed migration
php artisan migrate:rollback --step=1

# Run migrations again with the fix
php artisan migrate --force

# Clear caches
php artisan config:clear
php artisan cache:clear
```

## Verification

After deployment, verify the columns exist:

```bash
php artisan tinker --execute="
\$columns = \Illuminate\Support\Facades\Schema::getColumnListing('transactions');
echo 'provider_reference: ' . (in_array('provider_reference', \$columns) ? 'YES' : 'NO') . PHP_EOL;
echo 'provider: ' . (in_array('provider', \$columns) ? 'YES' : 'NO') . PHP_EOL;
echo 'reconciliation_status: ' . (in_array('reconciliation_status', \$columns) ? 'YES' : 'NO') . PHP_EOL;
echo 'reconciled_at: ' . (in_array('reconciled_at', \$columns) ? 'YES' : 'NO') . PHP_EOL;
"
```

All should show "YES".

## Testing

After the fix is deployed, test a transfer:
1. Initiate a transfer from the dashboard
2. Check that it completes successfully
3. Verify the transaction shows the correct status
4. Check that no errors appear in `storage/logs/laravel.log`

## Impact
- Fixes the transfer system completely
- Allows proper tracking of provider references
- Enables reconciliation status tracking
- No data loss or downtime required
