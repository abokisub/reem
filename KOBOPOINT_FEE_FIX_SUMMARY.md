# KoboPoint Transfer Fee Fix - Summary

## Issue Reported
KoboPoint was seeing ₦15 transfer fee via API instead of the configured ₦30 fee that works correctly in the dashboard.

## Root Cause
The API endpoint `POST /api/v1/banks/transfer` in `MerchantApiController.php` was using the wrong fee settings:
- ❌ Was using: `payout_palmpay_charge_*` (₦15 - for settlement withdrawals)
- ✅ Should use: `payout_bank_charge_*` (₦30 - for external transfers)

## The Fix

### File Changed: `app/Http/Controllers/API/V1/MerchantApiController.php`

**Method:** `initiateTransfer()`

**Changed from:**
```php
// Get payout charges from settings
$settings = DB::table('settings')->first();
$chargeType = $settings->payout_palmpay_charge_type ?? 'FLAT';
$chargeValue = $settings->payout_palmpay_charge_value ?? 15;
```

**Changed to:**
```php
// Get External Transfer (Other Banks) charges - same as dashboard
// This uses payout_bank_* settings (configured in /secure/discount/banks)
$settings = DB::table('settings')->first();
$chargeType = $settings->payout_bank_charge_type ?? 'FLAT';
$chargeValue = $settings->payout_bank_charge_value ?? 30;
```

## Settings Verification

Current database settings:
```
External Transfer (Other Banks):
  Type: FLAT
  Value: ₦30.00
  Cap: ₦0.00

Settlement Withdrawal (PalmPay):
  Type: FLAT
  Value: ₦15.00
  Cap: ₦0.00
```

## Fee Structure Clarification

### For KoboPoint (API Users)
- **Transfer Amount**: ₦100
- **PointWave Fee**: ₦30
- **Total Deducted**: ₦130

### For PointWave (Provider Costs)
- **PalmPay charges us**: ₦25 per transfer
- **We charge KoboPoint**: ₦30
- **Our profit**: ₦5 per transfer

## Dashboard vs API Consistency

Both now use the same fee calculation:

### Dashboard (`TransferPurchase.php`)
```php
// External Transfer (Other Banks)
$type = $settings->payout_bank_charge_type ?? 'FLAT';
$value = $settings->payout_bank_charge_value ?? 0;
```

### API (`MerchantApiController.php`)
```php
// External Transfer (Other Banks) charges - same as dashboard
$chargeType = $settings->payout_bank_charge_type ?? 'FLAT';
$chargeValue = $settings->payout_bank_charge_value ?? 30;
```

## Testing

### Before Fix
```bash
curl -X POST https://app.pointwave.ng/api/v1/banks/transfer \
  -H "Authorization: Bearer SECRET" \
  -H "x-business-id: BUSINESS_ID" \
  -H "x-api-key: API_KEY" \
  -d '{
    "amount": 100,
    "bank_code": "000004",
    "account_number": "7040540018",
    "account_name": "Test Account"
  }'

# Response showed: fee: 15, total_deducted: 115
```

### After Fix
```bash
# Same request will now show: fee: 30, total_deducted: 130
```

## Deployment Steps

1. **Commit changes:**
   ```bash
   git add app/Http/Controllers/API/V1/MerchantApiController.php
   git commit -m "Fix: Use correct payout_bank_charge settings for API transfers (₦30)"
   git push
   ```

2. **Deploy to production:**
   ```bash
   ssh server
   cd /home/aboksdfs/app.pointwave.ng
   git pull
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Clear OPcache:**
   ```bash
   # Visit: https://app.pointwave.ng/clear-opcache.php?secret=YOUR_SECRET
   # Or restart PHP-FPM if needed
   ```

4. **Verify:**
   ```bash
   # Test the API endpoint
   # Should now show ₦30 fee instead of ₦15
   ```

## Impact

- ✅ API now charges correct ₦30 fee (matches dashboard)
- ✅ KoboPoint balance check will now require ₦130 for ₦100 transfer
- ✅ Consistent fee calculation across all platforms
- ✅ No changes needed to KoboPoint's integration code

## Files Modified

1. `app/Http/Controllers/API/V1/MerchantApiController.php` - Fixed fee calculation
2. `MESSAGE_TO_KOBOPOINT.md` - Updated communication
3. `KOBOPOINT_FEE_FIX_SUMMARY.md` - This file

---

**Date:** February 21, 2026  
**Status:** Ready for deployment  
**Priority:** High (affects billing)
