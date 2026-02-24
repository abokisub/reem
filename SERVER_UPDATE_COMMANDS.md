# Server Update Commands - Run These Now

## 🚀 Quick Commands to Run on Server

```bash
# 1. SSH into server
ssh aboksdfs@app.pointwave.ng
cd /home/aboksdfs/app.pointwave.ng

# 2. Pull the fixes
git pull origin main

# 3. Clear caches
php artisan config:clear && php artisan cache:clear && php artisan route:clear

# 4. Test the diagnostic script (should work now)
php diagnose_and_fix_settlements.php

# 5. Check OPcache secret for today
php -r "echo 'OPcache Secret: ' . md5('pointwave_opcache_clear_' . date('Y-m-d')) . PHP_EOL;"

# 6. Clear OPcache with secret
curl "https://app.pointwave.ng/clear-opcache.php?secret=$(php -r "echo md5('pointwave_opcache_clear_' . date('Y-m-d'));")"
```

---

## 📋 What Was Fixed

### 1. ✅ SQL Syntax Error Fixed
**Issue:** MariaDB doesn't support `INTERVAL 7 DAYS` (with S)
**Fix:** Changed to `INTERVAL 7 DAY` (without S)
**File:** `diagnose_and_fix_settlements.php`

### 2. ✅ Deployment Guides Created
- `DEPLOYMENT_COMMANDS.md` - Complete step-by-step guide
- `QUICK_DEPLOYMENT_REFERENCE.md` - Quick copy-paste commands
- `DEPLOYMENT_RESULTS.md` - What happened during deployment

### 3. ✅ Amtpay KYC Fix Script
**Issue:** Amtpay only has RC number, PalmPay requires BVN/NIN
**Solution:** Created `fix_amtpay_kyc.php` with instructions
**Action Required:** Get director BVN/NIN from Amtpay and update company

---

## 🎯 Current Status

### ✅ WORKING:
- 13 stuck settlements processed successfully
- Kobopoint balance: ₦339.40 → ₦1,631.60
- Settlement system running (every 5 mins + hourly fallback)
- Cron job configured correctly
- All caches cleared
- Permissions set correctly

### ⚠️ NEEDS ATTENTION:
1. **Amtpay Master Wallet** - Needs director BVN/NIN
2. **OPcache** - Use secret to clear (see commands above)

---

## 📝 Next Steps

### Step 1: Pull Latest Fixes
```bash
cd /home/aboksdfs/app.pointwave.ng
git pull origin main
```

**Expected Output:**
```
Updating 44f8f28..c99093f
Fast-forward
 5 files changed, 786 insertions(+), 1 deletion(-)
 create mode 100644 DEPLOYMENT_COMMANDS.md
 create mode 100644 DEPLOYMENT_RESULTS.md
 create mode 100644 QUICK_DEPLOYMENT_REFERENCE.md
 create mode 100644 fix_amtpay_kyc.php
```

### Step 2: Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Step 3: Test Diagnostic Script
```bash
php diagnose_and_fix_settlements.php
```

**Should now work without SQL errors!**

### Step 4: Clear OPcache (Optional)
```bash
# Get today's secret
php -r "echo 'Secret: ' . md5('pointwave_opcache_clear_' . date('Y-m-d')) . PHP_EOL;"

# Use the secret to clear OPcache
curl "https://app.pointwave.ng/clear-opcache.php?secret=YOUR_SECRET_HERE"
```

### Step 5: Fix Amtpay (When Ready)
```bash
# First, read the instructions
php fix_amtpay_kyc.php

# Then, get director BVN/NIN from Amtpay
# Update via admin panel or MySQL:
# UPDATE companies SET director_bvn = '12345678901' WHERE id = 8;

# Finally, create master wallet
php fix_all_activated_companies_master_wallets.php
```

---

## 🔍 Verification

### Check Everything is Working:
```bash
# 1. No stuck settlements
php check_stuck_settlements.php

# 2. Cron is running
crontab -l | grep schedule:run

# 3. Scheduled commands listed
php artisan schedule:list

# 4. No errors in logs
tail -50 storage/logs/laravel.log | grep -i error

# 5. Settlement command works
php artisan settlements:process
```

---

## 📊 Deployment Summary

### Files Changed:
- ✅ `diagnose_and_fix_settlements.php` - Fixed SQL syntax
- ✅ `DEPLOYMENT_COMMANDS.md` - Added (new)
- ✅ `QUICK_DEPLOYMENT_REFERENCE.md` - Added (new)
- ✅ `DEPLOYMENT_RESULTS.md` - Added (new)
- ✅ `fix_amtpay_kyc.php` - Added (new)

### Issues Resolved:
- ✅ 13 stuck settlements processed
- ✅ SQL syntax error fixed
- ✅ Deployment guides created
- ✅ Amtpay fix script created

### Remaining Tasks:
- ⏳ Pull latest fixes on server
- ⏳ Test diagnostic script
- ⏳ Get Amtpay director BVN/NIN
- ⏳ Create Amtpay master wallet

---

## 🆘 If Something Goes Wrong

### Diagnostic Script Still Fails?
```bash
# Check MySQL version
mysql --version

# Test the query manually
mysql -u [user] -p[pass] [database] -e "SELECT NOW(), DATE_SUB(NOW(), INTERVAL 7 DAY);"
```

### OPcache Won't Clear?
```bash
# Check if OPcache is enabled
php -i | grep opcache

# Restart PHP-FPM (if you have access)
# systemctl restart php8.1-fpm
```

### Settlements Still Stuck?
```bash
# Force settle manually
php force_settle_overdue.php

# Check logs
tail -f storage/logs/laravel.log | grep settlement
```

---

## ✅ Success Criteria

- [ ] Latest code pulled
- [ ] Caches cleared
- [ ] Diagnostic script runs without errors
- [ ] No stuck settlements
- [ ] Kobopoint balance correct (₦1,631.60)
- [ ] Settlement system running automatically
- [ ] Admin panel working
- [ ] No errors in logs

---

**All fixes pushed to GitHub!** 🚀

Run the commands above to complete the deployment.
