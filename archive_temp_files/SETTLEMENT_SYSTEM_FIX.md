# Settlement System Fix

## Issue
Automatic settlements at 3 AM are not working. Admin has to manually settle companies daily.

## Root Cause Analysis
1. ✅ Settlement commands are properly scheduled in Kernel.php
2. ✅ Auto settlement is enabled in settings
3. ❌ Cron job may not be running on cPanel
4. ❌ Laravel scheduler may not be active

## Solution

### 1. **Set Up Cron Job on cPanel**

Add this cron job in your cPanel:

```bash
# Run every minute (Laravel scheduler will handle the actual timing)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

**For your cPanel, the path should be something like:**
```bash
* * * * * cd /home/yourusername/public_html && php artisan schedule:run >> /dev/null 2>&1
```

### 2. **Manual Settlement Command (Backup)**

If cron fails, you can run settlements manually:

```bash
# Process all pending settlements
php artisan settlements:process

# Process overdue settlements
php artisan settlements:process-overdue

# Force settlement for specific date
php artisan gateway:settle
```

### 3. **Test Settlement System**

```bash
# Check if scheduler is working
php artisan schedule:list

# Test settlement command
php artisan settlements:process --dry-run
```

### 4. **Monitor Settlement Logs**

Check these logs:
- `storage/logs/laravel.log` - Settlement processing logs
- cPanel cron job logs - Check if cron is running

## Verification Steps

1. ✅ Cron job added to cPanel
2. ✅ Laravel scheduler running every minute
3. ✅ Settlement commands executing at scheduled times
4. ✅ Companies being settled automatically

## Scheduled Settlement Times

- **settlements:process** - Every 5 minutes (processes due settlements)
- **settlements:process-overdue** - Every hour (backup for missed settlements)
- **gateway:settle** - Daily at 2:00 AM
- **gateway:reconcile** - Daily at 3:00 AM

## Emergency Manual Settlement

If automatic settlement fails, admin can manually process settlements at:
`/secure/pending-settlements`