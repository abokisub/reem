# Webhook Signature Issue - Resolution Summary

## Problem
Amtpay's webhooks were failing signature verification, causing them to bypass security checks.

## Root Cause
Amtpay was using `json_encode($request->all())` to get the payload for signature calculation, which creates different JSON formatting than what PointWave sends.

## Investigation Results

### Webhook Secret Verification
- Amtpay's .env secret: `whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24`
- PointWave database secret (decrypted): `whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24`
- ✅ **Secrets match - Amtpay has the correct webhook secret**

### Signature Test
Using test payload from Amtpay's logs:
- PointWave sent signature: `978ad8405b2f656b19d9f6da39512052618733d75e7d99e5874d273b1fc3ad39`
- Amtpay calculated (with json_encode): `105e7b5b3a3cfc708338ac1066e906d66e1003e33bc32530732d6386c94cf799`
- Correct calculation (with getContent): `978ad8405b2f656b19d9f6da39512052618733d75e7d99e5874d273b1fc3ad39`
- ✅ **Using getContent() produces matching signature**

## The Fix

### For Amtpay (Client Side)

Change ONE line in their webhook handler:

```php
// WRONG (current code):
$payload = json_encode($request->all());

// CORRECT (fixed code):
$payload = $request->getContent();
```

### Why This Works

- `$request->all()` parses the JSON, then `json_encode()` creates NEW JSON
- JSON encoding can change key order, spacing, and formatting
- `$request->getContent()` gets the EXACT raw bytes that PointWave sent
- HMAC signatures only match when calculated on identical bytes

### Complete Fixed Code for Amtpay

```php
<?php

// Get webhook secret from .env
$webhookSecret = env('POINTWAVE_WEBHOOK_SECRET');

// Get RAW request body (THE FIX)
$payload = $request->getContent();

// Get signature from header  
$receivedSignature = $request->header('X-PointWave-Signature');

if (!$receivedSignature) {
    return response()->json(['error' => 'Missing signature'], 401);
}

// Calculate expected signature
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

// Verify signature (REMOVE BYPASS CODE)
if (!hash_equals($expectedSignature, $receivedSignature)) {
    Log::warning('Invalid PointWave webhook signature');
    return response()->json(['error' => 'Invalid signature'], 401);
}

// Signature valid - parse JSON
$data = json_decode($payload, true);

// Process webhook...
```

## Security Note

The bypass code that Amtpay added is a **CRITICAL SECURITY VULNERABILITY**. Anyone can send fake payment notifications to their system while the bypass is active. This must be removed immediately after fixing the signature calculation.

## Files Created

- `verify_amtpay_webhook_secret.php` - Verifies webhook secret in database
- `get_decrypted_webhook_secret.php` - Gets decrypted webhook secret
- `AMTPAY_FINAL_FIX.txt` - Simple instructions for Amtpay
- `AMTPAY_CRITICAL_SECURITY_FIX.md` - Detailed fix instructions
- `WEBHOOK_ISSUE_RESOLUTION.md` - This summary

## Status

- ✅ Root cause identified
- ✅ Webhook secret verified correct
- ✅ Fix identified and documented
- ⏳ Waiting for Amtpay to implement fix
- ⏳ Waiting for Amtpay to remove bypass code

## Next Steps

1. Send `AMTPAY_FINAL_FIX.txt` to Amtpay
2. Have them fix the one line of code
3. Have them remove the bypass
4. Test with real transaction
5. Verify in logs that signatures match
