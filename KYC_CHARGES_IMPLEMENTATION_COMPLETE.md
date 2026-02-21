# ✅ KYC Charges System - Fully Implemented

**Date:** February 21, 2026  
**Status:** Production Ready

---

## 🎯 What Was Implemented

### 1. Automatic Charge Deduction ✅
- KYC charges are now automatically deducted BEFORE verification
- Charges are taken from company wallet
- Transaction records are created for each charge
- Balance is checked before verification

### 2. Three KYC Verification Endpoints ✅
All accessible via external API V1:

1. **POST /api/v1/kyc/verify-bvn**
   - Charge: ₦100 (Enhanced BVN)
   - Deducts from wallet automatically
   - Returns verification data + charge info

2. **POST /api/v1/kyc/verify-nin**
   - Charge: ₦100 (Enhanced NIN)
   - Deducts from wallet automatically
   - Returns verification data + charge info

3. **POST /api/v1/kyc/verify-bank-account**
   - Charge: ₦50 (Bank Account Verification)
   - Deducts from wallet automatically
   - Returns verification data + charge info

### 3. Smart Caching ✅
- Cached verifications don't charge again
- Saves money for repeated verifications
- Returns cached data instantly

### 4. Proper Error Handling ✅
- Insufficient balance → Clear error message
- Charge configuration missing → Helpful error
- Verification fails → Charge still deducted (API was called)

---

## 📋 How It Works

### Flow Diagram:
```
1. API Call (POST /api/v1/kyc/verify-bvn)
   ↓
2. Check Cache (Already verified?)
   ├─ Yes → Return cached data (NO CHARGE)
   └─ No → Continue
   ↓
3. Get Charge Configuration
   ├─ Company-specific charge
   └─ Fallback to global default
   ↓
4. Check Wallet Balance
   ├─ Sufficient → Continue
   └─ Insufficient → Return error (NO VERIFICATION)
   ↓
5. Deduct Charge from Wallet
   ├─ Update wallet balance
   └─ Create transaction record
   ↓
6. Call EaseID API (Verify BVN/NIN/Bank)
   ├─ Success → Cache result
   └─ Failure → Return error (CHARGE ALREADY DEDUCTED)
   ↓
7. Return Response
   ├─ Verification data
   ├─ Charge amount
   └─ Transaction reference
```

---

## 🔧 Technical Implementation

### Files Modified:

1. **app/Services/KYC/KycService.php**
   - Added `deductKycCharge()` method
   - Updated `verifyBVN()` with charge deduction
   - Updated `verifyNIN()` with charge deduction
   - Updated `verifyBankAccount()` with charge deduction

2. **app/Http/Controllers/API/V1/MerchantApiController.php**
   - Added `verifyBVN()` endpoint
   - Added `verifyNIN()` endpoint
   - Added `verifyBankAccount()` endpoint

3. **routes/api.php**
   - Updated KYC routes to use MerchantApiController
   - Routes now use charge deduction logic

---

## 📊 Charge Configuration

Charges are stored in `service_charges` table:

| Service Name | Category | Amount | Status |
|--------------|----------|--------|--------|
| enhanced_bvn | kyc | ₦100 | Active |
| enhanced_nin | kyc | ₦100 | Active |
| basic_bvn | kyc | ₦50 | Active |
| basic_nin | kyc | ₦50 | Active |
| bank_account_verification | kyc | ₦50 | Active |

**Admin can update charges at:** `/secure/discount/other`

---

## 🧪 API Examples

### 1. Verify BVN

**Request:**
```bash
curl -X POST "https://app.pointwave.ng/api/v1/kyc/verify-bvn" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID" \
  -H "Content-Type: application/json" \
  -d '{
    "bvn": "22222222222"
  }'
```

**Success Response:**
```json
{
  "status": "success",
  "message": "BVN verified successfully",
  "data": {
    "verified": true,
    "bvn": "22222222222",
    "data": {
      "first_name": "John",
      "last_name": "Doe",
      "date_of_birth": "1990-01-01",
      "phone_number": "08012345678"
    },
    "charged": true,
    "charge_amount": 100,
    "transaction_reference": "KYC_ENHANCED_BVN_1708257600_1234"
  }
}
```

**Insufficient Balance Response:**
```json
{
  "status": "error",
  "message": "Insufficient balance. Required: ₦100.00, Available: ₦50.00",
  "data": []
}
```

### 2. Verify NIN

**Request:**
```bash
curl -X POST "https://app.pointwave.ng/api/v1/kyc/verify-nin" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID" \
  -H "Content-Type: application/json" \
  -d '{
    "nin": "12345678901"
  }'
```

**Success Response:**
```json
{
  "status": "success",
  "message": "NIN verified successfully",
  "data": {
    "verified": true,
    "nin": "12345678901",
    "data": {
      "first_name": "John",
      "last_name": "Doe",
      "date_of_birth": "1990-01-01"
    },
    "charged": true,
    "charge_amount": 100,
    "transaction_reference": "KYC_ENHANCED_NIN_1708257600_5678"
  }
}
```

### 3. Verify Bank Account

**Request:**
```bash
curl -X POST "https://app.pointwave.ng/api/v1/kyc/verify-bank-account" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID" \
  -H "Content-Type: application/json" \
  -d '{
    "account_number": "0123456789",
    "bank_code": "058"
  }'
```

**Success Response:**
```json
{
  "status": "success",
  "message": "Bank account verified successfully",
  "data": {
    "verified": true,
    "account_number": "0123456789",
    "bank_code": "058",
    "data": {
      "account_name": "JOHN DOE",
      "bank_name": "GTBank"
    },
    "charged": true,
    "charge_amount": 50,
    "transaction_reference": "KYC_BANK_ACCOUNT_VERIFICATION_1708257600_9012"
  }
}
```

---

## 💰 Transaction Records

Each KYC charge creates a transaction record:

```sql
SELECT * FROM transactions WHERE category = 'kyc_charge';
```

**Transaction Details:**
- `type`: 'debit'
- `category`: 'kyc_charge'
- `amount`: Charge amount (₦50 or ₦100)
- `fee`: 0
- `status`: 'success'
- `description`: 'KYC Verification Charge - Enhanced BVN'
- `reference`: 'KYC_ENHANCED_BVN_1708257600_1234'
- `metadata`: JSON with service details

---

## 🔍 Verification

### Check if Charges are Working:

```bash
# 1. Check service charges configuration
mysql> SELECT * FROM service_charges WHERE service_category = 'kyc';

# 2. Check KYC transactions
mysql> SELECT * FROM transactions WHERE category = 'kyc_charge' ORDER BY created_at DESC LIMIT 10;

# 3. Test API endpoint
curl -X POST "https://app.pointwave.ng/api/v1/kyc/verify-bvn" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID" \
  -H "Content-Type: application/json" \
  -d '{"bvn": "22222222222"}'
```

---

## 📈 Benefits

### For Business:
- ✅ Automatic revenue from KYC services
- ✅ Covers EaseID API costs
- ✅ Prevents abuse of free verifications
- ✅ Proper financial tracking

### For Developers:
- ✅ Simple API - charges happen automatically
- ✅ Clear error messages
- ✅ Transaction references for tracking
- ✅ Cached results save money

### For System:
- ✅ Proper ledger entries
- ✅ Audit trail for all charges
- ✅ Balance validation before verification
- ✅ No manual intervention needed

---

## 🚀 Deployment

### Step 1: Push to GitHub
```bash
git add .
git commit -m "Implement KYC charge deduction system with external API endpoints"
git push origin main
```

### Step 2: Deploy on Server
```bash
cd /home/aboksdfs/app.pointwave.ng
git pull origin main
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

### Step 3: Test
```bash
# Test BVN verification
curl -X POST "https://app.pointwave.ng/api/v1/kyc/verify-bvn" \
  -H "Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c" \
  -H "x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba" \
  -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -H "Content-Type: application/json" \
  -d '{"bvn": "22222222222"}'
```

---

## 📝 Notes

1. **Charges are deducted BEFORE verification** - This is intentional because the EaseID API is called regardless of result
2. **Cached verifications don't charge** - Saves money for repeated checks
3. **Company-specific charges override global** - Allows custom pricing per company
4. **Transaction records are created** - Full audit trail
5. **Balance is checked first** - No verification if insufficient funds

---

**Status:** ✅ Production Ready  
**Tested:** ✅ Locally  
**Documented:** ✅ Complete  
**API Accessible:** ✅ External V1 API

