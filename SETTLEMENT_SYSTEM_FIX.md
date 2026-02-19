# Settlement System Fix - Funds Stuck in Queue

## The Problem

**Symptoms**:
- ✅ Transactions are created successfully
- ✅ Fees are calculated correctly (0.5%)
- ❌ Company wallet balance is ₦0.00 (empty)
- ❌ Pending Settlement shows ₦1,093.50 (funds stuck!)
- ❌ Even with 10-minute delay, funds don't move after hours

## Root Cause

The settlement system has 2 parts:
1. **Webhook** - Queues transactions for settlement ✅ (working)
2. **Cron Job** - Processes the queue and credits wallets ❌ (NOT running!)

**The cron job is not running**, so funds stay in the queue forever.

## Quick Fix - Process Settlements NOW

Run this script to immediately process all pending settlements:

```bash
php PROCESS_PENDING_SETTLEMENTS_NOW.php
```

This will:
- Find all pending settlements
- Credit them to company wallets immediately
- Update transaction balances
- Show you a summary

**Expected Output**:
```
Found 2 pending settlements

Processing settlement #1...
  Company ID: 1
  Amount: ₦995.00
  Old Balance: ₦0.00
  New Balance: ₦995.00
  ✓ SUCCESS

Processing settlement #2...
  Company ID: 1
  Amount: ₦98.50
  Old Balance: ₦995.00
  New Balance: ₦1,093.50
  ✓ SUCCESS

Settlement Summary
Processed: 2
Total Amount Settled: ₦1,093.50

✓ Settlements processed successfully!
```

## Permanent Fix - Set Up Cron Job

The settlement processor needs to run automatically. You have 2 options:

### Option 1: Run Every Minute (Recommended)

Add this to your crontab:

```bash
* * * * * cd /path/to/your/project && php artisan settlements:process >> /dev/null 2>&1
```

The command checks if settlements are due and only processes them if needed.

### Option 2: Run at Specific Time

If you want it to run only at 2am (as configured):

```bash
0 2 * * * cd /path/to/your/project && php artisan settlements:process >> /dev/null 2>&1
```

### How to Add Cron Job

**On cPanel**:
1. Go to cPanel → Cron Jobs
2. Add new cron job
3. Set schedule: Every minute or specific time
4. Command: `cd /home/aboksdfs/public_html && php artisan settlements:process`

**On Linux Server**:
```bash
crontab -e
# Add the line above
```

## Alternative Solution - Disable Settlement Queue

If you want funds to be available IMMEDIATELY (no delay), disable the settlement system:

### Option A: Via Admin Panel
1. Go to: https://app.pointwave.ng/secure/discount/banks
2. Find "Settlement Rules" section
3. Uncheck "Enable Auto Settlement"
4. Save

### Option B: Via Database
```sql
UPDATE settings SET auto_settlement_enabled = 0;
```

**After disabling**, new transactions will credit wallets immediately (no queue).

**Note**: This won't process existing queued settlements. You still need to run the emergency script first.

## How Settlement System Works

### With Settlement Enabled (Current Setup)

```
Payment Received
    ↓
Webhook Creates Transaction
    ↓
Queued for Settlement (funds NOT in wallet yet)
    ↓
Wait for scheduled time (10 minutes in your case)
    ↓
Cron Job Runs ← THIS IS NOT RUNNING!
    ↓
Funds Credited to Wallet
```

### With Settlement Disabled (Immediate)

```
Payment Received
    ↓
Webhook Creates Transaction
    ↓
Funds Credited to Wallet IMMEDIATELY ✓
```

## Check Settlement Status

### Check Pending Settlements
```bash
php artisan tinker
>>> DB::table('settlement_queue')->where('status', 'pending')->get();
```

### Check Settlement Settings
```bash
php artisan tinker
>>> DB::table('settings')->first();
```

### Check Company Wallet
```bash
php artisan tinker
>>> App\Models\CompanyWallet::where('company_id', 1)->first();
```

## Troubleshooting

### Issue: Script says "No pending settlements"
**Solution**: Settlements may have been processed already. Check wallet balance.

### Issue: Script fails with "Wallet not found"
**Solution**: Company wallet doesn't exist. Create it:
```bash
php artisan tinker
>>> App\Models\CompanyWallet::create(['company_id' => 1, 'currency' => 'NGN', 'balance' => 0]);
```

### Issue: Cron job not running
**Solution**: 
1. Check cron is enabled: `crontab -l`
2. Check Laravel scheduler: `php artisan schedule:list`
3. Check logs: `tail -f storage/logs/laravel.log`

## Recommended Setup

For your use case (testing with 10-minute delay):

1. **Keep settlement enabled** (for testing the delay feature)
2. **Set up cron to run every minute**:
   ```bash
   * * * * * cd /home/aboksdfs/public_html && php artisan settlements:process
   ```
3. **Monitor the queue**:
   ```bash
   watch -n 10 'php artisan tinker --execute="DB::table(\"settlement_queue\")->where(\"status\", \"pending\")->count()"'
   ```

This way:
- Transactions queue for 10 minutes ✓
- Cron checks every minute ✓
- After 10 minutes, funds are credited ✓

## Testing the Fix

### Step 1: Process Existing Settlements
```bash
php PROCESS_PENDING_SETTLEMENTS_NOW.php
```

### Step 2: Check Wallet Balance
Go to: https://app.pointwave.ng/dashboard/app
- Should now show ₦1,093.50 (or whatever was pending)

### Step 3: Test New Transaction
1. Send ₦100 to your virtual account
2. Wait 10 minutes
3. If cron is set up, balance should update automatically
4. If not, run the emergency script again

## Summary

**Immediate Fix**: Run `php PROCESS_PENDING_SETTLEMENTS_NOW.php`

**Permanent Fix**: Set up cron job to run `php artisan settlements:process`

**Alternative**: Disable settlement system for immediate crediting

---

**Status**: ❌ Cron job not running
**Impact**: 🔴 HIGH - Funds stuck in queue
**Priority**: 🔴 URGENT - Fix immediately
**Time to Fix**: 5 minutes
