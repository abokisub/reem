#!/bin/bash

# ========================================
# Deploy Transfer Endpoint Fix
# ========================================
# This script deploys the fix for the KoboPoint API transfer endpoint
# Issue: POST /api/v1/banks/transfer was using wrong PalmPay endpoint
# Fix: Changed from /transfer/v1/initiate to /api/v2/merchant/payment/payout
#
# Date: 2026-02-21
# ========================================

echo "========================================="
echo "Deploying Transfer Endpoint Fix"
echo "========================================="
echo ""

# 1. Pull latest code
echo "Step 1: Pulling latest code from GitHub..."
git pull origin main
if [ $? -ne 0 ]; then
    echo "❌ Git pull failed!"
    exit 1
fi
echo "✅ Code pulled successfully"
echo ""

# 2. Clear OPcache via web script
echo "Step 2: Clearing PHP OPcache..."
curl -s https://app.pointwave.ng/clear-opcache.php > /dev/null
if [ $? -eq 0 ]; then
    echo "✅ OPcache cleared via web"
else
    echo "⚠️  Web OPcache clear failed, trying PHP-FPM restart..."
    sudo systemctl restart php-fpm 2>/dev/null || sudo service php8.1-fpm restart 2>/dev/null || sudo service php-fpm restart 2>/dev/null
    echo "✅ PHP-FPM restarted"
fi
echo ""

# 3. Clear Laravel caches
echo "Step 3: Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ Laravel caches cleared"
echo ""

# 4. Verify the fix
echo "Step 4: Verifying the fix..."
echo "Checking if MerchantApiController has correct endpoint..."
if grep -q "/api/v2/merchant/payment/payout" app/Http/Controllers/API/V1/MerchantApiController.php; then
    echo "✅ Correct endpoint found in MerchantApiController"
else
    echo "❌ WARNING: Correct endpoint NOT found in MerchantApiController"
    echo "   Please verify the file was updated correctly"
fi
echo ""

echo "========================================="
echo "Deployment Complete!"
echo "========================================="
echo ""
echo "Next Steps:"
echo "1. Test the transfer endpoint with KoboPoint credentials"
echo "2. Monitor logs: tail -f storage/logs/laravel.log"
echo "3. Check balance: php artisan tinker --execute=\"\\\$wallet = DB::table('company_wallets')->where('company_id', 2)->first(); echo 'Balance: ₦' . number_format(\\\$wallet->balance, 2) . PHP_EOL;\""
echo ""
echo "Test Transfer Command:"
echo "curl -X POST https://app.pointwave.ng/api/v1/banks/transfer \\"
echo "  -H 'Authorization: Bearer 7db8dbb3991382487a1fc388a05d96a7139d92ba' \\"
echo "  -H 'X-Business-ID: 3450968aa027e86e3ff5b0169dc17edd7694a846' \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -d '{\"amount\": 100, \"bank_code\": \"090672\", \"account_number\": \"7040540018\", \"account_name\": \"BELLBANK MFB\"}'"
echo ""
