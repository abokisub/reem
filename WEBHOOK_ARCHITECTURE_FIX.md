# Webhook Architecture Fix - Professional Implementation

**Date:** February 22, 2026  
**Status:** 🔧 In Progress

## Current State Analysis

### ✅ What's Working
- Transactions and webhooks are properly separated
- Transaction status is NOT affected by webhook delivery
- Retry logic with exponential backoff exists
- DLQ (Dead Letter Queue) after 5 attempts
- WebhookEvent model tracks delivery status

### ❌ Critical Issues Found

1. **No Webhook Signature** - Companies cannot verify webhook authenticity
2. **Missing Security Headers** - No HMAC-SHA256 signature
3. **Incomplete Payload** - Missing important transaction details
4. **No Signature Documentation** - Companies don't know how to verify

## Industry Standard Implementation (Stripe/Paystack Model)

### Webhook Signature Flow

```
1. PointWave prepares payload (JSON)
2. PointWave computes: HMAC_SHA256(payload, company_webhook_secret)
3. PointWave sends:
   Headers:
     X-PointWave-Signature: <computed_signature>
     X-PointWave-Event-ID: <event_id>
     X-PointWave-Event-Type: <event_type>
   Body: <payload>
4. Company receives and verifies:
   $computed = hash_hmac('sha256', $payload, $their_secret);
   if ($computed !== $header_signature) reject();
5. Company returns 200 OK immediately
```

## Implementation Plan

### Phase 1: Add Webhook Signature (CRITICAL)

**File:** `app/Services/Webhook/OutgoingWebhookService.php`

Add signature generation:
```php
private function generateSignature(array $payload, string $secret): string
{
    $jsonPayload = json_encode($payload);
    return hash_hmac('sha256', $jsonPayload, $secret);
}
```

Update deliver() method to include signature header:
```php
$signature = $this->generateSignature($webhookEvent->payload, $company->webhook_secret);

$response = Http::timeout(10)
    ->withHeaders([
        'Content-Type' => 'application/json',
        'X-PointWave-Signature' => $signature,
        'X-PointWave-Event-ID' => $webhookEvent->event_id,
        'X-PointWave-Event-Type' => $webhookEvent->event_type,
        'X-PointWave-Timestamp' => now()->timestamp,
    ])
    ->post($webhookEvent->endpoint_url, $webhookEvent->payload);
```

### Phase 2: Enhanced Payload Structure

Current payload is incomplete. Should include:

```json
{
  "event": "transaction.success",
  "event_id": "evt_...",
  "timestamp": "2026-02-22T10:32:19Z",
  "data": {
    "transaction_id": "txn_699acd23ef3cd59017",
    "reference": "MI2025503875632607232",
    "session_id": "100004260222093210152958479687",
    "type": "virtual_account_deposit",
    "amount": 10000,
    "fee": 60,
    "net_amount": 9940,
    "currency": "NGN",
    "status": "success",
    "settlement_status": "pending",
    "customer": {
      "account_name": "ABOKI TELECOMMUNICATION SERVICES",
      "account_number": "7040540018",
      "bank_name": "OPAY"
    },
    "virtual_account": {
      "account_number": "6611630442",
      "account_name": "kobopoint-Kobo Point(PointWave)"
    },
    "created_at": "2026-02-22T10:32:19Z",
    "updated_at": "2026-02-22T10:32:19Z"
  }
}
```

### Phase 3: Company Webhook Implementation Guide

Create documentation for companies on how to implement webhook endpoint:

**File:** `resources/views/docs/webhook-implementation.blade.php`

```php
// Example webhook handler
public function handleWebhook(Request $request)
{
    // Step 1: Get signature from header
    $signature = $request->header('X-PointWave-Signature');
    $payload = $request->getContent();
    
    // Step 2: Verify signature
    $secret = config('services.pointwave.webhook_secret');
    $computed = hash_hmac('sha256', $payload, $secret);
    
    if (!hash_equals($computed, $signature)) {
        return response()->json(['error' => 'Invalid signature'], 400);
    }
    
    // Step 3: Parse payload
    $data = json_decode($payload, true);
    
    // Step 4: Queue processing (don't block response)
    dispatch(new ProcessPointWaveWebhook($data));
    
    // Step 5: Return 200 immediately
    return response()->json(['message' => 'Received'], 200);
}
```

### Phase 4: Webhook Retry Service

**File:** `app/Console/Commands/RetryFailedWebhooks.php`

```php
public function handle()
{
    $webhooks = WebhookEvent::where('status', 'failed')
        ->where('direction', 'outgoing')
        ->where('attempt_count', '<', 5)
        ->where('next_retry_at', '<=', now())
        ->get();
    
    foreach ($webhooks as $webhook) {
        app(OutgoingWebhookService::class)->deliver($webhook);
    }
}
```

Schedule in `app/Console/Kernel.php`:
```php
$schedule->command('webhooks:retry')->everyMinute();
```

## Deployment Steps

1. ✅ Fix webhook secret encryption (DONE)
2. 🔧 Add signature generation to OutgoingWebhookService
3. 🔧 Update payload structure
4. 🔧 Create webhook implementation guide
5. 🔧 Deploy retry command
6. 📧 Notify all companies about webhook signature requirement
7. 🧪 Test with Kobopoint

## Testing Checklist

- [ ] Webhook signature is generated correctly
- [ ] Company can verify signature
- [ ] Retry logic works (1min, 5min, 15min, 1hr, 6hr)
- [ ] DLQ moves webhooks after 5 failures
- [ ] Transaction status remains SUCCESS even if webhook fails
- [ ] Webhook logs show detailed error messages

## Important Notes

### Transaction vs Webhook Status

```
Transaction Status: SUCCESS (NEVER changes due to webhook failure)
Webhook Status: pending → delivered/failed → DLQ
```

These are COMPLETELY SEPARATE.

### Webhook Secret Management

- ❌ NEVER regenerate automatically
- ✅ Allow manual rotation with overlap period
- ✅ Store encrypted in database
- ✅ Provide to company via secure channel

### Company Responsibilities

1. Implement webhook endpoint
2. Verify signature
3. Return 200 OK immediately
4. Process asynchronously
5. Handle idempotency (same event_id)

## Kobopoint Specific Issue

Current problem:
- Webhook URL: `https://app.kobopoint.com/webhooks/pointwave`
- Status: Failing (moved to DLQ)
- Reason: Likely no signature verification or endpoint not ready

Action needed:
1. Kobopoint must implement webhook handler
2. Verify signature using their webhook_secret
3. Return 200 OK
4. We retry failed webhooks automatically

## References

- Stripe Webhooks: https://stripe.com/docs/webhooks
- Paystack Webhooks: https://paystack.com/docs/payments/webhooks
- Industry Best Practices: HMAC-SHA256 signature verification
