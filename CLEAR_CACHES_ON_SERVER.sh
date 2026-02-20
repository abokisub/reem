#!/bin/bash

# Clear Laravel Caches on Server
# Run this after uploading new frontend build files

echo "=========================================="
echo "Clearing Laravel Caches"
echo "=========================================="
echo ""

cd /home/aboksdfs/app.pointwave.ng

echo "Clearing application cache..."
php artisan cache:clear

echo "Clearing view cache..."
php artisan view:clear

echo "Clearing config cache..."
php artisan config:clear

echo "Clearing route cache..."
php artisan route:clear

echo ""
echo "=========================================="
echo "✅ All caches cleared!"
echo "=========================================="
echo ""
echo "The Wallet page should now show:"
echo "  ✅ Balance card with withdraw button"
echo "  ✅ Account details (PalmPay account number)"
echo "  ✅ Bank details"
echo "  ❌ Transaction history (HIDDEN)"
echo ""
echo "Test the page:"
echo "https://app.pointwave.ng/dashboard/wallet"
echo ""
