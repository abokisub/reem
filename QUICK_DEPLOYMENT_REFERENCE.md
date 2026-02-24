# Quick Deployment Reference Card

## 🚀 COPY & PASTE COMMANDS

### 1️⃣ SSH into Server
```bash
ssh aboksdfs@app.pointwave.ng
cd /home/aboksdfs/app.pointwave.ng
```

### 2️⃣ Backup Database
```bash
mkdir -p backups
mysqldump -u [DB_USER] -p[DB_PASS] [DB_NAME] > backups/backup_$(date +%Y%m%d_%H%M%S).sql
```

### 3️⃣ Pull Code
```bash
git pull origin main
```

### 4️⃣ Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### 5️⃣ Run Migration
```bash
php artisan migrate --force
```

### 6️⃣ Clear Caches
```bash
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear && php artisan optimize
```

### 7️⃣ Set Permissions
```bash
chmod -R 775 storage bootstrap/cache
chown -R aboksdfs:aboksdfs storage bootstrap/cache
```

### 8️⃣ Verify Cron Job
```bash
crontab -l | grep schedule:run
```
**If empty, add:**
```bash
crontab -e
# Add: * * * * * cd /home/aboksdfs/app.pointwave.ng && php artisan schedule:run >> /dev/null 2>&1
```

### 9️⃣ Run Diagnostic
```bash
php diagnose_and_fix_settlements.php
```

### 🔟 Fix Existing Companies
```bash
php fix_all_activated_companies_master_wallets.php
```

### 1️⃣1️⃣ Check Stuck Settlements
```bash
php check_stuck_settlements.php
```

### 1️⃣2️⃣ Test Settlement Command
```bash
php artisan settlements:process
```

### 1️⃣3️⃣ Restart Queue Workers
```bash
php artisan queue:restart
```

### 1️⃣4️⃣ Clear OPcache
```bash
curl https://app.pointwave.ng/clear-opcache.php
```

### 1️⃣5️⃣ Monitor Logs
```bash
tail -f storage/logs/laravel.log | grep -i settlement
```

---

## ✅ VERIFICATION CHECKLIST

- [ ] Database backed up
- [ ] Code pulled successfully
- [ ] Migration ran (or already ran)
- [ ] Caches cleared
- [ ] Permissions set
- [ ] Cron job configured
- [ ] Scheduled commands listed
- [ ] Diagnostic script ran
- [ ] Existing companies fixed
- [ ] No stuck settlements
- [ ] Settlement command works
- [ ] Queue workers restarted
- [ ] OPcache cleared
- [ ] Logs show no errors
- [ ] Frontend loads correctly
- [ ] Admin panel works
- [ ] Company preview works
- [ ] Company edit works
- [ ] Settlement filters work

---

## 🆘 QUICK TROUBLESHOOTING

### Settlements Stuck?
```bash
php diagnose_and_fix_settlements.php
php force_settle_overdue.php
```

### Cron Not Running?
```bash
sudo systemctl restart cron
crontab -l
```

### Permission Errors?
```bash
chmod -R 775 storage bootstrap/cache
chown -R aboksdfs:aboksdfs storage bootstrap/cache
```

### Frontend Not Updating?
```bash
curl https://app.pointwave.ng/clear-opcache.php
sudo systemctl restart php8.1-fpm
```

### Check Errors?
```bash
tail -100 storage/logs/laravel.log | grep -i error
```

---

## 📊 WHAT WAS FIXED

✅ Master wallet auto-creation on company activation
✅ Settlement system with hourly fallback
✅ Admin "All Pending" filter for settlements
✅ KYC submission saves director_bvn/nin/RC
✅ VirtualAccount model fixed (is_master)
✅ Admin company edit functionality
✅ Frontend/backend data structure match
✅ Comprehensive diagnostic tools

---

## 📞 NEED HELP?

1. Check: `DEPLOYMENT_COMMANDS.md` (detailed guide)
2. Read: `HOW_AUTOMATIC_SETTLEMENT_WORKS.md`
3. Review: `FINAL_DEPLOYMENT_CHECKLIST.md`
4. Check logs: `tail -f storage/logs/laravel.log`

---

**Total Time:** ~10-15 minutes
**Difficulty:** Easy (just copy & paste)
**Risk:** Low (database backed up)
