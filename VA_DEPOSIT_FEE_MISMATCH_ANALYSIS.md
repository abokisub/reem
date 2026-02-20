# VA Deposit Fee Configuration Mismatch - Complete Analysis

## THE PROBLEM

You updated the fee to 0.70% (or ₦100 flat) in the admin panel at `/secure/discount/banks` under "Funding with Bank Transfer", but deposits still charge 0.5%.

## ROOT CAUSE IDENTIFIED

### The 0.5% is coming from:
**File:** `app/Services/FeeService.php`  
**Line:** 127  
**Code:**
```php
// Fallback to hardcoded defaults
return [
    'model' => 'hardcoded_fallback',
    'type' => 'PERCENT',
    'value' => 0.5,  // ← THIS IS THE 0.5%
    'cap' => 500
];
```

### Why the fallback is triggered:

1. **Admin Panel** (at `/secure/discount/banks`) updates these columns:
   - `transfer_charge_type` = "FLAT" or "PERCENT"
   - `transfer_charge_value` = 100.00 or 0.70
   - `transfer_charge_cap` = 0.00 or 500

2. **FeeService** (used by WebhookHandler) looks for DIFFERENT columns:
   - `virtual_funding_type` ❌ DOES NOT EXIST
   - `virtual_funding_value` ❌ DOES NOT EXIST
   - `virtual_funding_cap` ❌ DOES NOT EXIST

3. **Result:** FeeService can't find the columns → uses hardcoded 0.5% fallback

## CURRENT DATABASE STATE

```
Settings Table Columns (that exist):
✅ transfer_charge_type: FLAT
✅ transfer_charge_value: 100.00
✅ transfer_charge_cap: 0.00

Settings Table Columns (that DON'T exist):
❌ virtual_funding_type
❌ virtual_funding_value
❌ virtual_funding_cap
```

## THE FLOW

```
VA Deposit Webhook
    ↓
WebhookHandler.php (line 198)
    ↓
FeeService->calculateFee($companyId, $amount, 'va_deposit')
    ↓
FeeService->getFeeConfig() (line 70)
    ↓
Looks for: virtual_funding_type, virtual_funding_value, virtual_funding_cap
    ↓
Columns don't exist
    ↓
Falls back to hardcoded 0.5% (line 127)
```

## THE FIX

**File:** `app/Services/FeeService.php`  
**Line:** ~70

### Change this:
```php
$typeMap = [
    'va_deposit' => 'virtual_funding',  // ← WRONG: columns don't exist
    'transfer' => 'transfer_charge',
    'withdrawal' => 'withdrawal_charge',
    'payout' => 'payout_charge'
];
```

### To this:
```php
$typeMap = [
    'va_deposit' => 'transfer_charge',  // ← CORRECT: use same as admin panel
    'transfer' => 'transfer_charge',
    'withdrawal' => 'withdrawal_charge',
    'payout' => 'payout_charge'
];
```

## IMPACT AFTER FIX

### Current Behavior (Wrong):
- Deposit: ₦100.00
- Fee: ₦0.50 (0.5% fallback)
- Net: ₦99.50

### After Fix (Correct):
If you set FLAT ₦100:
- Deposit: ₦100.00
- Fee: ₦100.00 (FLAT from admin panel)
- Net: ₦0.00

If you set 0.70% PERCENT:
- Deposit: ₦100.00
- Fee: ₦0.70 (0.70% from admin panel)
- Net: ₦99.30

## DEPLOYMENT STEPS

1. **Update FeeService.php** (already done in this session)
2. **Push to GitHub:**
   ```bash
   git add app/Services/FeeService.php
   git commit -m "Fix: VA deposit fee now reads from transfer_charge_* columns (matches admin panel)"
   git push origin main
   ```

3. **Pull on server:**
   ```bash
   cd /home/aboksdfs/app.pointwave.ng
   git pull origin main
   ```

4. **Clear caches:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

5. **Test with new deposit** - the fee should now match what you set in admin panel

## VERIFICATION

After deployment, check the logs for a new deposit:
```bash
tail -f storage/logs/laravel.log | grep "Virtual Account Credited"
```

Look for:
```json
{
  "charge_config": {
    "model": "system_default_flat",  // Should NOT be "hardcoded_fallback"
    "type": "FLAT",
    "value": 100,
    "fee": 100,
    "net": 0
  }
}
```

## FILES MODIFIED

- ✅ `app/Services/FeeService.php` - Line 70 (already updated)

## RELATED FILES

- `app/Services/PalmPay/WebhookHandler.php` - Line 198 (calls FeeService)
- `app/Http/Controllers/API/AppController.php` - Line 161 (getBankCharges - admin panel reads from here)
