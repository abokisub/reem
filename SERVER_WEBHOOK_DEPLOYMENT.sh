#!/bin/bash

echo "=========================================="
echo "Server Webhook Deployment Guide"
echo "=========================================="
echo ""
echo "Run these commands on the server:"
echo ""

cat << 'EOF'
# 1. Pull latest changes
cd /home/aboksdfs/app.pointwave.ng
git pull origin main

# 2. Run migration (webhook_events table already exists, this is safe)
php artisan migrate --force

# 3. Clear Laravel caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# 4. Verify routes are registered
php artisan route:list | grep webhook

# Expected output:
# GET|HEAD  api/admin/webhooks ..................... AdminWebhookController@index
# GET|HEAD  api/admin/webhooks/{webhook} ........... AdminWebhookController@show
# POST      api/admin/webhooks/{webhook}/retry ..... AdminWebhookController@retry
# GET|HEAD  api/webhooks ........................... CompanyWebhookController@index
# GET|HEAD  api/webhooks/{webhook} ................ CompanyWebhookController@show

# 5. Verify cron job is configured
php artisan schedule:list | grep webhook

# Expected output:
# 0 * * * * php artisan webhooks:retry

# 6. Test webhook migration
php artisan migrate:status | grep webhook

# Expected output:
# Yes | 2026_02_22_000000_create_webhook_events_table

echo ""
echo "=========================================="
echo "Backend deployment complete!"
echo "=========================================="
echo ""
echo "Next: Build frontend locally and copy to server"
echo ""
echo "On your LOCAL machine:"
echo "  cd frontend"
echo "  npm run build"
echo "  scp -r build/* aboksdfs@server350.web-hosting.com:/home/aboksdfs/app.pointwave.ng/public/"
echo ""
echo "Then test the webhook pages:"
echo "  Admin: https://app.pointwave.ng/secure/webhooks"
echo "  Company: https://app.pointwave.ng/dashboard/webhook"
echo ""
EOF
