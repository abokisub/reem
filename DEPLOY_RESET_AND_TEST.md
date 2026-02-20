# Deploy Reset Script and Start Fresh Testing

## Quick Start Guide

Follow these steps to reset the system and begin comprehensive testing.

---

## Step 1: Pull Latest Changes from GitHub

SSH to your server and pull the reset script:

```bash
ssh aboksdfs@server350.web-hosting.com
cd app.pointwave.ng
git pull origin main
```

You should see:
- ✅ `RESET_FOR_TESTING.php` downloaded
- ✅ `FRESH_TESTING_GUIDE.md` downloaded

---

## Step 2: Run the Reset Script

This will clear all transactions and reset balances to zero:

```bash
cd /home/aboksdfs/app.pointwave.ng
php RESET_FOR_TESTING.php
```

**When prompted:**
- Type `yes` and press Enter to confirm

**What it does:**
- ✅ Deletes all transactions
- ✅ Clears webhook logs (webhook_events and palmpay_webhooks)
- ✅ Clears settlement queue
- ✅ Clears transaction status logs
- ✅ Resets all company balances to ₦0.00
- ✅ Resets all user balances to ₦0.00
- ✅ Keeps last 100 API logs (deletes older ones)

**What it preserves:**
- ✅ Company accounts
- ✅ User accounts
- ✅ Virtual accounts (PalmPay account numbers)
- ✅ API credentials
- ✅ Settings

---

## Step 3: Clear Laravel Caches

After reset, clear all caches:

```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

---

## Step 4: Verify Clean State

### Check Database

```bash
php artisan tinker
```

Then run:
```php
// Check transaction count (should be 0)
DB::table('transactions')->count();

// Check company balance (should be 0)
$company = App\Models\Company::first();
echo $company->balance;

// Check webhook events (should be 0)
DB::table('webhook_events')->count();

// Exit tinker
exit
```

### Check Dashboard

1. Login to company dashboard: https://app.pointwave.ng
2. Go to **Wallet** page
   - Balance should show ₦0.00
   - Account number still displayed
   - Transaction history section hidden
3. Go to **RA Transactions** page
   - Should show "No data found"
   - All 11 columns visible

---

## Step 5: Start Testing

Follow the comprehensive testing guide:

```bash
# View the testing guide
cat FRESH_TESTING_GUIDE.md
```

Or read it on GitHub: [FRESH_TESTING_GUIDE.md](./FRESH_TESTING_GUIDE.md)

### Quick Test Flow:

1. **Test Deposit:**
   - Send ₦1,000 to your PalmPay virtual account
   - Check webhook logs (Admin > Webhook Logs)
   - Verify transaction in RA Transactions
   - Verify balance updated in Wallet

2. **Test Transfer:**
   - Make a ₦500 transfer from dashboard
   - Check transaction in RA Transactions
   - Verify balance deducted (₦1,000 - ₦500 - fee)
   - Check settlement status

3. **Test Responsive Design:**
   - Open on desktop (all columns visible)
   - Open on mobile (horizontal scroll works)
   - Verify no content cut off

4. **Test Admin View:**
   - Login to admin dashboard
   - Check Statement page (all 12 columns)
   - Check Webhook Logs (all 11 columns)
   - Verify can retry failed webhooks

---

## Troubleshooting

### Issue: Reset script fails

**Check:**
```bash
# Verify PHP version
php -v

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Issue: Transactions still showing

**Clear browser cache:**
- Chrome/Edge: Ctrl+Shift+R
- Firefox: Ctrl+Shift+R
- Safari: Cmd+Shift+R

**Clear Laravel caches again:**
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### Issue: Balance not zero

**Manually reset in database:**
```bash
php artisan tinker
>>> DB::table('companies')->update(['balance' => 0]);
>>> DB::table('users')->where('role', '!=', 'admin')->update(['balance' => 0]);
```

---

## What to Test

### Critical Flows:
- ✅ VA Deposit → Webhook → Transaction → Balance Update
- ✅ Transfer → Balance Deduction → Fee Calculation
- ✅ Settlement → Status Update → Queue Processing
- ✅ Webhook Retry → Exponential Backoff → Success

### UI/UX:
- ✅ Wallet page (clean, no transaction history)
- ✅ RA Transactions (all 11 columns visible)
- ✅ Admin Statement (all 12 columns visible)
- ✅ Responsive design (desktop, tablet, mobile)

### Data Integrity:
- ✅ No null values in UI
- ✅ All normalized fields populated
- ✅ Balance consistency
- ✅ Settlement status accuracy

---

## Success Criteria

All tests pass if:

✅ **Deposits work** - Webhooks received, transactions created, balances updated
✅ **Transfers work** - Transactions created, balances deducted, fees calculated
✅ **UI is clean** - Wallet page simplified, all columns visible, responsive
✅ **Admin has full visibility** - Webhook logs complete, can retry failed webhooks
✅ **Data is consistent** - No null values, balances match, settlement tracked

---

## Next Steps After Testing

If all tests pass:
1. ✅ System is production-ready
2. ✅ Monitor for 24 hours
3. ✅ Check settlement processing daily
4. ✅ Review webhook logs regularly

If issues found:
1. Document the issue
2. Check `storage/logs/laravel.log`
3. Review transaction data in database
4. Report back for fixes

---

## Quick Commands Reference

```bash
# Pull latest changes
cd app.pointwave.ng && git pull origin main

# Run reset
php RESET_FOR_TESTING.php

# Clear caches
php artisan cache:clear && php artisan view:clear && php artisan config:clear

# Check database
php artisan tinker
>>> DB::table('transactions')->count();
>>> DB::table('webhook_events')->count();
>>> App\Models\Company::first()->balance;

# View logs
tail -f storage/logs/laravel.log

# Process settlements manually
php artisan settlements:process
```

---

**You're all set! The system is ready for comprehensive fresh testing.** 🚀

Follow the steps above and refer to `FRESH_TESTING_GUIDE.md` for detailed testing procedures.
