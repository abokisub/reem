#!/bin/bash

echo "=========================================="
echo "PointWave Production Optimization Script"
echo "=========================================="
echo ""

# 1. Clear all caches
echo "1. Clearing all Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan event:clear

# 2. Optimize for production
echo ""
echo "2. Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 3. Clear opcache
echo ""
echo "3. Clearing OPcache..."
php public/clear-opcache.php

# 4. Optimize composer autoloader
echo ""
echo "4. Optimizing Composer autoloader..."
composer dump-autoload --optimize --no-dev

# 5. Set proper permissions
echo ""
echo "5. Setting proper permissions..."
chmod -R 775 storage bootstrap/cache
chown -R aboksdfs:aboksdfs storage bootstrap/cache

# 6. Clear session files (optional - will log users out)
# echo ""
# echo "6. Clearing old session files..."
# find storage/framework/sessions -type f -mtime +7 -delete

echo ""
echo "=========================================="
echo "Optimization Complete!"
echo "=========================================="
echo ""
echo "Additional recommendations:"
echo "1. Check database query performance"
echo "2. Enable Redis cache if available"
echo "3. Monitor server resources (CPU, RAM)"
echo "4. Check error logs: tail -f storage/logs/laravel.log"
