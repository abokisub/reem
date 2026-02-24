# Settlement Cron Job - Root Cause Analysis & Fix

## Problem
Settlements are stuck for 3+ days. This has happened 5 times, affecting multiple companies.

## Root Causes

### 1. Cron Job Not Running
**Symptoms:**
- Settlements scheduled for 3am are not processing
- Money stuck in "Pending Settlement" for days
- No automatic settlement happening

**Possible Causes:**
- Cron job not configured in crontab
- Laravel scheduler not running
- Command failing silently
- Wrong timezone configuration

### 2. Settlement Queue Not Being Created
**Symptoms:**
- Deposits successful but no entry in settlement_queue table
- Webhook processes but doesn't queue settlement

**Possible Causes:**
- WebhookHandler not creating settlement_queue entries
- Transaction type mismatch
- Missing settlement logic in webhook processing

---

## Investigation Steps

### Step 1: Check if Cron is Configured
```bash
crontab -l
```

**Expected:**
```
* * * * * cd /home/aboksdfs/app.pointwave.ng && php artisan schedule:run >> /dev/null 2>&1
```

**If Missing:** Cron is not configured!

### Step 2: Check Laravel Scheduler
```bash
php artisan schedule:list
```

**Expected to see:**
```
settlements:process ......... Every day at 3:00am
```

### Step 3: Check Settlement Queue Table
```sql
SELECT COUNT(*) FROM settlement_queue WHERE status = 'pending';
SELECT * FROM settlement_queue WHERE status = 'pending' ORDER BY scheduled_settlement_date LIMIT 10;
```

### Step 4: Check if Deposits Create Queue Entries
```sql
SELECT 
    t.id,
    t.reference,
    t.amount,
    t.created_at,
    t.settlement_status,
    sq.id as queue_id,
    sq.status as queue_status
FROM transactions t
LEFT JOIN settlement_queue sq ON t.id = sq.transaction_id
WHERE t.transaction_type = 'va_deposit'
AND t.status = 'successful'
AND t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
ORDER BY t.created_at DESC
LIMIT 20;
```

---

## Permanent Fixes

### Fix 1: Configure Cron Job (If Missing)
```bash
# Edit crontab
crontab -e

# Add this line:
* * * * * cd /home/aboksdfs/app.pointwave.ng && php artisan schedule:run >> /dev/null 2>&1
```

### Fix 2: Ensure Scheduler is Registered
Check `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    // Settlement processing at 3am daily
    $schedule->command('settlements:process')
        ->dailyAt('03:00')
        ->timezone('Africa/Lagos');
}
```

### Fix 3: Add Fallback - Hourly Settlement Check
```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Primary: 3am daily
    $schedule->command('settlements:process')
        ->dailyAt('03:00')
        ->timezone('Africa/Lagos');
    
    // Fallback: Every hour check for overdue settlements
    $schedule->command('settlements:process-overdue')
        ->hourly()
        ->timezone('Africa/Lagos');
}
```

### Fix 4: Ensure WebhookHandler Creates Settlement Queue
The WebhookHandler MUST create settlement_queue entries when processing deposits.

---

## Immediate Actions

### 1. Run Diagnostic Script
```bash
php check_stuck_settlements.php
```

### 2. Force Settle Overdue
```bash
php force_settle_overdue.php
```

### 3. Check Cron Configuration
```bash
crontab -l
```

### 4. Manually Run Settlement Command
```bash
php artisan settlements:process
```

### 5. Check Logs
```bash
tail -f storage/logs/laravel.log | grep -i settlement
```

---

## Monitoring & Alerts

### Create Alert System
1. Daily check at 4am: Are there any pending settlements from yesterday?
2. If yes, send alert email to admin
3. Auto-retry failed settlements

### Add Admin Dashboard Widget
- Show pending settlements count
- Show oldest pending settlement age
- Alert if any settlement is > 48 hours old

---

## Testing Checklist

- [ ] Cron job is configured
- [ ] Laravel scheduler runs every minute
- [ ] settlements:process command works manually
- [ ] Deposits create settlement_queue entries
- [ ] Settlement queue entries have correct scheduled_settlement_date
- [ ] ProcessSettlements command processes overdue settlements
- [ ] Company wallet is credited correctly
- [ ] Email notifications are sent
- [ ] Admin can manually trigger settlements from dashboard

---

## Next Steps

1. Check cron configuration on server
2. Verify WebhookHandler creates settlement_queue entries
3. Add hourly fallback command
4. Add monitoring/alerts
5. Test end-to-end flow
