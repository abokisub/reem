#!/bin/bash

echo "================================================================================"
echo "DEPLOY NET_AMOUNT FIX TO PRODUCTION"
echo "================================================================================"
echo ""
echo "This script deploys the fix for webhook net_amount returning null"
echo ""

# Pull latest code
echo "📥 Pulling latest code from GitHub..."
git pull origin main

if [ $? -ne 0 ]; then
    echo "❌ Git pull failed!"
    exit 1
fi

echo "✅ Code updated successfully"
echo ""

# Clear caches
echo "🧹 Clearing application caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo "✅ Caches cleared"
echo ""

# Clear OPcache
echo "🧹 Clearing OPcache..."
curl -s http://localhost/clear-opcache.php > /dev/null 2>&1 || echo "⚠️  OPcache clear endpoint not accessible (this is okay)"

echo ""
echo "================================================================================"
echo "✅ DEPLOYMENT COMPLETE"
echo "================================================================================"
echo ""
echo "What was fixed:"
echo "  - Webhook payload now includes correct net_amount value"
echo "  - Changed from \$transaction->netAmount to \$transaction->net_amount"
echo ""
echo "Next steps:"
echo "  1. Make a test deposit to Kobopoint"
echo "  2. Check webhook payload includes net_amount"
echo "  3. Verify Kobopoint receives correct value"
echo ""
echo "To verify webhook logs:"
echo "  php check_company_webhook_logs.php"
echo ""
echo "================================================================================"
