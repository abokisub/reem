#!/bin/bash

# Deployment script for settlement_status and transaction visibility fix
# This fixes:
# 1. API transfers now show "Settled" instead of "Unsettled"
# 2. Manual funding/debit transactions now visible in company dashboard

echo "=========================================="
echo "Transaction Visibility & Settlement Fix"
echo "=========================================="
echo ""

# Navigate to application directory
cd /home/aboksdfs/app.pointwave.ng

echo "Step 1: Pulling latest changes from GitHub..."
git pull origin main
if [ $? -ne 0 ]; then
    echo "ERROR: Git pull failed!"
    exit 1
fi
echo "✓ Git pull successful"
echo ""

echo "Step 2: Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✓ Laravel caches cleared"
echo ""

echo "Step 3: Clearing OPcache..."
curl -s https://app.pointwave.ng/clear-opcache.php > /dev/null
echo "✓ OPcache cleared"
echo ""

echo "Step 4: Restarting PHP-FPM..."
sudo systemctl restart php8.2-fpm
if [ $? -ne 0 ]; then
    echo "WARNING: PHP-FPM restart failed, trying alternative method..."
    sudo service php8.2-fpm restart
fi
echo "✓ PHP-FPM restarted"
echo ""

echo "=========================================="
echo "Deployment Complete!"
echo "=========================================="
echo ""
echo "Changes deployed:"
echo "✓ API transfers now show settlement_status = 'settled'"
echo "✓ Manual funding transactions now visible in company dashboard"
echo "✓ Manual debit transactions now visible in company dashboard"
echo "✓ All manual transactions have proper settlement_status"
echo ""
echo "Test by:"
echo "1. Making an API transfer - should show 'Settled' status"
echo "2. Admin creates manual funding - should appear in company dashboard"
echo ""
