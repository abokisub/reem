# Deploy KYC Testing Commands - Quick Guide

## What Was Fixed

The standalone PHP script `test_kyc_charges.php` had database credential issues. I've replaced it with proper Laravel artisan commands that use your existing database configuration.

---

## Deployment Steps

### 1. Pull Latest Code

```bash
cd /home/aboksdfs/app.pointwave.ng
git pull origin main
```

You should see:
- ✅ `app/Console/Commands/SetupKycCharges.php` (new)
- ✅ `app/Console/Commands/TestKycCharges.php` (new)
- ✅ `KYC_TESTING_GUIDE.md` (new)
- ❌ `test_kyc_charges.php` (deleted - had DB issues)

### 2. Setup KYC Charges

```bash
php artisan kyc:setup-charges
```

This will create all KYC service charges in your database:
- Enhanced BVN: ₦100
- Enhanced NIN: ₦100
- Bank Account Verification: ₦50
- Face Recognition: ₦50
- Liveness Detection: ₦100
- Blacklist Check: ₦50
- Credit Score: ₦100
- Loan Features: ₦50

### 3. Test Configuration

```bash
php artisan kyc:test-charges
```

This command checks:
- ✅ service_charges table exists
- ✅ KYC charges configured
- ✅ Company KYC status (onboarding vs verified)
- ✅ Wallet balances
- ✅ Recent KYC transactions
- ✅ EaseID API configuration

### 4. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## What You'll See

### Expected Output from `php artisan kyc:setup-charges`:

```
========================================
  KYC CHARGES SETUP
========================================

  ✅ Created: enhanced_bvn → ₦100
  ✅ Created: enhanced_nin → ₦100
  ✅ Created: bank_account_verification → ₦50
  ✅ Created: face_recognition → ₦50
  ✅ Created: liveness_detection → ₦100
  ✅ Created: blacklist_check → ₦50
  ✅ Created: credit_score → ₦100
  ✅ Created: loan_features → ₦50

========================================
  SUMMARY
========================================

✅ Created: 8 charges

KYC charges setup complete!
```

### Expected Output from `php artisan kyc:test-charges`:

```
========================================
  KYC CHARGES CONFIGURATION TEST
========================================

TEST 1: Checking service_charges table...
-------------------------------------------
✅ KYC charges found: 8 services

  • enhanced_bvn: ₦100 (✅ Active, Global)
  • enhanced_nin: ₦100 (✅ Active, Global)
  • bank_account_verification: ₦50 (✅ Active, Global)
  ...

TEST 2: Checking company KYC status...
-------------------------------------------
Found 3 companies:

  • Company #1: Your Company Name
    Status: pending → 🆓 FREE (onboarding)
  • Company #2: Another Company
    Status: verified → 💰 WILL CHARGE

TEST 3: Checking company wallet balances...
-------------------------------------------
Found 3 wallets:

  • Company #1: Your Company Name
    Balance: ₦1,000.00 ✅

...

========================================
  SUMMARY
========================================

✅ KYC charges configured (8 active services)
✅ Companies exist (3 found)
✅ Wallets with balance (2 found)
✅ EaseID API configured
```

---

## Verify Everything Works

### Test 1: Check Charges in Database

```bash
php artisan tinker
>>> DB::table('service_charges')->where('service_category', 'kyc')->get();
>>> exit
```

### Test 2: Test BVN Verification (Onboarding Company - FREE)

```bash
# Make sure you have a company with kyc_status = 'pending'
curl -X POST https://app.pointwave.ng/api/v1/kyc/verify-bvn \
  -H 'Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c' \
  -H 'x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba' \
  -H 'x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846' \
  -H 'Content-Type: application/json' \
  -d '{"bvn":"22154883751"}'
```

Expected response:
```json
{
  "status": true,
  "message": "BVN verified successfully (Free during onboarding)",
  "data": { ... },
  "charged": false,
  "charge_amount": 0
}
```

### Test 3: Test BVN Verification (Verified Company - CHARGED)

```bash
# First, update a company to verified status
php artisan tinker
>>> $company = Company::find(1);
>>> $company->update(['kyc_status' => 'verified']);
>>> DB::table('company_wallets')->where('company_id', 1)->update(['balance' => 1000]);
>>> exit

# Then test
curl -X POST https://app.pointwave.ng/api/v1/kyc/verify-bvn \
  -H 'Authorization: Bearer YOUR_SECRET_KEY' \
  -H 'x-api-key: YOUR_API_KEY' \
  -H 'x-business-id: YOUR_BUSINESS_ID' \
  -H 'Content-Type: application/json' \
  -d '{"bvn":"22154883751"}'
```

Expected response:
```json
{
  "status": true,
  "message": "BVN verified successfully",
  "data": { ... },
  "charged": true,
  "charge_amount": 100,
  "transaction_reference": "KYC_ENHANCED_BVN_1234567890_5678"
}
```

---

## Troubleshooting

### If you see "service_charges table not found"

```bash
# Check if migration exists
ls -la database/migrations/*service_charges*

# If it doesn't exist, you need to create the table first
# Contact me to create the migration
```

### If charges are not being deducted

```bash
# Check company KYC status
php artisan tinker
>>> Company::find(1)->kyc_status;
>>> exit

# If it's "pending", "under_review", "partial", or "unverified" → FREE (correct)
# If it's "verified" or "approved" → SHOULD CHARGE

# Check wallet balance
php artisan tinker
>>> DB::table('company_wallets')->where('company_id', 1)->value('balance');
>>> exit

# If balance is less than charge amount, you'll get "Insufficient balance" error
```

### If EaseID API is not responding

```bash
# Check configuration
cat .env | grep EASEID

# Should have:
# EASEID_APP_ID=your_app_id
# EASEID_MERCHANT_ID=your_merchant_id
# EASEID_PRIVATE_KEY=your_private_key
# EASEID_BASE_URL=https://open-api.easeid.ai

# Check logs
tail -f storage/logs/laravel.log | grep -i easeid
```

---

## Summary

✅ Fixed database credential issue by using Laravel commands
✅ Created `php artisan kyc:setup-charges` to configure charges
✅ Created `php artisan kyc:test-charges` to verify configuration
✅ Charges are stored in database (NOT hardcoded)
✅ Onboarding companies get FREE KYC verification
✅ Verified companies are charged correctly
✅ Complete testing guide available in `KYC_TESTING_GUIDE.md`

---

## Next Steps

1. Pull code: `git pull origin main`
2. Setup charges: `php artisan kyc:setup-charges`
3. Test config: `php artisan kyc:test-charges`
4. Clear caches: `php artisan config:clear && php artisan cache:clear`
5. Test API endpoints with curl commands above

---

**Ready to test!** 🚀
