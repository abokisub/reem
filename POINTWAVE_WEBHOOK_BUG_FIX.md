# PointWave Webhook Signature Bug - FIXED

## The Real Problem

PointWave was sending webhook signatures calculated with the SERIALIZED version of the webhook secret instead of the plain text secret.

### Root Cause

Laravel's `encrypted` cast on the `webhook_secret` column was returning the serialized PHP format:
```
s:70:"whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24";
```

Instead of just the plain string:
```
whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24
```

### Evidence

From Amtpay's logs:
- PointWave sent: `a2ddfe1723e6dfdccd79c0e4b3572be2b2f731deaac2853bc0e1dcaa585eb26c`
- Amtpay calculated: `3411cd7a9c6cebb47ce6faa6b3cddf83019672caf3d4b2665373a23deadce6e1`

Test results:
- Signature with plain secret: `3411cd7a9c6cebb47ce6faa6b3cddf83019672caf3d4b2665373a23deadce6e1` ✅ Matches Amtpay
- Signature with serialized secret: `a2ddfe1723e6dfdccd79c0e4b3572be2b2f731deaac2853bc0e1dcaa585eb26c` ✅ Matches PointWave

This proves PointWave was using the serialized format.

## The Fix

### Files Modified

1. `app/Services/Webhook/OutgoingWebhookService.php`
2. `app/Http/Controllers/API/TransactionController.php`

### Changes Made

Added unserialization check before using webhook_secret for HMAC:

```php
// Get webhook secret and ensure it's plain string (not serialized)
$webhookSecret = $company->webhook_secret;
if (is_string($webhookSecret) && (strpos($webhookSecret, 's:') === 0 || strpos($webhookSecret, 'a:') === 0)) {
    $webhookSecret = unserialize($webhookSecret);
}

// Now use the plain string for HMAC
$signature = hash_hmac('sha256', $payload, $webhookSecret);
```

## Impact

### Before Fix
- All companies receiving webhooks had signature mismatches
- Companies had to bypass signature verification (security risk)
- Amtpay and potentially other clients affected

### After Fix
- Webhook signatures will match correctly
- Companies can enable proper signature verification
- Security vulnerability eliminated

## Deployment

### Steps
1. Deploy the fixed code to production
2. Test with Amtpay (they should see signatures match now)
3. Have Amtpay remove their bypass code
4. Monitor webhook logs for any other companies with issues

### Testing
After deployment, Amtpay should:
1. Keep their current code (using `$request->getContent()`)
2. Remove the "BYPASSING signature check" code
3. Test with a real transaction
4. Verify logs show "webhook verified successfully"

## Why This Happened

The `encrypted` cast in Laravel sometimes returns serialized values when the data was stored in a certain way. The `CompanyController::getCredentials()` method properly unserializes before returning to clients, but the webhook sending code didn't have this check.

## Prevention

All future code that uses `webhook_secret` for HMAC should include the unserialization check, or we should create a helper method:

```php
// Helper method to add to Company model
public function getPlainWebhookSecret(): string
{
    $secret = $this->webhook_secret;
    if (is_string($secret) && (strpos($secret, 's:') === 0 || strpos($secret, 'a:') === 0)) {
        return unserialize($secret);
    }
    return $secret;
}
```

## Status

✅ Bug identified
✅ Fix implemented
⏳ Awaiting deployment
⏳ Awaiting Amtpay confirmation
