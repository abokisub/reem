# KoboPoint Transfer Endpoint Fix - FINAL

## Issue Summary

The KoboPoint API transfer endpoint (POST /api/v1/banks/transfer) was failing with error:
```
"invalid url router" (Code: OPEN_GW_000022)
```

## Root Cause

The `initiateTransfer()` method in `app/Http/Controllers/API/V1/MerchantApiController.php` was using the **WRONG PalmPay endpoint**:
- ❌ **Wrong**: `/transfer/v1/initiate` (old endpoint)
- ✅ **Correct**: `/api/v2/merchant/payment/payout` (official endpoint)

This was the SAME error that was fixed in TASK 3 for the dashboard transfers, but the API endpoint method was never updated.

## What Was Fixed

### File: `app/Http/Controllers/API/V1/MerchantApiController.php`

**Changed the PalmPay API call from:**
```php
$response = $this->palmPay->post('/transfer/v1/initiate', [
    'amount' => $request->amount,
    'bankCode' => $request->bank_code,
    'accountNumber' => $request->account_number,
    'accountName' => $request->account_name,
    'reference' => $internalRef,
]);
```

**To the correct format:**
```php
$response = $this->palmPay->post('/api/v2/merchant/payment/payout', [
    'orderId' => $internalRef,
    'payeeBankCode' => $request->bank_code,
    'payeeBankAccNo' => $request->account_number,
    'payeeName' => $request->account_name,
    'amount' => (int) ($request->amount * 100), // Convert to kobo
    'currency' => 'NGN',
    'notifyUrl' => config('app.url') . '/api/v1/palmpay/webhook/payout',
    'remark' => 'Bank Transfer via API',
]);
```

### Key Changes:
1. **Endpoint**: Changed to `/api/v2/merchant/payment/payout`
2. **Field names**: Updated to match PalmPay API specification
   - `reference` → `orderId`
   - `bankCode` → `payeeBankCode`
   - `accountNumber` → `payeeBankAccNo`
   - `accountName` → `payeeName`
3. **Amount format**: Convert to kobo (multiply by 100)
4. **Added required fields**: `currency`, `notifyUrl`, `remark`

## Current Status

✅ **All KoboPoint API Issues Fixed:**
1. ✅ GET /banks - Returns proper bank list with codes
2. ✅ POST /banks/verify - Clear error messages
3. ✅ GET /balance - Returns correct wallet balance (₦492.30)
4. ✅ POST /banks/transfer - Uses correct PalmPay endpoint + correct ₦30 fee

## Deployment

Run the deployment script:
```bash
bash DEPLOY_TRANSFER_ENDPOINT_FIX.sh
```

Or manually:
```bash
# 1. Pull code
git pull origin main

# 2. Clear OPcache
curl https://app.pointwave.ng/clear-opcache.php

# 3. Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## Testing

Test the transfer endpoint:
```bash
curl -X POST https://app.pointwave.ng/api/v1/banks/transfer \
  -H 'Authorization: Bearer 7db8dbb3991382487a1fc388a05d96a7139d92ba' \
  -H 'X-Business-ID: 3450968aa027e86e3ff5b0169dc17edd7694a846' \
  -H 'Content-Type: application/json' \
  -d '{
    "amount": 100,
    "bank_code": "090672",
    "account_number": "7040540018",
    "account_name": "BELLBANK MFB"
  }'
```

Expected response:
```json
{
  "status": true,
  "message": "Transfer successful",
  "data": {
    "reference": "PWV_OUT_XXXXXXXXXX",
    "status": "successful",
    "amount": 100,
    "fee": 30,
    "total_deducted": 130
  }
}
```

## Balance Check

Current PointWave Business (KoboPoint) wallet balance:
```bash
php artisan tinker --execute="
\$wallet = DB::table('company_wallets')->where('company_id', 2)->first();
echo 'Balance: ₦' . number_format(\$wallet->balance, 2) . PHP_EOL;
"
```

Result: **₦492.30** (sufficient for testing)

## Fee Structure

- **Transfer Amount**: ₦100
- **PointWave Fee**: ₦30 (charged to KoboPoint)
- **Total Deducted**: ₦130
- **PalmPay Provider Fee**: ₦25 (charged to PointWave)
- **PointWave Profit**: ₦5 per transfer

## Files Changed

1. `app/Http/Controllers/API/V1/MerchantApiController.php` - Fixed PalmPay endpoint and request format
2. `DEPLOY_TRANSFER_ENDPOINT_FIX.sh` - Deployment script

## Related Tasks

- **TASK 3**: Fixed dashboard transfers (TransferService.php) - used correct endpoint
- **TASK 4**: Fixed transfer fee calculation (₦15 → ₦30)
- **TASK 5**: Fixed balance endpoint (ledger_accounts → company_wallets)
- **TASK 6**: Fixed API transfer endpoint (this fix)

---

**Date**: February 21, 2026  
**Status**: ✅ COMPLETE - Ready for Production Testing
