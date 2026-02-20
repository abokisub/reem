# Email to Kobopoint Developer - Correct Credentials

---

**Subject:** PointWave API - Updated Credentials Required

---

Dear Kobopoint Development Team,

Thank you for your patience while we investigated the "Invalid credentials" error you reported.

## Issue Identified

The credentials you're currently using are outdated. Our system shows that API credentials were regenerated on **February 17, 2026**, which invalidated the previous set of credentials.

## Current Valid Credentials

Please update your integration to use these **CURRENT** credentials:

```
Base URL: https://app.pointwave.ng/api/gateway
Business ID: 3450968aa027e86e3ff5b0169dc17edd7694a846
API Key: 2aa89c1398c330d6ed16198dc1e872f572c02d07
Secret Key: d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c
```

## Authentication Headers

```
Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c
X-API-Key: 2aa89c1398c330d6ed16198dc1e872f572c02d07
X-Business-ID: 3450968aa027e86e3ff5b0169dc17edd7694a846
Content-Type: application/json
Accept: application/json
```

## Test Command

Run this command to verify the credentials work:

```bash
curl -X GET "https://app.pointwave.ng/api/gateway/banks" \
  -H "Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c" \
  -H "X-API-Key: 2aa89c1398c330d6ed16198dc1e872f572c02d07" \
  -H "X-Business-ID: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "status": true,
  "banks": [
    {
      "code": "100033",
      "name": "PalmPay"
    },
    {
      "code": "000001",
      "name": "Sterling Bank"
    },
    ...
  ]
}
```

## What Changed

### Old Credentials (No Longer Valid):
```
API Key: 7db8dbb3991382487a1fc388a05d96a7139d92ba ❌
Secret Key: d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c ✅
Business ID: 3450968aa027e86e3ff5b0169dc17edd7694a846 ✅
```

### New Credentials (Current):
```
API Key: 2aa89c1398c330d6ed16198dc1e872f572c02d07 ✅
Secret Key: d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c ✅
Business ID: 3450968aa027e86e3ff5b0169dc17edd7694a846 ✅
```

**Only the API Key changed.** The Secret Key and Business ID remain the same.

## Where to Find Latest Credentials

You can always find your current API credentials by:

1. Logging in to https://app.pointwave.ng
2. Going to **"Developer API"** in the sidebar
3. Copying the credentials shown there

The credentials displayed in the dashboard are always current and pulled directly from our database.

## Laravel/PHP Integration Example

Update your `.env` file:

```env
POINTWAVE_BASE_URL=https://app.pointwave.ng/api/gateway
POINTWAVE_BUSINESS_ID=3450968aa027e86e3ff5b0169dc17edd7694a846
POINTWAVE_API_KEY=2aa89c1398c330d6ed16198dc1e872f572c02d07
POINTWAVE_SECRET_KEY=d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c
```

Update your `config/services.php`:

```php
'pointwave' => [
    'base_url' => env('POINTWAVE_BASE_URL', 'https://app.pointwave.ng/api/gateway'),
    'business_id' => env('POINTWAVE_BUSINESS_ID'),
    'api_key' => env('POINTWAVE_API_KEY'),
    'secret_key' => env('POINTWAVE_SECRET_KEY'),
],
```

Your service class should use:

```php
$this->client = new Client([
    'base_uri' => config('services.pointwave.base_url'),
    'timeout' => 30,
    'headers' => [
        'Authorization' => 'Bearer ' . config('services.pointwave.secret_key'),
        'X-API-Key' => config('services.pointwave.api_key'),
        'X-Business-ID' => config('services.pointwave.business_id'),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ]
]);
```

## Complete Integration Guide

For a complete integration guide with all endpoints and examples, please refer to:

📄 **POINTPAY_COMPLETE_API_GUIDE.md** (attached or available in your dashboard)

This guide includes:
- ✅ All 10 API endpoints with full examples
- ✅ Webhook configuration and signature verification
- ✅ Error codes and handling
- ✅ Fees, limits, and settlement schedule
- ✅ Sandbox testing credentials
- ✅ Laravel/PHP integration examples

## About the /balance Endpoint Error

The internal server error you encountered on the `/balance` endpoint has been fixed. It will work correctly with the updated credentials.

## Next Steps

1. ✅ Update your `.env` file with the new API Key
2. ✅ Clear your config cache: `php artisan config:clear`
3. ✅ Test the authentication with the curl command above
4. ✅ Test your integration end-to-end
5. ✅ Deploy to production

## Support

If you encounter any issues after updating the credentials:

- **Email:** support@pointwave.ng
- **Response Time:** Within 24 hours
- **Your Account Manager:** Abubakar Jamilu (abokisub@gmail.com)

## Important Note for Future

⚠️ **Always get your API credentials from the dashboard, not from documentation or old emails.**

When credentials are regenerated in the dashboard, old credentials stop working immediately. The dashboard always shows the current, valid credentials.

---

We apologize for any confusion this may have caused. Your integration should now work perfectly with the updated credentials.

Please confirm once you've tested and verified the integration is working.

Best regards,

**PointWave Technical Team**  
support@pointwave.ng  
https://app.pointwave.ng

---

## Attachments

- POINTPAY_COMPLETE_API_GUIDE.md - Complete API integration guide
- CREDENTIAL_MANAGEMENT_GUIDE.md - How credentials work and best practices

---

**This email contains sensitive API credentials. Please store them securely and do not share them publicly.**
