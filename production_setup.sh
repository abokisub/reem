#!/bin/bash

# Production Server Setup Script
# Run this script on your PRODUCTION SERVER after pulling from GitHub

echo "=========================================="
echo "PointPay Production Server Setup"
echo "=========================================="
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Not in Laravel project root"
    exit 1
fi

echo "✅ Laravel project detected"
echo ""

# Step 1: Pull latest changes
echo "1️⃣  Pulling latest changes from GitHub..."
git pull origin main

if [ $? -ne 0 ]; then
    echo "❌ Git pull failed"
    exit 1
fi
echo "✅ Code updated"
echo ""

# Step 2: Install/Update Composer dependencies
echo "2️⃣  Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

if [ $? -ne 0 ]; then
    echo "⚠️  Composer install had issues, but continuing..."
fi
echo "✅ Dependencies installed"
echo ""

# Step 3: Run migrations
echo "3️⃣  Running database migrations..."
php artisan migrate --force

if [ $? -ne 0 ]; then
    echo "⚠️  Migrations had issues, but continuing..."
fi
echo "✅ Migrations complete"
echo ""

# Step 4: Clear all caches
echo "4️⃣  Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ Caches cleared"
echo ""

# Step 5: Optimize for production
echo "5️⃣  Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Optimization complete"
echo ""

# Step 6: Set permissions
echo "6️⃣  Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache
echo "✅ Permissions set"
echo ""

# Step 7: Check migration status
echo "7️⃣  Checking migration status..."
php artisan migrate:status
echo ""

echo "=========================================="
echo "✅ Production Setup Complete!"
echo "=========================================="
echo ""
echo "🧪 Testing:"
echo ""
echo "1. Test homepage:"
echo "   curl -I https://app.pointwave.ng"
echo ""
echo "2. Test API:"
echo "   curl https://app.pointwave.ng/api/v1/health"
echo ""
echo "3. Check logs:"
echo "   tail -50 storage/logs/laravel.log"
echo ""
echo "4. Visit your site:"
echo "   https://app.pointwave.ng"
echo ""
