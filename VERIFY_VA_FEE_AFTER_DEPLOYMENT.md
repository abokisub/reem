# Verify VA Deposit Fee After Deployment

## Step 1: Run Deployment Script

```bash
cd /home/aboksdfs/app.pointwave.ng
bash DEPLOY_ALL_PENDING_FIXES.sh
```

This will:
- Pull latest code from GitHub
- Run migrations (adds `virtual_funding_*` columns)
- Clear all caches
- Show current fee configuration

---

## Step 2: Verify Configuration

After deployment, the script will automatically run `verify_va_deposit_fee_update.php` and show:

### ✅ What You Should See:

```
1️⃣  Virtual Funding Columns (for VA deposits):
  ✅ virtual_funding_type: PERCENTAGE
  ✅ virtual_funding_value: 0.70
  ✅ virtual_funding_cap: 0

2️⃣  Transfer Charge Columns (admin panel updates):
  Type: PERCENTAGE
  Value: 0.70
  Cap: 0

✅ virtual_funding_* matches transfer_charge_*
✅ Admin panel updates are synced correctly
✅ VA deposits will use: PERCENTAGE 0.70%
```

### ❌ If Columns Don't Match:

If `virtual_funding_*` doesn't match `transfer_charge_*`, you need to update the admin panel again:

1. Go to: `/secure/discount/banks`
2. Find: "Funding with Bank Transfer"
3. Set your desired fee (e.g., 0.70%)
4. Click Save

The `AdminController::updateBankCharges()` method will automatically sync both sets of columns.

---

## Step 3: Test with Real VA Deposit

1. Make a test deposit to any virtual account
2. Monitor the logs:
   ```bash
   tail -f storage/logs/laravel.log | grep "Virtual Account Credited"
   ```

3. Check the transaction in admin panel or database:
   ```bash
   php artisan tinker
   ```
   ```php
   $tx = \App\Models\Transaction::latest()->first();
   echo "Amount: " . $tx->amount . "\n";
   echo "Fee: " . $tx->fee . "\n";
   echo "Fee %: " . ($tx->fee / $tx->amount * 100) . "%\n";
   ```

4. Verify the fee percentage matches what you set in admin panel

---

## Step 4: Update Admin Panel Fee (If Needed)

If you want to change the VA deposit fee:

1. Login to admin panel
2. Go to: `/secure/discount/banks`
3. Find section: "Funding with Bank Transfer"
4. Update the fee settings:
   - Type: Percentage or Flat
   - Value: Your desired fee (e.g., 0.70 for 0.70%)
   - Cap: Maximum fee amount (optional, 0 for no cap)
5. Click Save
6. Clear cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

The system will now use your new fee for all future VA deposits!

---

## How It Works Now

### Before Fix:
- Admin panel updated `transfer_charge_*` columns
- FeeService read from `virtual_funding_*` columns (didn't exist)
- FeeService defaulted to 0.5% hardcoded fallback
- **Result:** Fee always 0.5% regardless of admin panel setting

### After Fix:
- Admin panel updates BOTH `transfer_charge_*` AND `virtual_funding_*` columns
- FeeService reads from `virtual_funding_*` columns (now exist and synced)
- No more hardcoded fallback
- **Result:** Fee matches admin panel setting exactly

---

## Troubleshooting

### Issue: Fee still showing 0.5%

**Solution:**
1. Verify migration ran: `php verify_va_deposit_fee_update.php`
2. Update admin panel fee again (to trigger sync)
3. Clear caches: `php artisan cache:clear && php artisan config:clear`
4. Test with new deposit

### Issue: virtual_funding columns don't exist

**Solution:**
```bash
php artisan migrate --force
php artisan cache:clear
```

### Issue: Columns exist but values are NULL

**Solution:**
1. Go to admin panel: `/secure/discount/banks`
2. Update "Funding with Bank Transfer" fee
3. Click Save (this will populate virtual_funding_* columns)
4. Verify: `php verify_va_deposit_fee_update.php`

---

## Summary

✅ **TransferController dependency injection fixed** - No more constructor errors  
✅ **VA deposit fee configuration fixed** - Admin panel now controls VA fees  
✅ **Automatic sync** - Updating admin panel syncs both column sets  
✅ **No hardcoded fallback** - System uses your configured fee  

**You're all set!** 🎉
