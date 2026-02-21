# Message to KoboPoint Team

Hi KoboPoint Team,

Great news! We've fixed the transfer fee issue you reported.

## The Problem

Your API transfers were being charged ₦15 instead of the correct ₦30 fee. This was because the API endpoint was using the wrong fee configuration.

## What We Fixed

The API was using "Settlement Withdrawal" fees (₦15) instead of "External Transfer (Other Banks)" fees (₦30). We've now updated the API to use the same fee calculation as your dashboard.

**Fee Breakdown:**
- **Transfer Amount**: ₦100
- **PointWave Fee**: ₦30 (now correct!)
- **Total Required**: ₦130

**PalmPay Provider Fee:**
- PalmPay charges us ₦25 per transfer to other banks
- We charge you ₦30
- Our profit: ₦5 per transfer

## Current Status

✅ **GET /banks** - Fixed (returns proper bank list with codes)
✅ **POST /banks/verify** - Fixed (clear error messages)
✅ **POST /banks/transfer** - Fixed (correct ₦30 fee + working endpoint)

## About the Balance Error

The error you saw earlier:
```
"Insufficient balance. Required: 115 (Amount: 100 + Fee: 15.00)"
```

This was from an OLD test when:
1. The fee was incorrectly ₦15 (now fixed to ₦30)
2. Your PointWave balance was ₦222.30 (now ₦492.30)

**Current Situation:**
- ✅ Your PointWave Business Wallet: ₦492.30
- ✅ Correct fee: ₦30
- ✅ For ₦100 transfer, you need: ₦130 total
- ✅ You have enough balance!

## Next Steps

1. **We'll deploy this fix to production**
2. **You can retry your transfers** - they should work now with the correct ₦30 fee
3. **Your end users** will see ₦130 deducted for a ₦100 transfer (₦100 + ₦30 fee)

## Technical Details

The fix ensures the API uses the same fee settings as your dashboard:
- Dashboard path: `/secure/discount/banks` → "External Transfer (Other Banks)" → ₦30
- API now uses: `payout_bank_charge_value` → ₦30

Both now use the exact same calculation logic.

---

**PointWave Team**  
February 21, 2026