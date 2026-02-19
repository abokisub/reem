#!/bin/bash

echo "=========================================="
echo "FIXING PROVIDER REFERENCE MIGRATION"
echo "=========================================="

cd /home/aboksdfs/app.pointwave.ng

echo ""
echo "Step 1: Checking current migration status..."
php artisan migrate:status | grep "provider_reference"

echo ""
echo "Step 2: Rolling back the failed migration if it's stuck..."
php artisan migrate:rollback --step=1

echo ""
echo "Step 3: Running the fixed migration..."
php artisan migrate --force

echo ""
echo "Step 4: Verifying the columns exist..."
php artisan tinker --execute="
\$columns = \Illuminate\Support\Facades\Schema::getColumnListing('transactions');
echo 'provider_reference exists: ' . (in_array('provider_reference', \$columns) ? 'YES' : 'NO') . PHP_EOL;
echo 'provider exists: ' . (in_array('provider', \$columns) ? 'YES' : 'NO') . PHP_EOL;
echo 'reconciliation_status exists: ' . (in_array('reconciliation_status', \$columns) ? 'YES' : 'NO') . PHP_EOL;
echo 'reconciled_at exists: ' . (in_array('reconciled_at', \$columns) ? 'YES' : 'NO') . PHP_EOL;
"

echo ""
echo "Step 5: Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "=========================================="
echo "MIGRATION FIX COMPLETE!"
echo "=========================================="
echo ""
echo "The transfer system should now work properly."
echo "Test by initiating a new transfer."
