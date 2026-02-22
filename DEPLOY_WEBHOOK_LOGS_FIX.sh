#!/bin/bash

echo "========================================="
echo "Deploy Webhook Logs Fix"
echo "========================================="
echo ""

# Pull latest code
echo "📥 Pulling latest code from GitHub..."
git pull origin main

# Clear route cache
echo "🗑️  Clearing route cache..."
php artisan route:clear

# Clear config cache
echo "🗑️  Clearing config cache..."
php artisan config:clear

# Clear view cache
echo "🗑️  Clearing view cache..."
php artisan view:clear

# Clear OPcache
echo "🗑️  Clearing OPcache..."
curl -s "https://app.pointwave.ng/clear-opcache.php?secret=$(grep OPCACHE_SECRET .env | cut -d '=' -f2)"

echo ""
echo "========================================="
echo "✅ Deployment Complete!"
echo "========================================="
echo ""
echo "What was fixed:"
echo "- Admin webhook logs now query the correct table (webhook_logs)"
echo "- Previously queried webhook_events table which was empty"
echo "- Now shows all webhook delivery attempts to companies"
echo ""
echo "Test it:"
echo "1. Go to: https://app.pointwave.ng/secure/webhooks"
echo "2. You should now see webhook logs for KoboPoint"
echo "3. Check the 405 error from their endpoint"
echo ""
