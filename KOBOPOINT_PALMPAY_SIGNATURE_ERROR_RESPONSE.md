# Response to Kobopoint Developer - PalmPay Signature Error

## Issue Summary
Error Code: `OPEN_GW_000008`  
Error Message: "PalmPay Error: sign error"  
Business ID: `3450968aa027e86e3ff5b0169dc17edd7694a846`

## Root Cause
The "sign error" indicates that the PalmPay API signature verification is failing. This happens when:
1. PalmPay API credentials are missing or incorrect
2. PalmPay API credentials are not properly configured in your PointWave account
3. Your account hasn't been fully activated for PalmPay integration

## Immediate Action Required

### Step 1: Verify Your PalmPay Credentials
Please check your PointWave admin panel:

1. Log in to your PointWave admin dashboard
2. Navigate to: **Settings → PalmPay Configuration**
3. Verify these fields are filled:
   - PalmPay App ID
   - PalmPay Secret Key
   - PalmPay Public Key
   - PalmPay Merchant ID

### Step 2: Check Account Activation Status
Run this diagnostic script on your server to check your configuration:

```bash
php check_kobopoint_palmpay_config.php
```

This will show:
- Whether PalmPay credentials are configured
- Whether your account is activated for PalmPay
- Current integration status

## What We Need From You

To resolve this quickly, please provide:

1. **Screenshot** of your PalmPay configuration page (hide sensitive keys)
2. **Confirmation** that you've received PalmPay credentials from PointWave
3. **Company ID** from your PointWave account (found in Settings)

## Expected Resolution Steps

Once we verify your configuration:

1. If credentials are missing → We'll provide them
2. If credentials are incorrect → We'll update them
3. If account needs activation → We'll activate PalmPay for your account

## Testing After Fix

After we resolve the configuration issue, test with:

```bash
curl -X POST https://app.pointwave.ng/api/gateway/virtual-accounts \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -H "Business-Id: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -d '{
    "userId": "TEST-USER-001",
    "customerName": "Test Customer",
    "email": "test@example.com",
    "phoneNumber": "+2349012345678",
    "accountType": "static",
    "bankCodes": ["100033"]
  }'
```

## Timeline
- Configuration check: Immediate
- Credential provision: Within 1 hour
- Account activation: Within 2 hours
- Full resolution: Same business day

## Contact
For urgent assistance:
- Email: support@pointwave.ng
- Phone: [Support Number]
- WhatsApp: [Support WhatsApp]

We'll prioritize this as it's blocking your production launch.

---

**Next Steps:**
1. Run the diagnostic script
2. Send us the results
3. We'll provide the fix immediately
