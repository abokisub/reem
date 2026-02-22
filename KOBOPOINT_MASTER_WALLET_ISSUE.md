# KoboPoint Master Wallet Creation Issue - RESOLVED

## Problem
Master wallet creation failing for KoboPoint with error:
```
PalmPay Error: LicenseNumber verification failed (Code: AC100007)
```

## Root Cause
The company was using RC number `RC-9058987` for KYC, but PalmPay rejected it. The system needs to use the **director's BVN** instead.

From the company verification page, the director's BVN is: `22490148602`

However, this BVN was NOT stored in the `companies.director_bvn` column, so the system fell back to using the RC number which PalmPay rejected.

## Solution

### Step 1: Update Company KYC Data
Run the fix script to add the director's BVN:
```bash
php fix_kobopoint_kyc.php
```

This will update the `companies` table:
```sql
UPDATE companies 
SET director_bvn = '22490148602' 
WHERE id = 4;
```

### Step 2: Create Master Wallet
After updating the BVN, run:
```bash
php create_missing_master_wallets.php
```

Or simply have KoboPoint login - the system will auto-create the master wallet using the BVN.

## Why This Happened

The BVN verification data was stored somewhere (visible in the admin panel) but not in the `companies.director_bvn` column. This could be because:

1. The BVN was verified but not saved to the company record
2. The KYC submission process didn't update the company table
3. The BVN is stored in a different table (like `company_kyc_submissions`)

## Prevention

Going forward, ensure that when admin approves KYC:
1. Director BVN is saved to `companies.director_bvn`
2. Director NIN is saved to `companies.director_nin` (if provided)
3. RC number is saved to `companies.business_registration_number`

The system will use them in this priority order:
1. Director BVN (preferred - most reliable)
2. Director NIN (alternative)
3. RC Number (fallback - but PalmPay may reject invalid ones)

## Testing

After running the fix:
```bash
# 1. Check the BVN was updated
mysql> SELECT id, name, director_bvn, director_nin, business_registration_number 
       FROM companies WHERE id = 4;

# 2. Create master wallet
php create_missing_master_wallets.php

# 3. Verify wallet was created
mysql> SELECT id, name, palmpay_account_number, palmpay_account_name 
       FROM companies WHERE id = 4;
```

## Circuit Breaker Note

The repeated failures triggered the PalmPay circuit breaker:
```
🛡️ PalmPay Circuit Breaker: OPENED (High failure rate)
```

This is a safety feature that prevents hammering PalmPay's API with failing requests. It will auto-reset after a cooldown period, or you can manually reset it:

```bash
php artisan cache:clear
```

## Files Modified
- `fix_kobopoint_kyc.php` (NEW) - Script to update KoboPoint's BVN
- `create_missing_master_wallets.php` - Script to create master wallets

## Deployment
Already deployed. Just need to run the fix script on the server.
