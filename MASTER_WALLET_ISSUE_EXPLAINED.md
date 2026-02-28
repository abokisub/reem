.sh` (deployment script)

---

## FUTURE COMPANIES

All new companies that register and get KYC approved will now automatically get:
1. ✅ Company wallet
2. ✅ Master virtual account (using director BVN)
3. ✅ Ability to create customer accounts immediately

No manual intervention needed!

---

**Last Updated:** February 24, 2026  
**Status:** Fixed and Deployed ✅
ccounts

### What Was Fixed:
- ✅ Admin KYC approval now creates company wallet
- ✅ Admin KYC approval now creates master virtual account
- ✅ Master account uses company director's BVN
- ✅ Customers can create accounts without KYC
- ✅ All customer accounts use company director's BVN

### Files Changed:
- `app/Http/Controllers/Admin/CompanyKycController.php` (main fix)
- `check_amtpay_company.php` (diagnostic script)
- `fix_amtpay_master_wallet.php` (fix script for existing companies)
- `DEPLOY_MASTER_WALLET_FIXID: {amtpay_business_id}" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "test-customer-001",
    "customerName": "Test Customer",
    "email": "test@example.com",
    "phoneNumber": "+2349012345678",
    "accountType": "static",
    "bankCodes": ["100033"]
  }'
```

Should return success with account number

---

## SUMMARY

### What Was Wrong:
- ❌ Admin KYC approval didn't create company wallet
- ❌ Admin KYC approval didn't create master virtual account
- ❌ New companies couldn't create customer a;
```

Should return 1 row with balance = 0

### 3. Check Master Virtual Account
```sql
SELECT * FROM virtual_accounts 
WHERE company_id = (SELECT id FROM companies WHERE email = 'amtpxon@gmail.com')
AND is_master = 1
AND provider = 'pointwave';
```

Should return 1 row with account number

### 4. Test Customer Account Creation
```bash
curl -X POST https://app.pointwave.ng/api/gateway/virtual-accounts \
  -H "Authorization: Bearer {amtpay_secret_key}" \
  -H "X-API-Key: {amtpay_api_key}" \
  -H "X-Business- deployment script
bash DEPLOY_MASTER_WALLET_FIX.sh

# Fix existing companies (if needed)
php check_amtpay_company.php
php fix_amtpay_master_wallet.php
```

---

## VERIFICATION

After deployment, verify the fix:

### 1. Check Admin Dashboard
- Go to: https://app.pointwave.ng/secure/companies
- Click on "amtpay" company
- Verify KYC status is "Verified" or "Approved"

### 2. Check Company Wallet
```sql
SELECT * FROM company_wallets WHERE company_id = (
    SELECT id FROM companies WHERE email = 'amtpxon@gmail.com'
)e.ng

# Step 1: Check current status
php check_amtpay_company.php

# Step 2: Fix missing wallet and account
php fix_amtpay_master_wallet.php
```

### What the Fix Script Does:

1. ✅ Checks if company wallet exists, creates if missing
2. ✅ Checks if master virtual account exists
3. ✅ Verifies company has director BVN
4. ✅ Creates master virtual account using director BVN
5. ✅ Logs all actions

---

## DEPLOYMENT INSTRUCTIONS

```bash
cd /home/aboksdfs/app.pointwave.ng

# Pull latest code
git pull origin main

# Run                          │
└──────────────────────────────────────────────────────────┘
```

### Why Aggregator Model?

1. **Fast Onboarding:** Customers get accounts instantly
2. **No Customer KYC:** Customers don't submit BVN/NIN
3. **Company Responsibility:** Company is responsible for all transactions
4. **Regulatory Compliance:** Company's KYC covers all customers

---

## HOW TO FIX EXISTING COMPANIES

### For "amtpay" Company:

Run these commands on the server:

```bash
cd /home/aboksdfs/app.pointwav│
│       │                                                  │
│       ├─ Customer 1 → Gets Account (uses director BVN)  │
│       ├─ Customer 2 → Gets Account (uses director BVN)  │
│       └─ Customer 3 → Gets Account (uses director BVN)  │
│                                                          │
│  ✅ Only company does KYC once                          │
│  ✅ Fast customer onboarding                            │
│  ✅ Low friction                                        │
│                                                            │
│  ❌ High friction                                       │
│                                                          │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│  AGGREGATOR MODEL (PointWave Uses This)                 │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  Company Director → Submits BVN → Master Account        │  TRADITIONAL MODEL (Not Used)                            │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  Customer 1 → Submits BVN → Gets Account                │
│  Customer 2 → Submits BVN → Gets Account                │
│  Customer 3 → Submits BVN → Gets Account                │
│                                                          │
│  ❌ Every customer must do KYC                          │
│  ❌ Slow onboarding          true
        );
    }
    
    return $company;
}
```

**Result:**
- Company approved ✅
- API credentials generated ✅
- Company wallet created ✅
- Master virtual account created ✅
- Customer account creation WORKS ✅

---

## AGGREGATOR MODEL EXPLAINED

### What is Aggregator Model?

In the aggregator model, the company (merchant) uses their own KYC (director BVN) for ALL customer virtual accounts. Customers don't need to submit their own BVN.

```
┌──────────────────────────────────────────────────────────┐
 ]);
    }

    // ✅ CREATE MASTER VIRTUAL ACCOUNT
    if ($company->director_bvn) {
        $virtualAccountService = new VirtualAccountService();
        $result = $virtualAccountService->createVirtualAccount(
            $company->id,
            null,                    // No customer_id
            $company->name,
            $company->email,
            $company->phone,
            $company->director_bvn,  // ⭐ Company director's BVN
            null,
            true                     // is_master =pproveCompany(Company $company)
{
    // Generate API credentials ✅
    $credentials = Company::generateApiKeys();
    $company->update($credentials);

    $company->update([
        'kyc_status' => 'approved',
        'is_active' => true
    ]);

    // ✅ CREATE COMPANY WALLET
    $wallet = CompanyWallet::where('company_id', $company->id)->first();
    if (!$wallet) {
        CompanyWallet::create([
            'company_id' => $company->id,
            'currency' => 'NGN',
            'balance' => 0,
       
    $company->update($credentials);

    $company->update([
        'kyc_status' => 'approved',
        'is_active' => true
    ]);

    // ❌ NO WALLET CREATION
    // ❌ NO MASTER ACCOUNT CREATION
    
    return $company;
}
```

**Result:**
- Company approved ✅
- API credentials generated ✅
- Company wallet NOT created ❌
- Master virtual account NOT created ❌
- Customer account creation FAILS ❌

---

## WHAT WAS FIXED

### After Fix:

```php
// app/Http/Controllers/Admin/CompanyKycController.php
private function a
4. Customer Creates Virtual Account
   ├─ Company calls API: POST /api/gateway/virtual-accounts
   ├─ System uses company's MASTER account director BVN
   ├─ Customer gets account WITHOUT submitting their own BVN
   └─ All customer accounts linked to company director's BVN
```

---

## WHAT WAS BROKEN

### Before Fix:

```php
// app/Http/Controllers/Admin/CompanyKycController.php
private function approveCompany(Company $company)
{
    // Generate API credentials ✅
    $credentials = Company::generateApiKeys();
└─────────────────────────────────────────────────────────────┘

1. Company Registers
   ├─ User account created
   ├─ Company record created
   └─ Company wallet created ✅ (was working)

2. Company Submits KYC
   ├─ Business info
   ├─ Director BVN ⭐ (CRITICAL)
   ├─ CAC documents
   └─ Bank details

3. Admin Approves KYC
   ├─ API credentials generated ✅ (was working)
   ├─ Company wallet created ❌ (was MISSING)
   └─ Master virtual account created ❌ (was MISSING)
        └─ Uses director BVN for PalmPay
hey got virtual accounts from other providers (Xixapay, Monnify, Paystack) but the PointWave PalmPay master account failed with error:

```
PalmPay Error: LicenseNumber verification failed (Code: AC100007)
```

**Root Cause:** The system was NOT creating the master wallet and master virtual account when admin approved company KYC.

---

## HOW IT SHOULD WORK (AGGREGATOR MODEL)

```
┌─────────────────────────────────────────────────────────────┐
│  COMPANY REGISTRATION & ACTIVATION FLOW                     │pany registered and you activated their business, t# Master Wallet Issue - Explained & Fixed

## THE PROBLEM

When "amtpay" com