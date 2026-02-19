#!/bin/bash

echo "=========================================="
echo "FIX: Add transaction_type Column"
echo "=========================================="
echo ""

echo "This script will:"
echo "1. Add transaction_type column to transactions table"
echo "2. Update existing records with proper transaction types"
echo "3. Update TransferPurchase to set transaction_type on new transfers"
echo ""

read -p "Press ENTER to continue or CTRL+C to cancel..."

echo ""
echo "Step 1: Running migration..."
php artisan migrate --path=database/migrations/2026_02_19_192000_add_transaction_type_to_transactions.php --force

if [ $? -eq 0 ]; then
    echo "✓ Migration completed successfully"
else
    echo "✗ Migration failed"
    exit 1
fi

echo ""
echo "Step 2: Clearing cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo ""
echo "=========================================="
echo "✓ FIX COMPLETED SUCCESSFULLY"
echo "=========================================="
echo ""
echo "The transaction_type column has been added and existing records updated."
echo "New transfers will now properly set the transaction_type field."
echo ""
echo "Please test:"
echo "1. Go to dashboard/wallet"
echo "2. Check that transactions show proper types (Transfer, Settlement Withdrawal, etc.)"
echo "3. Make a new transfer and verify it appears with correct type"
echo ""
