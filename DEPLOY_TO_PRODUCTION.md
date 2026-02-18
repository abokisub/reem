# Deploy Backend to Production - Step by Step Guide

## Current Status
- ✅ Domain configured: app.pointwave.ng
- ✅ Document root set to: /public
- ✅ Local development complete
- 🔄 Ready to deploy backend

## Step 1: Commit and Push Backend Changes

### 1.1 Add All Changes
```bash
# Add all modified and new files
git add .

# Check what will be committed
git status
```

### 1.2 Commit Changes
```bash
git commit -m "Production deployment: Settlement rules, logs pages, PalmPay integration docs"
```

### 1.3 Push to GitHub
```bash
git push origin main
```

## Step 2: Deploy to Live Server

### Option A: Using cPanel Git Deployment (Recommended)

1. **Login to cPanel**
2. **Go to:** Git Version Control
3. **Click:** "Manage" on your repository
4. **Click:** "Pull or Deploy" → "Update from Remote"
5. **Done!** Files will be updated

### Option B: Using SSH/FTP

#### Via SSH:
```bash
# SSH into your server
ssh username@app.pointwave.ng

# Navigate to your project
cd /home/abokisub/app.pointwave.ng/public

# Pull latest changes
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 755 storage bootstrap/cache
```

#### Via FTP:
1. Download your local project as ZIP
2. Upload to server via FTP
3. Extract on server
4. Run commands above via cPanel Terminal

## Step 3: Configure Production Environment

### 3.1 Update .env File on Server

**IMPORTANT:** Your production `.env` should have:

```env
APP_NAME="PointPay"
APP_ENV=production
APP_KEY=base64:YOUR_PRODUCTION_KEY
APP_DEBUG=false
APP_URL=https://app.pointwave.ng

# Database (Production)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=pointpay
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# PalmPay (Production)
PALMPAY_BASE_URL=https://api.palmpay.com
PALMPAY_MERCHANT_ID=your_merchant_id
PALMPAY_API_KEY=your_api_key
PALMPAY_SECRET_KEY=your_secret_key
PALMPAY_MASTER_WALLET=6644694207
PALMPAY_SETTLEMENT_ACCOUNT=7040540018

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@pointwave.ng
MAIL_FROM_NAME="${APP_NAME}"

# Session & Cache
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=database

# Security
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=app.pointwave.ng
```

### 3.2 Generate Application Key (if needed)
```bash
php artisan key:generate
```

## Step 4: Run Database Migrations

```bash
# Run migrations (safe - won't lose data)
php artisan migrate --force

# Verify migrations
php artisan migrate:status
```

**Migrations to be run:**
- ✅ Settlement rules (adds columns to settings table)
- ✅ Audit logs table
- ✅ API request logs columns
- ✅ Support tickets table
- ✅ KYC upgrade fields

## Step 5: Set Up Cron Jobs

### 5.1 Via cPanel Cron Jobs

1. **Go to:** cPanel → Cron Jobs
2. **Add New Cron Job:**

**Every Minute (Laravel Scheduler):**
```
* * * * * cd /home/abokisub/app.pointwave.ng && php artisan schedule:run >> /dev/null 2>&1
```

This will run:
- Settlement processor (hourly at 2:00 AM)
- PalmPay transaction sync (every 5 minutes)
- Other scheduled tasks

### 5.2 Verify Scheduler
```bash
php artisan schedule:list
```

## Step 6: Set Correct Permissions

```bash
# Storage and cache directories
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Make sure web server can write
chown -R www-data:www-data storage bootstrap/cache
# OR (depending on your server)
chown -R nobody:nobody storage bootstrap/cache
```

## Step 7: Configure Web Server

### For Apache (.htaccess already configured)
Your `.htaccess` in `public/` folder is already set up correctly.

### For Nginx (if applicable)
Add to your nginx config:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
}
```

## Step 8: Test Production Deployment

### 8.1 Test Homepage
```bash
curl -I https://app.pointwave.ng
```
Expected: HTTP 200 OK

### 8.2 Test API
```bash
curl https://app.pointwave.ng/api/v1/health
```

### 8.3 Test Documentation
Visit: https://app.pointwave.ng/docs

### 8.4 Test Login
Visit: https://app.pointwave.ng/auth/login

## Step 9: Verify New Features

### Settlement Rules
1. Login as admin
2. Go to: /secure/discount/banks
3. Verify settlement configuration section shows

### Logs Pages
1. Login as company user
2. Check sidebar under "MERCHANT"
3. Verify these pages work:
   - Webhook Logs
   - API Request Logs
   - Audit Logs

### Updated Documentation
Visit: https://app.pointwave.ng/docs
- Verify PalmPay branding
- Check settlement information
- Verify KYC tier details

## Step 10: Monitor Logs

### Check Laravel Logs
```bash
tail -f storage/logs/laravel.log
```

### Check Web Server Logs
```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log
```

## Troubleshooting

### Issue: 500 Internal Server Error
**Solution:**
```bash
# Check permissions
chmod -R 775 storage bootstrap/cache

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check logs
tail -50 storage/logs/laravel.log
```

### Issue: Database Connection Error
**Solution:**
- Verify `.env` database credentials
- Test connection: `php artisan tinker` then `DB::connection()->getPdo();`

### Issue: Migrations Fail
**Solution:**
```bash
# Check migration status
php artisan migrate:status

# Rollback last batch if needed
php artisan migrate:rollback

# Re-run migrations
php artisan migrate --force
```

### Issue: Assets Not Loading
**Solution:**
```bash
# Clear view cache
php artisan view:clear

# Regenerate cached views
php artisan view:cache
```

## Post-Deployment Checklist

- [ ] Backend deployed to production
- [ ] `.env` configured with production values
- [ ] Database migrations run successfully
- [ ] Cron jobs configured
- [ ] Permissions set correctly
- [ ] Homepage loads (https://app.pointwave.ng)
- [ ] Login works
- [ ] API endpoints respond
- [ ] Documentation accessible
- [ ] Settlement rules visible in admin
- [ ] Logs pages accessible
- [ ] No errors in Laravel logs
- [ ] SSL certificate active (https)

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] Strong database password
- [ ] `.env` file not accessible via web
- [ ] `storage/` not accessible via web
- [ ] File permissions correct (755/775)
- [ ] SSL certificate installed
- [ ] Firewall configured
- [ ] Regular backups enabled

## Maintenance Commands

### Clear All Caches
```bash
php artisan optimize:clear
```

### Optimize for Production
```bash
php artisan optimize
```

### View Queue Jobs
```bash
php artisan queue:work --once
```

### Check Scheduled Tasks
```bash
php artisan schedule:list
```

## Backup Before Deployment

**IMPORTANT:** Always backup before deploying:

```bash
# Backup database
mysqldump -u username -p pointpay > backup_$(date +%Y%m%d).sql

# Backup files
tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/project
```

## Rollback Plan

If something goes wrong:

```bash
# Rollback migrations
php artisan migrate:rollback

# Restore database
mysql -u username -p pointpay < backup_20260218.sql

# Revert git changes
git reset --hard HEAD~1
git push -f origin main
```

## Support

If you encounter issues:
1. Check `storage/logs/laravel.log`
2. Check web server error logs
3. Verify `.env` configuration
4. Test database connection
5. Check file permissions

## Next Steps After Deployment

1. Monitor logs for 24 hours
2. Test all critical features
3. Verify PalmPay webhooks working
4. Check settlement processor runs at 2 AM
5. Test API endpoints with real data
6. Verify email notifications work
7. Test payment flows end-to-end

## Status: Ready to Deploy! 🚀

Follow the steps above to deploy your backend to production.
