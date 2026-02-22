# PointWave Webhook Signature Fix - APPLIED

## THE PROBLEM

Webhooks from PointWave to Kobopoint were failing with "Invalid signature" errors because:

1. **Different JSON encoding**: PointWave was using `Http::post($url, $array)` which Laravel encodes automatically, but the signature was computed on a different JSON string
2. **Prefix mismatch**: PointWave sent `sha256=<hash>` but Kobopoint expected just `<hash>`
3. **Payload mismatch**: The JSON sent didn't match the JSON used for signature calculation

## THE FIX APPLIED

### File: `app/Jobs/SendOutgoingWebhook.php`

**BEFORE:**
```php
$payload = json_encode($this->webhookLog->payload);
$signature = hash_hmac('sha256', $payload, $secret);

$response = Http::timeout(15)
    ->withHeaders([
        'X-PointWave-Signature' => 'sha256=' . $signature,
    ])
    ->post($this->webhookLog->webhook_url, $this->webhookLog->payload);
```

**AFTER:**
```php
// Encode payload with consistent formatting
$payloadJson = json_encode($this->webhookLog->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// Compute signature on the exact JSON string we're sending
$signature = hash_hmac('sha256', $payloadJson, $secret);

// Send the webhook with raw JSON body
$response = Http::timeout(15)
    ->withHeaders([
        'X-PointWave-Signature' => $signature,  // No prefix
    ])
    ->withBody($payloadJson, 'application/json')
    ->post($this->webhookLog->webhook_url);
```

## WHAT CHANGED

1. ✅ **Consistent JSON encoding**: Using `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE` flags
2. ✅ **Same payload for signature and sending**: Computing signature on the exact JSON string being sent
3. ✅ **No prefix**: Sending signature as raw hash (no `sha256=` prefix)
4. ✅ **Raw body**: Using `withBody()` instead of passing array to `post()`

## DEPLOYMENT STEPS

### 1. Commit and Push Changes
```bash
git add app/Jobs/SendOutgoingWebhook.php
git commit -m "Fix webhook signature mismatch - use consistent JSON encoding"
git push origin main
```

### 2. Deploy to PointWave Server
```bash
cd /path/to/app.pointwave.ng
git pull origin main
php artisan config:clear
php artisan cache:clear
```

### 3. Verify Webhook Secret Matches
```bash
# On PointWave server
php compare_webhook_secrets.php

# On Kobopoint server
grep POINTWAVE_WEBHOOK_SECRET .env
```

Make sure both show the EXACT same value.

### 4. Retry Failed Webhooks
```bash
# On PointWave server
php retry_failed_company_webhooks.php
```

### 5. Test with New Deposit
Make a test deposit to Kobopoint's virtual account and verify:
- ✅ PointWave receives PalmPay webhook
- ✅ PointWave credits company user (ID 42)
- ✅ PointWave sends webhook to Kobopoint
- ✅ Kobopoint receives webhook with HTTP 200
- ✅ Kobopoint customer balance updates

## KOBOPOINT SIDE REQUIREMENTS

Kobopoint's webhook handler must:

```php
// Get raw payload exactly as sent
$payload = $request->getContent();

// Get signature (no prefix)
$receivedSignature = $request->header('X-PointWave-Signature');

// Get secret from .env
$secret = env('POINTWAVE_WEBHOOK_SECRET');

// Compute expected signature on raw payload
$expectedSignature = hash_hmac('sha256', $payload, $secret);

// Compare using timing-safe comparison
if (!hash_equals($expectedSignature, $receivedSignature)) {
    Log::warning('Invalid PointWave webhook signature', [
        'received' => substr($receivedSignature, 0, 20) . '...',
        'expected' => substr($expectedSignature, 0, 20) . '...'
    ]);
    return response()->json(['error' => 'Invalid signature'], 401);
}

// Signature valid - process webhook
$data = json_decode($payload, true);
// ... process payment notification
```

## VERIFICATION

After deployment, check logs:

### PointWave Logs (should show):
```
[2026-02-22 XX:XX:XX] production.INFO: Client Payment Detected
[2026-02-22 XX:XX:XX] production.INFO: Virtual Account Credited
[2026-02-22 XX:XX:XX] production.INFO: Webhook sent successfully (HTTP 200)
```

### Kobopoint Logs (should show):
```
[2026-02-22 XX:XX:XX] production.INFO: PointWave webhook received
[2026-02-22 XX:XX:XX] production.INFO: Processing PointWave webhook
[2026-02-22 XX:XX:XX] production.INFO: Customer balance updated
```

### NOT:
```
❌ [WEBHOOK_DLQ]: Webhook permanently failed
❌ Invalid PointWave webhook signature
❌ HTTP 401 errors
```

## MONITORING

Check webhook status:
```bash
php check_company_webhook_logs.php
```

Should show:
```
✅ delivered: 10
```

NOT:
```
❌ delivery_failed: 10
```

## ROLLBACK (if needed)

If this causes issues, rollback:
```bash
git revert HEAD
git push origin main
# Then deploy
```

## NEXT STEPS

1. Deploy this fix to PointWave
2. Verify webhook secret matches on both systems
3. Retry failed webhooks
4. Test with new deposit
5. Monitor logs for success

---

**Status**: ✅ FIX APPLIED - READY FOR DEPLOYMENT
**Date**: 2026-02-22
**Files Modified**: `app/Jobs/SendOutgoingWebhook.php`
