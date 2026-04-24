#!/bin/bash

echo "🚀 Deploying Backend Changes to Server..."
echo ""

# SSH into server and pull changes
ssh aboksdfs@server350.web-hosting.com << 'ENDSSH'
cd app.pointwave.ng

echo "📥 Pulling latest changes from GitHub..."
git pull origin main

echo "🔧 Running composer install..."
composer install --no-dev --optimize-autoloader

echo "🗄️ Running migrations (if any)..."
php artisan migrate --force

echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "⚡ Optimizing..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Deployment Complete!"
echo ""
echo "Changes deployed:"
echo "- Fixed backend errors (undefined properties)"
echo "- Added company virtual account support"
echo "- Added mobile app API endpoints"
echo "- Fixed FCM token error"
echo ""
ENDSSH

echo "🎉 Server deployment finished!"
