# How Automatic Settlement Works - Complete Explanation

## Overview
The automatic settlement system processes customer deposits and releases funds to company wallets after a delay period (T+1 by default).

---

## The Complete Flow (Step by Step)

### STEP 1: Customer Makes a Deposit
**What Happens:**
1. Customer transfers money to company's virtual account
2. PalmPay receives the money
3. PalmPay sends webhook to PointWave

**Example:**
- Customer transfers ₦10,000 to virtual account 8012345678
- Transaction happens at: Monday 2:30 PM

---

### STEP 2: Webhook Processing
**File:** `app/Services/PalmPay/WebhookHandler.php`

**What Happens:**
1. PointWave receives webhook from PalmPay
2. Validates signature (security check)
3. Finds the virtual account
4. Checks if it's company self-funding or client payment

**Two Types of Deposits:**

#### Type A: Company Self-Funding (Master Account)
- **When:** Company deposits to their own master virtual account
- **Action:** INSTANT CREDIT - Money goes to wallet immediately
- **Why:** Company is funding themselves, no need to wait

#### Type B: Client Payment (Customer Account)
- **When:** End customer deposits to company's customer virtual account
- **Action:** DELAYED SETTLEMENT - Money held for T+1
- **Why:** Industry standard, allows for fraud detection and chargebacks

**In Our Example:**
- This is a client payment (Type B)
- Money is NOT credited immediately
- Settlement is scheduled

---

### STEP 3: Settlement Queue Creation
**File:** `app/Services/PalmPay/WebhookHandler.php` (line ~320)

**What Happens:**
1. System calculates fees (e.g., 1.5% = ₦150)
2. Net amount = ₦10,000 - ₦150 = ₦9,850
3. Calculates settlement date using T+1 logic
4. Creates entry in `settlement_queue` table

**Settlement Date Calculation:**
```
Transaction Date: Monday 2:30 PM
T+1 = Next business day at 3:00 AM
Settlement Date: Tuesday 3:00 AM

If Monday is a holiday → Wednesday 3:00 AM
If Friday → Monday 3:00 AM (skips weekend)
```

**Database Entry Created:**
```sql
INSERT INTO settlement_queue (
    company_id = 8,
    transaction_id = 12345,
    amount = 9850.00,  -- Net amount after fees
    status = 'pending',
    transaction_date = '2026-02-24 14:30:00',
    scheduled_settlement_date = '2026-02-25 03:00:00'
)
```

**Transaction Status:**
- `settlement_status` = 'unsettled'
- Money is in "Pending Settlement" state

---

### STEP 4: Cron Job Runs (Every 5 Minutes)
**File:** `app/Console/Kernel.php`

**Configuration:**
```php
$schedule->command('settlements:process')->everyFiveMinutes();
```

**What This Means:**
- Every 5 minutes, the system checks for due settlements
- Runs at: 00:00, 00:05, 00:10, 00:15... 02:55, 03:00, 03:05...

**Cron Setup (Server):**
```bash
* * * * * cd /home/aboksdfs/app.pointwave.ng && php artisan schedule:run >> /dev/null 2>&1
```

This line in crontab runs EVERY MINUTE and Laravel decides which commands to run.

---

### STEP 5: Settlement Processing
**File:** `app/Console/Commands/ProcessSettlements.php`

**What Happens at 3:00 AM (or next 5-min check):**

1. **Find Due Settlements:**
```sql
SELECT * FROM settlement_queue 
WHERE status = 'pending' 
AND scheduled_settlement_date <= NOW()
```

2. **For Each Settlement:**
   - Lock company wallet (prevents race conditions)
   - Credit the net amount (₦9,850)
   - Update transaction status to 'settled'
   - Mark settlement_queue as 'completed'
   - Send email notification to company

3. **Example:**
```
Company Wallet Before: ₦339.40
Settlement Amount: ₦9,850.00
Company Wallet After: ₦10,189.40
```

---

### STEP 6: Fallback System (Hourly Check)
**File:** `app/Console/Commands/ProcessOverdueSettlements.php`

**Configuration:**
```php
$schedule->command('settlements:process-overdue')->hourly();
```

**What This Does:**
- Runs every hour as a safety net
- Finds settlements that are overdue (past scheduled date)
- Processes them even if main command missed them
- Prevents settlements from getting stuck

**Why We Need This:**
- If server restarts during settlement time
- If main command fails
- If there's a temporary database issue
- Ensures no settlement is stuck for more than 1 hour

---

## Configuration Settings

### Database Table: `settings`

```sql
auto_settlement_enabled = 1          -- Must be TRUE
settlement_delay_hours = 24          -- T+1 (24 hours)
settlement_skip_weekends = 1         -- Skip Sat/Sun
settlement_skip_holidays = 1         -- Skip public holidays
settlement_time = '03:00:00'         -- 3 AM daily
```

### How to Check Settings:
```bash
php diagnose_and_fix_settlements.php
```

This script will:
- Check if auto_settlement is enabled
- Enable it if disabled
- Show current configuration
- Find and fix stuck settlements

---

## Why Settlements Get Stuck (5 Common Reasons)

### 1. Auto Settlement Disabled
**Problem:** `auto_settlement_enabled = 0` in settings
**Solution:** Run `diagnose_and_fix_settlements.php` to enable

### 2. Cron Job Not Running
**Problem:** Server crontab not configured
**Check:** `crontab -l`
**Solution:** Add cron job:
```bash
* * * * * cd /home/aboksdfs/app.pointwave.ng && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Settlement Queue Not Created
**Problem:** Webhook didn't create settlement_queue entry
**Reason:** Usually because auto_settlement was disabled when deposit happened
**Solution:** Run `diagnose_and_fix_settlements.php` to create missing entries

### 4. Command Failing Silently
**Problem:** ProcessSettlements command has an error
**Check:** `php artisan settlements:process` manually
**Solution:** Check logs: `tail -f storage/logs/laravel.log`

### 5. Database Lock Issues
**Problem:** Wallet locked by another transaction
**Solution:** Fallback command will retry hourly

---

## How to Verify It's Working

### Test 1: Check Cron is Running
```bash
# Check if cron is configured
crontab -l

# Expected output:
* * * * * cd /home/aboksdfs/app.pointwave.ng && php artisan schedule:run >> /dev/null 2>&1
```

### Test 2: Check Scheduled Commands
```bash
php artisan schedule:list

# Expected output:
settlements:process ............. Every 5 minutes
settlements:process-overdue ..... Every hour
```

### Test 3: Check Pending Settlements
```bash
php check_stuck_settlements.php

# Should show:
# - Total pending settlements
# - Overdue settlements (if any)
# - Settlement dates
```

### Test 4: Manual Run
```bash
php artisan settlements:process

# Should process any due settlements
# Check output for success/failure
```

### Test 5: Check Logs
```bash
tail -f storage/logs/laravel.log | grep -i settlement

# Should see:
# [timestamp] Settlement processing...
# [timestamp] Settled: 9850 NGN for company 8
```

---

## Timeline Example (Real World)

**Monday 2:30 PM** - Customer deposits ₦10,000
- Webhook received
- Fee calculated: ₦150
- Net amount: ₦9,850
- Settlement scheduled for: Tuesday 3:00 AM
- Status: "Pending Settlement"

**Monday 2:35 PM** - Cron runs (5-min check)
- Checks settlement_queue
- Finds settlement scheduled for Tuesday 3:00 AM
- Too early, skips it

**Monday 11:00 PM** - Cron runs
- Still too early, skips

**Tuesday 3:00 AM** - Cron runs
- Finds settlement is now due!
- Locks company wallet
- Credits ₦9,850
- Updates status to "settled"
- Sends email to company
- Status: "Settled" ✅

**Tuesday 3:05 AM** - Next cron run
- No pending settlements found
- Nothing to do

---

## What You See in Dashboard

### Company Dashboard:
- **Balance:** ₦339.40 (before settlement)
- **Pending Settlement:** ₦9,850.00 (waiting for T+1)

### After Settlement (Tuesday 3:00 AM):
- **Balance:** ₦10,189.40 (updated!)
- **Pending Settlement:** ₦0.00 (cleared!)

---

## Admin Manual Settlement

If automatic settlement fails, admin can manually process:

1. Go to: `/secure/pending-settlements`
2. Select filter:
   - **Yesterday:** Process yesterday's deposits
   - **Today:** Process today's deposits
   - **All Pending (24h+):** Process ALL overdue settlements
3. Click "Process Settlements"
4. System processes immediately

---

## Current Status & Fixes Applied

### ✅ What's Working:
1. Settlement command runs every 5 minutes
2. Fallback command runs hourly
3. WebhookHandler creates settlement_queue entries
4. T+1 calculation works correctly
5. Weekend/holiday skipping works

### ✅ What We Fixed:
1. Added "All Pending" filter for admin
2. Created fallback command (hourly check)
3. Created diagnostic script
4. Created force-settle script
5. Updated frontend with 3 filter options

### ⚠️ What to Check:
1. **Cron job configured?** Run: `crontab -l`
2. **Auto settlement enabled?** Run: `php diagnose_and_fix_settlements.php`
3. **Any stuck settlements?** Run: `php check_stuck_settlements.php`

---

## Quick Fix Commands

```bash
# 1. Diagnose and fix everything
php diagnose_and_fix_settlements.php

# 2. Check for stuck settlements
php check_stuck_settlements.php

# 3. Force settle overdue
php force_settle_overdue.php

# 4. Manual settlement run
php artisan settlements:process

# 5. Check logs
tail -f storage/logs/laravel.log | grep settlement
```

---

## Summary

**Automatic Settlement = 3 Components:**

1. **Webhook Handler** - Creates settlement_queue entries (T+1 calculation)
2. **Cron Job** - Runs every minute, triggers Laravel scheduler
3. **Settlement Commands** - Process due settlements every 5 minutes + hourly fallback

**The system is AUTOMATIC** - no manual intervention needed if:
- ✅ Cron job is configured
- ✅ Auto settlement is enabled
- ✅ Commands are running without errors

**If settlements get stuck:**
- Use admin panel: `/secure/pending-settlements`
- Or run: `php diagnose_and_fix_settlements.php`
