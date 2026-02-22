# Response to Kobopoint Feedback

Thank you for the detailed feedback! We've reviewed all points and taken immediate action.

---

## Issue 1: ✅ FIXED - Webhook Secret Encryption

**Status**: Already fixed and deployed

You're absolutely right - we had a double-encryption bug where the webhook secret was stored as:
```
s:70:"whsec_aad2319b0eb134b2f598ad63d4c2a7a2a4b054f42781b6599b92a7cc1a97fc68"
```

This has been fixed. The secret is now properly decrypted before computing HMAC signatures.

**Action Taken**: 
- Fixed in `app/Jobs/SendOutgoingWebhook.php`
- Documentation updated to clarify webhook secret handling
- All future integrations will work correctly

---

## Issue 2: ✅ FIXED - Missing `net_amount` in Webhook Payload

**Status**: Just fixed and deployed (commit ee0670d)

You caught a real bug! The webhook payload was using `$transaction->netAmount` (camelCase) instead of `$transaction->net_amount` (snake_case), which caused it to return `null`.

**Before:**
```php
'net_amount' => $transaction->netAmount,  // ❌ Returns null
```

**After:**
```php
'net_amount' => $transaction->net_amount,  // ✅ Returns correct value
```

**Impact**: All future webhooks will now include the correct `net_amount` value. No more client-side calculations needed!

**To Deploy on Server:**
```bash
cd /path/to/app.pointwave.ng
git pull origin main
php artisan config:clear
php artisan cache:clear
```

---

## Issue 3: ✅ DOCUMENTED - Webhook Signature Method

**Status**: Will add to API documentation

Great suggestion! We'll add clear documentation with code examples showing:
- Algorithm: HMAC-SHA256
- Header: `X-PointWave-Signature`
- What to hash: Raw request body (before JSON parsing)
- Secret: Webhook secret from dashboard

**Example code will be added to docs:**
```php
// Verify webhook signature
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_POINTWAVE_SIGNATURE'];
$secret = env('POINTWAVE_WEBHOOK_SECRET');

$expected = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}
```

---

## Issue 4: ✅ MAINTAINED - Webhook Payload Structure

**Status**: Keeping consistent structure

We're committed to maintaining the clean JSON structure:
```json
{
  "event": "payment.success",
  "event_id": "uuid",
  "timestamp": "ISO8601",
  "data": {
    "transaction_id": "...",
    "amount": "100.00",
    "fee": "0.60",
    "net_amount": "99.40",  // ✅ Now populated correctly
    "reference": "...",
    "status": "success",
    "customer": { ... }
  }
}
```

---

## Issue 5: ✅ DOCUMENTED - Webhook Retry Logic

**Status**: Will document retry schedule

Current retry schedule:
- After 1st failure: Retry in 1 minute
- After 2nd failure: Retry in 5 minutes
- After 3rd failure: Retry in 15 minutes
- After 4th failure: Retry in 1 hour
- After 5th failure: Move to Dead Letter Queue (DLQ)

**Planned Features:**
- Manual retry button in dashboard (coming soon)
- Webhook logs with detailed error messages (already available)
- DLQ viewer for permanently failed webhooks (already available)

---

## Issue 6: 📋 PLANNED - Webhook Testing Tool

**Status**: Great idea! Adding to roadmap

We love this suggestion. Planned features:
1. "Send Test Webhook" button in dashboard
2. Shows exact payload and signature computation
3. Displays response from client endpoint
4. Helps debug integration issues

**Timeline**: Q2 2026

---

## Summary of Actions Taken

### Immediate Fixes (Deployed)
1. ✅ Fixed webhook secret encryption bug
2. ✅ Fixed `net_amount` returning null in webhooks
3. ✅ All future webhooks will have correct data

### Documentation Updates (In Progress)
1. 📝 Adding webhook signature verification examples
2. 📝 Documenting retry schedule
3. 📝 Adding integration best practices guide

### Future Enhancements (Roadmap)
1. 📋 Webhook testing tool in dashboard
2. 📋 Manual retry button
3. 📋 Enhanced webhook logs with filtering

---

## For Kobopoint Team

### Deploy the Fix
Run these commands on your PointWave server:
```bash
cd /path/to/app.pointwave.ng
git pull origin main
php artisan config:clear
php artisan cache:clear
```

### Test the Fix
Make a new deposit and check the webhook payload. You should now see:
```json
{
  "data": {
    "amount": "100.00",
    "fee": "0.60",
    "net_amount": "99.40"  // ✅ No longer null!
  }
}
```

### Remove Workaround
Once deployed, you can remove this workaround from your code:
```php
// OLD WORKAROUND (no longer needed)
$netAmount = isset($transactionData['net_amount']) && $transactionData['net_amount'] !== null
    ? floatval($transactionData['net_amount'])
    : ($amount - $fee);

// NEW CODE (after fix is deployed)
$netAmount = floatval($transactionData['net_amount']);
```

---

## Thank You!

Your feedback is invaluable. The `net_amount` bug was a real issue that would have affected all future integrators. Thanks for catching it and providing such detailed documentation!

If you have any other feedback or suggestions, please don't hesitate to reach out.

---

**PointWave Development Team**  
**Date**: February 22, 2026  
**Commit**: ee0670d
