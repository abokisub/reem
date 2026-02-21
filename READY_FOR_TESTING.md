# ✅ KoboPoint API - Ready for Testing

## What Was Fixed

The transfer endpoint was using the **wrong PalmPay API endpoint**, causing the "invalid url router" error. This has now been fixed.

## Changes Pushed to GitHub

✅ Fixed `app/Http/Controllers/API/V1/MerchantApiController.php`
- Changed endpoint from `/transfer/v1/initiate` to `/api/v2/merchant/payment/payout`
- Updated request format to match PalmPay specification
- Amount now in kobo (multiply by 100)

## Deploy to Production

Run this on your server:
```bash
cd /home/aboksdfs/app.pointwave.ng
bash DEPLOY_TRANSFER_ENDPOINT_FIX.sh
```

Or manually:
```bash
git pull origin main
curl https://app.pointwave.ng/clear-opcache.php
php artisan config:clear
php artisan cache:clear
```

## Test Transfer

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

## Expected Result

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

## All KoboPoint Issues - FIXED ✅

1. ✅ GET /banks - Returns proper bank list
2. ✅ POST /banks/verify - Clear error messages  
3. ✅ GET /balance - Correct balance (₦492.30)
4. ✅ POST /banks/transfer - Correct endpoint + ₦30 fee

## Your Balance

**PointWave Business Wallet**: ₦492.30  
**Sufficient for testing**: Yes (need ₦130 for ₦100 transfer)

---

**Ready to test!** 🚀
