# Deployment Quick Start Guide

## 🚀 Deploy in 3 Steps

### Step 1: Push Backend to GitHub (Local Machine)

```bash
# Run the deployment script
./deploy.sh
```

Or manually:
```bash
git add .
git commit -m "Production deployment"
git push origin main
```

---

### Step 2: Update Production Server

**Option A: Via cPanel (Easiest)**

1. Login to cPanel
2. Go to: **Git Version Control**
3. Click: **"Manage"** on your repository
4. Click: **"Pull or Deploy"** → **"Update from Remote"**
5. Done! ✅

**Option B: Via SSH**

```bash
# SSH into server
ssh username@app.pointwave.ng

# Navigate to project
cd /home/abokisub/app.pointwave.ng

# Run setup script
./production_setup.sh
```

Or manually:
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
chmod -R 775 storage bootstrap/cache
```

---

### Step 3: Verify Deployment

Visit these URLs:

1. **Homepage:** https://app.pointwave.ng
2. **Login:** https://app.pointwave.ng/auth/login
3. **API Docs:** https://app.pointwave.ng/docs
4. **API Health:** https://app.pointwave.ng/api/v1/health

---

## ✅ What Was Deployed

### New Features:
1. ✅ Settlement rules system (T+1 schedule)
2. ✅ Webhook logs page
3. ✅ API request logs page
4. ✅ Audit logs page
5. ✅ Updated API documentation (PalmPay branding)
6. ✅ Fixed sidebar icons

### Database Changes:
- Settlement rules columns in settings table
- Audit logs table
- API request logs columns
- Support tickets table
- KYC upgrade fields

---

## 🔧 Production Configuration

### Required .env Variables

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.pointwave.ng

DB_DATABASE=pointpay
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

PALMPAY_BASE_URL=https://api.palmpay.com
PALMPAY_MERCHANT_ID=your_merchant_id
PALMPAY_API_KEY=your_api_key
PALMPAY_SECRET_KEY=your_secret_key
PALMPAY_MASTER_WALLET=6644694207
PALMPAY_SETTLEMENT_ACCOUNT=7040540018
```

---

## 📅 Set Up Cron Job

**Via cPanel → Cron Jobs:**

Add this cron job:
```
* * * * * cd /home/abokisub/app.pointwave.ng && php artisan schedule:run >> /dev/null 2>&1
```

This runs:
- Settlement processor (daily at 2:00 AM)
- PalmPay transaction sync (every 5 minutes)

---

## 🧪 Test Checklist

After deployment, test:

- [ ] Homepage loads
- [ ] Login works
- [ ] Admin dashboard accessible
- [ ] Company dashboard accessible
- [ ] Settlement rules visible (/secure/discount/banks)
- [ ] Webhook logs page works (/dashboard/webhook-logs)
- [ ] API logs page works (/dashboard/api-logs)
- [ ] Audit logs page works (/dashboard/audit-logs)
- [ ] API documentation shows PalmPay branding
- [ ] Sidebar icons display correctly
- [ ] No errors in logs

---

## 🆘 Troubleshooting

### 500 Error
```bash
chmod -R 775 storage bootstrap/cache
php artisan optimize:clear
tail -50 storage/logs/laravel.log
```

### Database Error
```bash
# Check .env database credentials
php artisan tinker
>>> DB::connection()->getPdo();
```

### Migrations Fail
```bash
php artisan migrate:status
php artisan migrate --force
```

### Assets Not Loading
```bash
php artisan view:clear
php artisan view:cache
```

---

## 📞 Support

- **Logs:** `storage/logs/laravel.log`
- **Documentation:** `DEPLOY_TO_PRODUCTION.md`
- **Server:** Check cPanel error logs

---

## 🎯 Quick Commands

```bash
# Clear everything
php artisan optimize:clear

# Optimize everything
php artisan optimize

# Check migrations
php artisan migrate:status

# View scheduled tasks
php artisan schedule:list

# Check queue
php artisan queue:work --once
```

---

## Status: Ready to Deploy! 🚀

Run `./deploy.sh` to start deployment.
