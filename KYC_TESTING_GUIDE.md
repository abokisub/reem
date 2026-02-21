# KYC Charges Testing Guide

## Overview

This guide explains how to test the KYC charging system to ensure:
1. ✅ Charges are configured in database (NOT hardcoded)
2. ✅ Companies during onboarding are NOT charged (FREE)
3. ✅ Verified companies using API are charged correctly
4. ✅ External EaseID API is working

---

## Quick Start

### Step 1: Setup KYC Charges

```bash
# On your server
cd /home/aboksdfs/app.pointwave.ng
php artisan kyc:setup-charges
```

This command will create all KYC service charges in the database:
- Enhanced BVN: ₦100
- Enhanced NIN: ₦100
- Basic BVN: ₦50
- Basic NIN: ₦50
- Bank Account Verification: ₦50
- Face Recognition: ₦50
- Liveness Detection: ₦100
- Blacklist Check: ₦50
- Credit Score: ₦100
- Loan Features: ₦50

### Step 2: Test Configuration

```bash
php artisan kyc:test-charges
```

This command checks:
- ✅ service_charges table exists
- ✅ KYC charges are configured
- ✅ Company KYC status (onboarding vs verified)
- ✅ Wallet balances
- ✅ Recent KYC transactions
- ✅ EaseID API configuration

### Step 3: Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## Understanding the Charging Logic

### FREE KYC (During Onboarding)

Companies with these statuses are NOT charged:
- `pending` - Just registered
- `under_review` - KYC submitted, waiting for admin review
- `partial` - Some KYC sections approved
- `unverified` - Not yet verified

**Example:**
```php
Company ID: 1
Name: "New Startup Ltd"
KYC Status: "pending"
→ BVN Verification: FREE ✅
→ NIN Verification: FREE ✅
```

### CHARGED KYC (After Activation)

Companies with these statuses ARE charged:
- `verified` - Fully verified and activated
- `approved` - Approved for API usage

**Example:**
```php
Company ID: 2
Name: "Established Corp"
KYC Status: "verified"
Wallet Balance: ₦5,000
→ BVN Verification: ₦100 charged ✅
→ Wallet Balance: ₦4,900
```

---

## Testing Scenarios

### Scenario 1: Test Onboarding Company (FREE)

```bash
# 1. Create test company with pending status
php artisan tinker
>>> $company = Company::create([
...   'name' => 'Test Onboarding Company',
...   'email' => 'test@example.com',
...   'kyc_status' => 'pending'
... ]);
>>> exit

# 2. Create wallet
php artisan tinker
>>> DB::table('company_wallets')->insert([
...   'company_id' => $company->id,
...   'balance' => 0,
...   'created_at' => now(),
...   'updated_at' => now()
... ]);
>>> exit

# 3. Test BVN verification (should be FREE)
curl -X POST https://app.pointwave.ng/api/v1/kyc/verify-bvn \
  -H 'Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c' \
  -H 'x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba' \
  -H 'x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846' \
  -H 'Content-Type: application/json' \
  -d '{"bvn":"22154883751"}'

# Expected response:
# {
#   "status": true,
#   "message": "BVN verified successfully (Free during onboarding)",
#   "data": { ... },
#   "charged": false,
#   "charge_amount": 0
# }
```

### Scenario 2: Test Verified Company (CHARGED)

```bash
# 1. Update company to verified status
php artisan tinker
>>> $company = Company::find(1);
>>> $company->update(['kyc_status' => 'verified']);
>>> exit

# 2. Add balance to wallet
php artisan tinker
>>> DB::table('company_wallets')->where('company_id', 1)->update(['balance' => 1000]);
>>> exit

# 3. Test BVN verification (should charge ₦100)
curl -X POST https://app.pointwave.ng/api/v1/kyc/verify-bvn \
  -H 'Authorization: Bearer YOUR_SECRET_KEY' \
  -H 'x-api-key: YOUR_API_KEY' \
  -H 'x-business-id: YOUR_BUSINESS_ID' \
  -H 'Content-Type: application/json' \
  -d '{"bvn":"22154883751"}'

# Expected response:
# {
#   "status": true,
#   "message": "BVN verified successfully",
#   "data": { ... },
#   "charged": true,
#   "charge_amount": 100,
#   "transaction_reference": "KYC_ENHANCED_BVN_1234567890_5678"
# }

# 4. Check wallet balance (should be ₦900)
php artisan tinker
>>> DB::table('company_wallets')->where('company_id', 1)->value('balance');
>>> exit

# 5. Check transaction record
php artisan tinker
>>> DB::table('transactions')->where('category', 'kyc_charge')->latest()->first();
>>> exit
```

### Scenario 3: Test Insufficient Balance

```bash
# 1. Set wallet balance to ₦50 (less than ₦100 BVN charge)
php artisan tinker
>>> DB::table('company_wallets')->where('company_id', 1)->update(['balance' => 50]);
>>> exit

# 2. Try BVN verification
curl -X POST https://app.pointwave.ng/api/v1/kyc/verify-bvn \
  -H 'Authorization: Bearer YOUR_SECRET_KEY' \
  -H 'x-api-key: YOUR_API_KEY' \
  -H 'x-business-id: YOUR_BUSINESS_ID' \
  -H 'Content-Type: application/json' \
  -d '{"bvn":"22154883751"}'

# Expected response:
# {
#   "status": false,
#   "message": "Insufficient balance. Required: ₦100.00, Available: ₦50.00"
# }
```

---

## Verifying Charges Are NOT Hardcoded

### Check 1: Database Configuration

```bash
# View charges in database
php artisan tinker
>>> DB::table('service_charges')->where('service_category', 'kyc')->get();
>>> exit
```

### Check 2: Code Review

Open `app/Services/KYC/KycService.php` and look for the `deductKycCharge()` method:

```php
// ✅ CORRECT: Loads from database
$charge = DB::table('service_charges')
    ->where('company_id', $companyId)
    ->where('service_category', 'kyc')
    ->where('service_name', $serviceName)
    ->where('is_active', true)
    ->first();

$chargeAmount = $charge->charge_value; // From database

// ❌ WRONG: Hardcoded (this should NOT exist)
// $chargeAmount = 100; // Hardcoded value
```

### Check 3: Modify Charges

```bash
# Change BVN charge from ₦100 to ₦150
php artisan tinker
>>> DB::table('service_charges')
...   ->where('service_name', 'enhanced_bvn')
...   ->update(['charge_value' => 150]);
>>> exit

# Clear cache
php artisan config:clear

# Test again - should charge ₦150 now
curl -X POST https://app.pointwave.ng/api/v1/kyc/verify-bvn ...

# Revert back to ₦100
php artisan tinker
>>> DB::table('service_charges')
...   ->where('service_name', 'enhanced_bvn')
...   ->update(['charge_value' => 100]);
>>> exit
```

---

## Checking EaseID External API

### Test 1: Check Configuration

```bash
php artisan tinker
>>> config('services.easeid.app_id');
>>> config('services.easeid.base_url');
>>> exit
```

### Test 2: Check Logs

```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log | grep -i easeid

# Then make a BVN verification request
# You should see:
# [timestamp] EaseID BVN Verification Request
# [timestamp] EaseID API Response
```

### Test 3: Check EaseID Balance

```bash
# Add endpoint to check EaseID balance
curl -X GET https://app.pointwave.ng/api/v1/kyc/easeid-balance \
  -H 'Authorization: Bearer YOUR_SECRET_KEY' \
  -H 'x-api-key: YOUR_API_KEY' \
  -H 'x-business-id: YOUR_BUSINESS_ID'
```

---

## Troubleshooting

### Issue: "service_charges table not found"

```bash
# Check if migration exists
ls -la database/migrations/*service_charges*

# Run migrations
php artisan migrate

# If migration doesn't exist, create it
php artisan make:migration create_service_charges_table
```

### Issue: "No KYC charges configured"

```bash
# Run setup command
php artisan kyc:setup-charges

# Verify
php artisan kyc:test-charges
```

### Issue: "Company always charged even during onboarding"

```bash
# Check company KYC status
php artisan tinker
>>> Company::find(1)->kyc_status;
>>> exit

# Should be one of: pending, under_review, partial, unverified
# If it's "verified" or "approved", that's why it's charging

# Change to pending for testing
php artisan tinker
>>> Company::find(1)->update(['kyc_status' => 'pending']);
>>> exit
```

### Issue: "EaseID API not responding"

```bash
# Check .env configuration
cat .env | grep EASEID

# Should have:
# EASEID_APP_ID=your_app_id
# EASEID_MERCHANT_ID=your_merchant_id
# EASEID_PRIVATE_KEY=your_private_key
# EASEID_BASE_URL=https://open-api.easeid.ai

# Test connection
php artisan tinker
>>> $client = app(\App\Services\KYC\EaseIdClient::class);
>>> $client->getBalance();
>>> exit
```

---

## Production Checklist

Before going live, verify:

- [ ] KYC charges configured in database
- [ ] Test onboarding company (FREE verification)
- [ ] Test verified company (CHARGED verification)
- [ ] Test insufficient balance error
- [ ] EaseID API credentials configured
- [ ] EaseID API responding correctly
- [ ] Transaction records created correctly
- [ ] Wallet balances updated correctly
- [ ] Logs showing charge deductions
- [ ] API documentation updated

---

## Support Commands

```bash
# View all KYC routes
php artisan route:list | grep kyc

# View recent KYC transactions
php artisan tinker
>>> DB::table('transactions')->where('category', 'kyc_charge')->latest()->take(10)->get();
>>> exit

# View company wallet balances
php artisan tinker
>>> DB::table('company_wallets')->join('companies', 'companies.id', '=', 'company_wallets.company_id')->select('companies.name', 'company_wallets.balance')->get();
>>> exit

# View KYC charges configuration
php artisan tinker
>>> DB::table('service_charges')->where('service_category', 'kyc')->get();
>>> exit
```

---

## Contact

If you encounter any issues:
1. Run: `php artisan kyc:test-charges`
2. Check logs: `tail -f storage/logs/laravel.log`
3. Share the output with the development team

---

**Last Updated:** February 21, 2026
**Version:** 1.0
