#!/bin/bash

# Script to clear PHP OPcache and Circuit Breaker after deployment
# Run this on the production server: /home/aboksdfs/app.pointwave.ng

echo "=========================================="
echo "Clearing PHP OPcache & Circuit Breaker"
echo "=========================================="
echo ""

# Step 1: Clear Laravel caches (already done, but doing again for safety)
echo "1. Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ Laravel caches cleared"
echo ""

# Step 2: Clear PHP OPcache (this is the critical step)
echo "2. Clearing PHP OPcache..."
php artisan optimize:clear
echo "✅ PHP OPcache cleared via artisan"
echo ""

# Step 3: Restart PHP-FPM to ensure OPcache is fully cleared
echo "3. Restarting PHP-FPM service..."
echo "   (You may need to run this with sudo)"
echo ""
echo "   Run one of these commands based on your PHP version:"
echo "   sudo systemctl restart php-fpm"
echo "   sudo systemctl restart php8.1-fpm"
echo "   sudo systemctl restart php8.2-fpm"
echo "   sudo systemctl restart php8.3-fpm"
echo ""
read -p "   Press Enter after you've restarted PHP-FPM..."
echo ""

# Step 4: Clear PalmPay Circuit Breaker
echo "4. Clearing PalmPay Circuit Breaker..."
php artisan tinker --execute="
    Cache::forget('palmpay_circuit_breaker');
    Cache::forget('palmpay_failure_count');
    Cache::forget('palmpay_circuit_breaker_time');
    echo 'Circuit breaker cleared\n';
"
echo "✅ Circuit breaker cleared"
echo ""

# Step 5: Verify the fix
echo "5. Verifying deployment..."
echo ""
echo "Checking TransferService.php for correct endpoint..."
grep -n "api/v2/payment/merchant/payout/transfer" app/Services/PalmPay/TransferService.php
echo ""

if [ $? -eq 0 ]; then
    echo "✅ Correct endpoint found in code"
else
    echo "❌ WARNING: Correct endpoint NOT found in code!"
    echo "   Please verify git pull was successful"
fi

echo ""
echo "=========================================="
echo "Deployment Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Ask KoboPoint to test the transfer endpoint"
echo "2. Monitor logs: tail -f storage/logs/laravel.log"
echo "3. Look for: 'api/v2/payment/merchant/payout/transfer' in logs"
echo ""
