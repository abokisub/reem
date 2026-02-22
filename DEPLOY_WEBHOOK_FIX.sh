#!/bin/bash

echo "================================================================================"
echo "DEPLOYING POINTWAVE WEBHOOK SIGNATURE FIX"
echo "================================================================================"
echo ""

echo "Step 1: Pulling latest changes..."
git pull origin main

if [ $? -ne 0 ]; then
    echo "❌ Git pull failed!"
    exit 1
fi

echo "✅ Code updated"
echo ""

echo "Step 2: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo "✅ Caches cleared"
echo ""

echo "Step 3: Checking webhook secret..."
php compare_webhook_secrets.php

echo ""
echo "================================================================================"
echo "DEPLOYMENT COMPLETE"
echo "================================================================================"
echo ""
echo "Next steps:"
echo "1. Verify webhook secret matches on Kobopoint server"
echo "2. Run: php retry_failed_company_webhooks.php"
echo "3. Test with new deposit"
echo "4. Monitor: php check_company_webhook_logs.php"
echo ""
