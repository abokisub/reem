#!/bin/bash

echo "=========================================="
echo "DEPLOYING TO PRODUCTION SERVER"
echo "=========================================="
echo ""

ssh root@167.172.232.130 << 'ENDSSH'
cd /var/www/html

echo "Step 1: Pulling latest changes from GitHub..."
git pull origin main

echo ""
echo "Step 2: Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

echo ""
echo "Step 3: Copying frontend build to public..."
cd frontend
cp -r build/* ../public/

echo ""
echo "✅ Deployment complete!"
echo ""
echo "The Pending Settlements feature is now live at:"
echo "https://kobopoint.com/secure/pending-settlements"
ENDSSH

echo ""
echo "=========================================="
echo "DEPLOYMENT COMPLETE"
echo "=========================================="
