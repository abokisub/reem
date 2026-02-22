# Kobopoint Webhook Signature Verification Fix

## The Problem

Your logs show:
```
Invalid PointWave webhook signature
received_signature="sha256=8a72b3c0cfa97..."
```

You're expecting the signature in format `sha256=<hash>`, but we're sending it as just the hash value in the `X-PointWave-Signature` header.

## The Solution

Update your webhook handler to read the signature from the correct header.

### Current (WRONG) Implementation

```php
// You're probably doing this:
$receivedSignature = $request->header('X-Signature'); // Wrong header
// or
$receivedSignature = 'sha256=' . $request->header('X-PointWave-Signature'); // Adding prefix
```

### Correct Implementation

```php
// Get the raw JSON payload
$payload = $request->getContent();

// Get signature from header (no prefix needed)
$receivedSignature = $request->header('X-PointWave-Signature');

// Get webhook secret from .env
$webhookSecret = config('services.pointwave.webhook_secret');
// or
$webhookSecret = env('POINTWAVE_WEBHOOK_SECRET');

// Compute expected signature
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

// Compare signatures
if (!hash_equals($expectedSignature, $receivedSignature)) {
    Log::warning('Invalid PointWave webhook signature', [
        'ip' => $request->ip(),
        'url' => $request->url(),
        'received_signature' => $receivedSignature,
        'expected_signature' => $expectedSignature,
        'payload_preview' => substr($payload, 0, 100)
    ]);
    
    return response()->json(['error' => 'Invalid signature'], 401);
}

// Signature is valid, process the webhook
$data = json_decode($payload, true);

// Process transaction...
```

## Complete Webhook Handler Example

```php
<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PointWaveWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Step 1: Get raw payload
        $payload = $request->getContent();
        
        // Step 2: Verify signature
        $receivedSignature = $request->header('X-PointWave-Signature');
        $webhookSecret = env('POINTWAVE_WEBHOOK_SECRET');
        
        if (!$receivedSignature) {
            Log::warning('PointWave webhook missing signature', [
                'ip' => $request->ip(),
                'url' => $request->url()
            ]);
            return response()->json(['error' => 'Missing signature'], 401);
        }
        
        // Compute expected signature
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        
        // Compare using hash_equals (timing-safe comparison)
        if (!hash_equals($expectedSignature, $receivedSignature)) {
            Log::warning('Invalid PointWave webhook signature', [
                'ip' => $request->ip(),
                'url' => $request->url(),
                'received' => $receivedSignature,
                'expected' => $expectedSignature
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        // Step 3: Parse payload
        $data = json_decode($payload, true);
        
        if (!$data) {
            Log::error('Invalid JSON payload from PointWave');
            return response()->json(['error' => 'Invalid JSON'], 400);
        }
        
        // Step 4: Get event details
        $eventType = $data['event'] ?? null;
        $eventId = $request->header('X-PointWave-Event-ID');
        $timestamp = $request->header('X-PointWave-Timestamp');
        
        // Step 5: Check for duplicate events (idempotency)
        if ($this->isDuplicateEvent($eventId)) {
            Log::info('Duplicate PointWave webhook event', ['event_id' => $eventId]);
            return response()->json(['message' => 'Event already processed'], 200);
        }
        
        // Step 6: Return 200 immediately (IMPORTANT!)
        // Queue the actual processing
        dispatch(new ProcessPointWaveWebhook($data, $eventId));
        
        return response()->json(['message' => 'Webhook received'], 200);
    }
    
    private function isDuplicateEvent(string $eventId): bool
    {
        // Check if this event ID was already processed
        return \DB::table('webhook_events')
            ->where('event_id', $eventId)
            ->exists();
    }
}
```

## Job for Processing (Queue)

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPointWaveWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $eventId;

    public function __construct(array $data, string $eventId)
    {
        $this->data = $data;
        $this->eventId = $eventId;
    }

    public function handle()
    {
        $eventType = $this->data['event'];
        
        Log::info('Processing PointWave webhook', [
            'event_id' => $this->eventId,
            'event_type' => $eventType
        ]);
        
        switch ($eventType) {
            case 'payment.success':
            case 'transaction.success':
                $this->handlePaymentSuccess();
                break;
                
            case 'payment.failed':
            case 'transaction.failed':
                $this->handlePaymentFailed();
                break;
                
            default:
                Log::warning('Unknown PointWave event type', [
                    'event_type' => $eventType
                ]);
        }
    }
    
    private function handlePaymentSuccess()
    {
        $transactionData = $this->data['data'];
        
        // Extract transaction details
        $transactionId = $transactionData['transaction_id'];
        $amount = $transactionData['amount'];
        $fee = $transactionData['fee'];
        $netAmount = $transactionData['net_amount'];
        $reference = $transactionData['reference'];
        
        // Credit user wallet
        $user = \App\Models\User::where('pointwave_customer_id', $transactionData['customer']['id'])->first();
        
        if ($user) {
            $user->wallet()->increment('balance', $netAmount);
            
            // Create transaction record
            \App\Models\Transaction::create([
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'reference' => $reference,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'type' => 'deposit',
                'status' => 'success',
                'provider' => 'pointwave',
            ]);
            
            Log::info('User wallet credited from PointWave', [
                'user_id' => $user->id,
                'amount' => $netAmount,
                'transaction_id' => $transactionId
            ]);
        }
    }
    
    private function handlePaymentFailed()
    {
        // Handle failed payment
        Log::warning('PointWave payment failed', $this->data);
    }
}
```

## Headers We Send

```
X-PointWave-Signature: <hmac_sha256_hash>
X-PointWave-Event-ID: <uuid>
X-PointWave-Event-Type: payment.success
X-PointWave-Timestamp: <unix_timestamp>
Content-Type: application/json
```

## Payload Format

```json
{
  "event": "payment.success",
  "event_id": "550e8400-e29b-41d4-a716-446655440000",
  "timestamp": "2026-02-22T11:20:56.000000Z",
  "data": {
    "transaction_id": "txn_699ad88800d6778976",
    "reference": "MI2025516106948780032",
    "session_id": "100004260222102046152962532224",
    "type": "virtual_account_deposit",
    "amount": "100.00",
    "fee": "0.60",
    "net_amount": "99.40",
    "currency": "NGN",
    "status": "success",
    "customer": {
      "id": "42",
      "name": "ABOKI TELECOMMUNICATION SERVICES",
      "account_number": "6611630442",
      "bank_name": "PointWave Virtual Account"
    },
    "virtual_account": {
      "account_number": "6611630442",
      "account_name": "kobopoint-Kobo Point(PointWave)",
      "bank_name": "PalmPay"
    },
    "created_at": "2026-02-22T11:20:56Z"
  }
}
```

## Testing Your Implementation

1. Update your webhook handler code
2. Update `.env` with correct webhook secret:
   ```
   POINTWAVE_WEBHOOK_SECRET=whsec_aad2319b0eb134b2f598ad63d4c2a7a2a4b054f42781b6599b92a7cc1a97fc68
   ```
3. Clear config cache: `php artisan config:clear`
4. Restart your application
5. Make a test deposit to trigger a webhook

## Debugging

Add this to your webhook handler to debug:

```php
Log::debug('PointWave Webhook Debug', [
    'headers' => $request->headers->all(),
    'payload' => $payload,
    'received_signature' => $receivedSignature,
    'expected_signature' => $expectedSignature,
    'webhook_secret' => substr($webhookSecret, 0, 10) . '...' // First 10 chars only
]);
```

## Common Mistakes

1. ❌ Adding `sha256=` prefix to signature
2. ❌ Using wrong header name
3. ❌ Not using raw payload for signature verification
4. ❌ Using wrong webhook secret
5. ❌ Not returning 200 immediately
6. ❌ Processing webhook synchronously (should queue)

## Correct Flow

1. Receive webhook → Verify signature → Return 200 immediately
2. Queue job → Process transaction → Credit wallet
3. If processing fails → Job retries automatically
4. Webhook delivery is separate from transaction processing

This ensures fast response times and reliable processing.
