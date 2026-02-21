# KYC Charges - Final Implementation Summary

## Overview
KYC verification charges are now implemented with smart logic that differentiates between company onboarding and API usage.

## Charging Logic

### FREE KYC Verification (Company Onboarding)
**When:** New companies registering their business
**Status:** `pending`, `under_review`, `partial`, `unverified`
**Reason:** Companies have zero balance during onboarding and need to verify their own identity

**Example:**
- New company "ABC Ltd" signs up
- They need to verify their BVN/NIN to complete registration
- System checks: `kyc_status = 'pending'` → **NO CHARGE**
- Verification completes successfully
- Company can complete onboarding without hitting "insufficient funds"

### CHARGED KYC Verification (API Usage)
**When:** Verified companies using API to verify their customers
**Status:** `verified`, `approved`
**Reason:** Company is now operational and using the service commercially

**Example:**
- Company "ABC Ltd" is now verified (`kyc_status = 'verified'`)
- They integrate the API to verify their customers
- Customer verification request comes in
- System checks: `kyc_status = 'verified'` → **CHARGE ₦100**
- Charge deducted from company wallet
- Verification proceeds

## Implementation Details

### Modified Files

1. **`app/Services/KYC/KycService.php`**
   - Updated `deductKycCharge()` method
   - Added company status check before charging
   - Free KYC for onboarding statuses: `pending`, `under_review`, `partial`, `unverified`
   - Charges only verified companies

2. **`app/Http/Controllers/API/V1/MerchantApiController.php`**
   - Updated comments to clarify charging logic
   - Endpoints: `/api/v1/kyc/verify-bvn`, `/api/v1/kyc/verify-nin`, `/api/v1/kyc/verify-bank-account`

### Charge Rates

| Service | Charge (Verified Companies) | Onboarding (Free) |
|---------|----------------------------|-------------------|
| BVN Verification | ₦100 | ₦0 |
| NIN Verification | ₦100 | ₦0 |
| Bank Account Verification | ₦50 | ₦0 |

### Business Model

**Your Cost (EaseID):** ₦5 per verification
**Your Charge (to companies):** ₦10 per verification
**Your Profit:** ₦5 per verification

**During Onboarding:**
- You absorb the ₦5 EaseID cost
- Company pays ₦0
- This is a customer acquisition cost

**After Activation:**
- Company pays ₦10
- You pay EaseID ₦5
- You profit ₦5 per API call

## API Endpoints

### 1. Verify BVN
```http
POST /api/v1/kyc/verify-bvn
Authorization: Bearer {SECRET_KEY}
x-api-key: {API_KEY}
x-business-id: {BUSINESS_ID}

{
  "bvn": "12345678901"
}
```

**Response (Onboarding - Free):**
```json
{
  "success": true,
  "message": "BVN verified successfully",
  "data": { ... },
  "charged": false,
  "charge_amount": 0
}
```

**Response (Verified Company - Charged):**
```json
{
  "success": true,
  "message": "BVN verified successfully",
  "data": { ... },
  "charged": true,
  "charge_amount": 100,
  "transaction_reference": "KYC_ENHANCED_BVN_1708531200_1234"
}
```

### 2. Verify NIN
```http
POST /api/v1/kyc/verify-nin
Authorization: Bearer {SECRET_KEY}
x-api-key: {API_KEY}
x-business-id: {BUSINESS_ID}

{
  "nin": "12345678901"
}
```

### 3. Verify Bank Account
```http
POST /api/v1/kyc/verify-bank-account
Authorization: Bearer {SECRET_KEY}
x-api-key: {API_KEY}
x-business-id: {BUSINESS_ID}

{
  "account_number": "0123456789",
  "bank_code": "058"
}
```

## Testing Scenarios

### Scenario 1: New Company Onboarding
1. Company signs up → `kyc_status = 'pending'`
2. Company verifies BVN → **FREE** (no charge)
3. Company verifies NIN → **FREE** (no charge)
4. Admin approves KYC → `kyc_status = 'verified'`
5. Company is now activated

### Scenario 2: Verified Company Using API
1. Company is verified → `kyc_status = 'verified'`
2. Company has ₦500 in wallet
3. Company verifies customer BVN → **CHARGED ₦100**
4. Wallet balance: ₦400
5. Transaction record created

### Scenario 3: Insufficient Balance
1. Company is verified → `kyc_status = 'verified'`
2. Company has ₦50 in wallet
3. Company tries to verify BVN (requires ₦100)
4. Error: "Insufficient balance. Required: ₦100.00, Available: ₦50.00"

## Caching Logic

To save costs, the system caches verification results:

1. **First Verification:** Charges company, calls EaseID, caches result
2. **Subsequent Verifications:** Returns cached result, **NO CHARGE**

This prevents duplicate charges for the same BVN/NIN.

## Transaction Records

All charged verifications create transaction records:

```sql
SELECT * FROM transactions 
WHERE category = 'kyc_charge' 
AND company_id = 123;
```

**Fields:**
- `reference`: KYC_ENHANCED_BVN_1708531200_1234
- `type`: debit
- `category`: kyc_charge
- `amount`: 100.00
- `status`: success
- `description`: KYC Verification Charge - Enhanced BVN

## Deployment Status

✅ Code implemented
✅ Smart charging logic added
✅ Onboarding companies get FREE KYC
✅ Verified companies get CHARGED
✅ Caching prevents duplicate charges
✅ Transaction records created
✅ Logs for debugging

## Next Steps

1. **Test on Server:**
   ```bash
   cd /home/aboksdfs/app.pointwave.ng
   git pull origin main
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Test Scenarios:**
   - Create new company → verify BVN → should be FREE
   - Approve company → verify customer BVN → should be CHARGED

3. **Monitor Logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep "KYC"
   ```

## Summary

The system now intelligently handles KYC charges:
- **Onboarding = FREE** (companies can register without funds)
- **API Usage = CHARGED** (companies pay for customer verifications)
- **Caching = SMART** (no duplicate charges)
- **Transparent = CLEAR** (response shows if charged or not)

This ensures a smooth onboarding experience while maintaining profitability on API usage.
