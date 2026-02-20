#!/bin/bash

echo "=========================================="
echo "WEBHOOK LOGS FIX - FRONTEND REBUILD"
echo "=========================================="
echo ""
echo "This script will:"
echo "1. Pull latest backend changes from GitHub"
echo "2. Clear all Laravel caches"
echo "3. Rebuild the React frontend"
echo "4. Test the webhook API endpoint"
echo ""
read -p "Press Enter to continue..."

echo ""
echo "Step 1: Pulling latest changes from GitHub..."
git pull origin main

if [ $? -ne 0 ]; then
    echo "❌ Git pull failed. Please resolve any conflicts and try again."
    exit 1
fi

echo ""
echo "Step 2: Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "Step 3: Testing webhook API endpoint..."
php test_webhook_api.php

if [ $? -ne 0 ]; then
    echo "❌ Backend API test failed. Please check the error above."
    exit 1
fi

echo ""
echo "Step 4: Rebuilding React frontend..."
echo "This may take a few minutes..."
cd frontend

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    echo "Installing npm dependencies..."
    npm install
fi

# Build the frontend
npm run build

if [ $? -ne 0 ]; then
    echo "❌ Frontend build failed. Please check the error above."
    exit 1
fi

cd ..

echo ""
echo "Step 5: Final cache clear..."
php artisan cache:clear

echo ""
echo "=========================================="
echo "✅ DEPLOYMENT COMPLETE!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Open your browser and go to /secure/webhooks"
echo "2. Do a hard refresh (Ctrl+F5 or Cmd+Shift+R)"
echo "3. You should now see the 11 webhook records"
echo ""
echo "If you still see 'Dense pending' or '0-0 of 0':"
echo "1. Clear your browser cache completely"
echo "2. Try in an incognito/private window"
echo "3. Check browser console for any errors (F12)"
echo ""
