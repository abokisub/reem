#!/bin/bash

echo "=========================================="
echo "DEPLOYING VIRTUAL ACCOUNT DEPOSIT FIXES"
echo "=========================================="
echo ""

echo "1. Pulling latest changes from GitHub..."
git pull origin main
echo "✓ Code updated"
echo ""

echo "2. Clearing application caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
echo "✓ Caches cleared"
echo ""

echo "3. Optimizing application..."
php artisan config:cache
php artisan route:cache
echo "✓ Application optimized"
echo ""

echo "=========================================="
echo "FIXES APPLIED:"
echo "=========================================="
echo "✓ Created FeeService for proper fee calculation"
echo "✓ Fixed fee calculation (0.50% now calculates correctly)"
echo "✓ Sender information now stored in transaction metadata"
echo "✓ Balance before/after fields now populated"
echo "✓ Improved webhook payload to companies"
echo "✓ Better ledger entry descriptions"
echo ""
echo "=========================================="
echo "DEPLOYMENT COMPLETE!"
echo "=========================================="
echo ""
echo "TESTING INSTRUCTIONS:"
echo "---------------------"
echo "1. Make a test deposit of 100 NGN"
echo "   Expected fee: 0.50 NGN"
echo "   Expected net: 99.50 NGN"
echo ""
echo "2. Check RA Transactions page"
echo "   - Transaction should appear"
echo "   - Sender name should be visible"
echo "   - Sender bank should be visible"
echo "   - Amounts should be correct"
echo ""
echo "3. View transaction details"
echo "   - Balance before/after should be populated"
echo "   - Metadata should include sender info"
echo "   - Status should be 'success'"
echo ""
echo "4. Check logs for any errors:"
echo "   tail -f storage/logs/laravel.log"
echo ""

