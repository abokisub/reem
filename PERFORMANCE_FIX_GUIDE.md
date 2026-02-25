# PointWave Performance Fix Guide

## Problem
Pages loading slowly, going blank, requiring multiple refreshes or logout on live server.

## Root Causes
1. **No caching optimization** - Routes, config, views not cached
2. **Missing database indexes** - Slow queries on large tables
3. **File-based cache/sessions** - Slow I/O operations
4. **OPcache not optimized** - PHP code recompiled on every request
5. **Large log files** - Slowing down disk operations
6. **N+1 query problems** - Multiple database queries per page

## Immediate Fixes (Run on Live Server)

### Step 1: Run Diagnostic Script
```bash
cd /home/aboksdfs/app.pointwave.ng
php diagnose_performance.php
```

This will identify the specific bottleneck.

### Step 2: Run Optimization Script
```bash
chmod +x optimize_production.sh
bash optimize_production.sh
```

This will:
- Clear all Laravel caches
- Optimize for production (cache routes, config, views)
- Clear OPcache
- Optimize Composer autoloader
- Set proper permissions

### Step 3: Add Database Indexes
```bash
php artisan migrate --force
```

This runs the new migration that adds performance indexes to:
- transactions (company_id, status, reference, transaction_type)
- virtual_accounts (company_id, status, account_number, customer_email)
- company_wallets (company_id)
- company_webhook_logs (company_id, status)
- settlement_queue (company_id, status, settlement_date)

### Step 4: Clear Large Log Files
```bash
# Check log size
ls -lh storage/logs/laravel.log

# If larger than 100MB, rotate it
mv storage/logs/laravel.log storage/logs/laravel.log.old
touch storage/logs/laravel.log
chmod 664 storage/logs/laravel.log
```

## Long-term Solutions

### 1. Enable Redis Cache (Recommended)
If Redis is available on your server:

```bash
# Install Redis PHP extension
sudo apt-get install php-redis

# Update .env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Restart PHP-FPM
sudo systemctl restart php-fpm
```

### 2. Enable OPcache (If not already enabled)
Add to php.ini:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### 3. Optimize Database Queries
The migration adds indexes, but also consider:
- Using `select()` to limit columns fetched
- Using `with()` for eager loading relationships
- Adding pagination to large result sets

### 4. Monitor Performance
```bash
# Watch slow queries
tail -f storage/logs/laravel.log | grep "slow"

# Monitor server resources
top
htop

# Check MySQL slow query log
sudo tail -f /var/log/mysql/slow-query.log
```

## Frontend Optimization

### 1. Build React for Production
```bash
cd frontend
npm run build
```

### 2. Enable Gzip Compression
Add to .htaccess or nginx config:
```apache
# Apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

# Nginx
gzip on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
```

### 3. Enable Browser Caching
Add to .htaccess:
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

## Verification

After applying fixes, test:

1. **Page Load Speed**: Should load in < 2 seconds
2. **No Blank Pages**: Pages should render immediately
3. **No Multiple Refreshes**: Single refresh should work
4. **Smooth Navigation**: Switching between pages should be instant

## Monitoring Commands

```bash
# Check OPcache status
php -r "var_dump(opcache_get_status());"

# Check cache driver
php artisan tinker
>>> config('cache.default')

# Check database connection pool
mysql -u username -p -e "SHOW PROCESSLIST;"

# Monitor real-time logs
tail -f storage/logs/laravel.log
```

## Emergency Rollback

If issues persist after optimization:

```bash
# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Restart services
sudo systemctl restart php-fpm
sudo systemctl restart nginx  # or apache2
```

## Expected Results

After applying all fixes:
- ✅ Pages load in 1-3 seconds (down from 10-30 seconds)
- ✅ No blank pages on navigation
- ✅ No need for multiple refreshes
- ✅ Smooth user experience
- ✅ Database queries < 100ms
- ✅ OPcache hit rate > 95%

## Support

If issues persist, check:
1. Server resources (CPU, RAM, Disk I/O)
2. Database server performance
3. Network latency
4. Third-party API response times (PalmPay, etc.)
