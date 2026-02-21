# EaseID API Debug Guide

## Problem Summary

The NIN verification API is returning empty responses because EaseID API is returning `success: false` even though we get HTTP 200 responses. This indicates an authentication or signature issue with EaseID.

## Root Cause Analysis

From the logs:
```
[2026-02-21 09:43:42] production.INFO: EaseID NIN Verification Request {"nin":"352****66"}
[2026-02-21 09:43:42] production.INFO: EaseID API Response {"endpoint":"/api/validator-service/open/nin/inquire","status":200,"success":false}
```

**HTTP 200 but success=false** means:
- The request reached EaseID servers ✅
- Authentication/signature is failing ❌
- OR EaseID account has issues ❌

## Step 1: Run Debug Script

I've created a comprehensive debug script to test EaseID API authentication.

```bash
cd /home/aboksdfs/app.pointwave.ng
php test_easeid_debug.php
```

This script will:
1. ✅ Check EaseID configuration in .env
2. ✅ Validate RSA private key format
3. ✅ Test signature generation algorithm
4. ✅ Make real API call to EaseID
5. ✅ Analyze response and provide diagnosis

## Step 2: Interpret Results

### Scenario A: Invalid Private Key Format
```
❌ Invalid RSA private key format!
OpenSSL Error: ...
```

**Solution:** The private key in `.env` needs proper PEM formatting. Contact EaseID support to get the correct private key with headers:
```
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDLJkOO...
-----END PRIVATE KEY-----
```

### Scenario B: Authentication Error (Code 10001)
```
❌ API Success: false
Code: 10001
⚠️  DIAGNOSIS: Invalid signature or authentication
```

**Possible Causes:**
1. **Wrong App ID** - Verify `EASEID_APP_ID=K8865857536` is correct
2. **Wrong Private Key** - The private key doesn't match what's registered with EaseID
3. **Signature Algorithm Mismatch** - EaseID expects specific signature format

**Solution:**
- Contact EaseID support: support@easeid.ai
- Verify credentials are for PRODUCTION environment (not sandbox)
- Request new credentials if needed
- Ask EaseID to verify your account is active

### Scenario C: Insufficient Balance (Code 10003)
```
❌ API Success: false
Code: 10003
⚠️  DIAGNOSIS: Insufficient balance
```

**Solution:** Top up your EaseID account balance

### Scenario D: Account Not Activated
```
❌ API Success: false
Message: Account not activated
```

**Solution:** Contact EaseID to activate your account for production use

## Step 3: Common Fixes

### Fix 1: Update Private Key Format in .env

If the private key needs PEM headers, update `.env`:

```bash
nano .env
```

Change from:
```
EASEID_PRIVATE_KEY="MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDLJkOO..."
```

To:
```
EASEID_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDLJkOO...
-----END PRIVATE KEY-----"
```

Then clear config cache:
```bash
php artisan config:clear
```

### Fix 2: Verify EaseID Account Status

Contact EaseID support with these details:
- **App ID:** K8865857536
- **Merchant ID:** K8865857536
- **Issue:** API returning success=false for all requests
- **Environment:** Production (https://open-api.easeid.ai)

Ask them to verify:
1. Account is active and approved for production
2. Private key is correct
3. Account has sufficient balance
4. No IP restrictions blocking your server

### Fix 3: Test with EaseID Sandbox First

If production credentials are having issues, test with sandbox first:

1. Get sandbox credentials from EaseID
2. Update `.env`:
```
EASEID_BASE_URL=https://sandbox-api.easeid.ai
EASEID_APP_ID=<sandbox_app_id>
EASEID_PRIVATE_KEY="<sandbox_private_key>"
```
3. Test again

## Step 4: Re-test NIN Verification

After fixing the EaseID issue, test again:

```bash
curl -X POST "https://app.pointwave.ng/api/v1/kyc/verify-nin" \
  -H "Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c" \
  -H "x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba" \
  -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -H "Idempotency-Key: test_nin_$(date +%s)" \
  -H "Content-Type: application/json" \
  -d '{"nin":"35257106066"}' | python3 -m json.tool
```

Expected success response:
```json
{
  "status": true,
  "request_id": "...",
  "message": "NIN verified successfully",
  "data": {
    "verified": true,
    "nin": "35257106066",
    "data": {
      "firstName": "...",
      "lastName": "...",
      "dateOfBirth": "...",
      ...
    },
    "charged": true,
    "charge_amount": 100,
    "transaction_reference": "KYC_ENHANCED_NIN_..."
  }
}
```

## Step 5: Verify Transaction Created

After successful verification, check transaction was created:

```bash
php artisan tinker
```

```php
// Get latest KYC transaction
$tx = DB::table('transactions')->where('category', 'kyc_charge')->latest()->first();
echo "Transaction ID: " . $tx->id . "\n";
echo "Amount: ₦" . $tx->amount . "\n";
echo "Status: " . $tx->status . "\n";
echo "Description: " . $tx->description . "\n";
echo "Reference: " . $tx->reference . "\n";

// Check wallet balance (should be deducted)
$wallet = DB::table('company_wallets')->where('company_id', 1)->first();
echo "\nWallet Balance: ₦" . $wallet->balance . "\n";
```

Expected output:
```
Transaction ID: 123
Amount: ₦100.00
Status: success
Description: KYC Verification Charge - Enhanced Nin
Reference: KYC_ENHANCED_NIN_1234567890_5678

Wallet Balance: ₦99.00  (deducted from ₦199.00)
```

## EaseID Support Contact

If you need to contact EaseID support:

**Email:** support@easeid.ai  
**Subject:** Production API Authentication Issue - App ID K8865857536

**Message Template:**
```
Hello EaseID Support,

We are experiencing authentication issues with our production API credentials.

Account Details:
- App ID: K8865857536
- Merchant ID: K8865857536
- Environment: Production (https://open-api.easeid.ai)

Issue:
All API requests are returning HTTP 200 but with success=false in the response body.
We are using the correct signature generation algorithm (MD5 hash + RSA SHA256 signing).

Request:
1. Please verify our account is active and approved for production use
2. Confirm our private key is correct
3. Check if there are any IP restrictions or account limitations
4. Verify our account has sufficient balance

Thank you for your assistance.
```

## Next Steps

1. ✅ Run `php test_easeid_debug.php` on server
2. ✅ Share the output with me
3. ✅ Based on the error code, we'll know exactly what to fix
4. ✅ Contact EaseID support if needed
5. ✅ Re-test NIN verification after fix
6. ✅ Verify transaction and wallet deduction working

## Files Modified

- ✅ `app/Http/Controllers/API/V1/MerchantApiController.php` - Fixed company_id retrieval
- ✅ `test_easeid_debug.php` - New debug script
- ✅ `EASEID_DEBUG_GUIDE.md` - This guide
