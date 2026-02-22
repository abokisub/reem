#!/bin/bash

# PointWave Registration Fix Deployment Script
# Fixes: "Attempt to read property 'default_limit' on null" error during company registration
# Date: 2026-02-22

echo "=========================================="
echo "PointWave Registration Fix Deployment"
echo "=========================================="
echo ""

# Navigate to project directory
cd /home/aboksdfs/app.pointwave.ng || exit 1

echo "Step 1: Pulling latest changes from GitHub..."
git pull origin main
if [ $? -ne 0 ]; then
    echo "❌ Git pull failed!"
    exit 1
fi
echo "✅ Git pull successful"
echo ""

echo "Step 2: Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ Laravel caches cleared"
echo ""

echo "Step 3: Clearing OPcache..."
curl -s https://app.pointwave.ng/clear-opcache.php > /dev/null
if [ $? -eq 0 ]; then
    echo "✅ OPcache cleared via web"
else
    echo "⚠️  Web OPcache clear failed, trying PHP-FPM restart..."
    sudo systemctl restart php8.1-fpm 2>/dev/null || sudo systemctl restart php-fpm 2>/dev/null
    if [ $? -eq 0 ]; then
        echo "✅ PHP-FPM restarted"
    else
        echo "⚠️  Could not restart PHP-FPM (may need manual restart)"
    fi
fi
echo ""

echo "=========================================="
echo "✅ Deployment Complete!"
echo "=========================================="
echo ""
echo "What was fixed:"
echo "- Added null checks for habukhan_key() in AuthController"
echo "- Added null checks in APP/Auth.php controller"
echo "- Added null checks in AdminController"
echo "- Added null checks in autopilot_request() method"
echo "- Default user_limit now falls back to 999999999 (unlimited) if settings not configured"
echo ""
echo "Test the fix:"
echo "1. Try registering a new company account"
echo "2. Check that registration completes successfully"
echo "3. Verify user_limit is set to 999999999 (unlimited)"
echo ""
