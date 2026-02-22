# Kobopoint Webhook Implementation Guide

**Date:** February 22, 2026  
**Endpoint:** `https://app.kobopoint.com/webhooks/pointwave`  
**Status:** 🔴 Currently Failing → Need Implementation

## Problem Summary

PointWave is successfully:
- ✅ Receiving deposits from PalmPay
- ✅ Crediting your virtual account
- ✅ Queuing settlements
- ✅ Sending webhooks to your endpoint

But your endpoint is:
- ❌ Not responding with 200 OK
- ❌ Webhooks going to DLQ (Dead Letter Queue)
- ❌ Your users not seeing deposits

## Webhook Signature Verification (CRITICAL)

PointWave now sends webhooks with HMAC-SHA256 signature for security.

### Headers You'll Receive

```
Content-Type: application/json
X-PointWave-Signature: <hmac_sha256_signature>
X-PointWave-Event-ID: <unique_event_id>
X-PointWave-Event-Type: transaction.success
X-PointWave-Timestamp: <unix_timestamp>
```

### Payload Structure

```json
{
  "event": "transaction.success",
  "event_id": "evt_abc123...",
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
      "name": "ABOKI TELECOMMUNICATION SERVICES",
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

## Implementation Steps

### Step 1: Get Your Webhook Secret

Your webhook secret is stored in PointWave database (encrypted).

To retrieve it:
1. Login to PointWave dashboard
2. Go to Developer API section
3. Copy your "Webhook Secret (Live)"

It looks like: `whsec_...` (64 characters)

### Step 2: Implement Webhook Handler

```php
<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PointWaveWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Step 1: Get signature from header
        $signature = $request->header('X-PointWave-Signature');
        $eventId = $request->header('X-PointWave-Event-ID');
        $eventType = $request->header('X-PointWave-Event-Type');
        
        // Step 2: Get raw payload
        $payload = $request->getContent();
        
        // Step 3: Verify signature
        if (!$this->verifySignature($payload, $signature)) {
            Log::error('PointWave webhook signature verification failed', [
                'event_id' => $eventId,
                'signature' => $signature
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 400);
        }
        
        // Step 4: Parse payload
        $data = json_decode($payload, true);
        
        // Step 5: Check for duplicate (idempotency)
        if ($this->isDuplicate($eventId)) {
            Log::info('Duplicate webhook received', ['event_id' => $eventId]);
            return response()->json(['message' => 'Already processed'], 200);
        }
        
        // Step 6: Queue processing (DON'T BLOCK RESPONSE)
        dispatch(new ProcessPointWaveWebhook($data));
        
        // Step 7: Return 200 OK IMMEDIATELY
        return response()->json(['message' => 'Received'], 200);
    }
    
    /**
     * Verify webhook signature
     */
    private function verifySignature(string $payload, string $signature): bool
    {
        // Get your webhook secret from config
        $secret = config('services.pointwave.webhook_secret');
        
        // Compute HMAC-SHA256
        $computed = hash_hmac('sha256', $payload, $secret);
        
        // Use hash_equals to prevent timing attacks
        return hash_equals($computed, $signature);
    }
    
    /**
     * Check if webhook already processed
     */
    private function isDuplicate(string $eventId): bool
    {
        return \DB::table('webhook_events')
            ->where('event_id', $eventId)
            ->exists();
    }
}
```

### Step 3: Create Background Job

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
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    public function handle()
    {
        $event = $this->data['event'];
        $transaction = $this->data['data'];
        
        // Log webhook event
        \DB::table('webhook_events')->insert([
            'event_id' => $this->data['event_id'],
            'event_type' => $event,
            'payload' => json_encode($this->data),
            'processed_at' => now(),
        ]);
        
        // Handle different event types
        switch ($event) {
            case 'transaction.success':
                $this->handleTransactionSuccess($transaction);
                break;
                
            case 'settlement.completed':
                $this->handleSettlementCompleted($transaction);
                break;
                
            default:
                Log::warning('Unknown webhook event type', ['event' => $event]);
        }
    }
    
    private function handleTransactionSuccess(array $transaction)
    {
        // Find user by virtual account number
        $virtualAccount = $transaction['virtual_account']['account_number'];
        $user = \App\Models\User::where('virtual_account_number', $virtualAccount)->first();
        
        if (!$user) {
            Log::error('User not found for virtual account', [
                'account_number' => $virtualAccount
            ]);
            return;
        }
        
        // Credit user wallet
        $user->wallet()->increment('balance', $transaction['net_amount']);
        
        // Create transaction record
        \App\Models\Transaction::create([
            'user_id' => $user->id,
            'reference' => $transaction['reference'],
            'transaction_id' => $transaction['transaction_id'],
            'type' => 'deposit',
            'amount' => $transaction['amount'],
            'fee' => $transaction['fee'],
            'net_amount' => $transaction['net_amount'],
            'status' => 'success',
            'provider' => 'pointwave',
            'metadata' => json_encode($transaction),
        ]);
        
        // Send notification to user
        $user->notify(new DepositSuccessNotification($transaction));
        
        Log::info('User wallet credited successfully', [
            'user_id' => $user->id,
            'amount' => $transaction['net_amount'],
            'reference' => $transaction['reference']
        ]);
    }
    
    private function handleSettlementCompleted(array $transaction)
    {
        // Handle settlement completion
        // Update transaction status, send notifications, etc.
    }
}
```

### Step 4: Add Route

```php
// routes/api.php

Route::post('/webhooks/pointwave', [PointWaveWebhookController::class, 'handle'])
    ->name('webhooks.pointwave');
```

### Step 5: Configure Webhook Secret

```php
// config/services.php

return [
    // ... other services
    
    'pointwave' => [
        'webhook_secret' => env('POINTWAVE_WEBHOOK_SECRET'),
    ],
];
```

```env
# .env

POINTWAVE_WEBHOOK_SECRET=whsec_your_actual_secret_here
```

## Testing Your Implementation

### Test 1: Signature Verification

```php
// Test script
$payload = '{"event":"transaction.success","data":{...}}';
$secret = 'whsec_your_secret';
$signature = hash_hmac('sha256', $payload, $secret);

echo "Signature: " . $signature . "\n";
```

### Test 2: Send Test Webhook

Ask PointWave to send a test webhook or trigger a small deposit.

### Test 3: Check Logs

```bash
tail -f storage/logs/laravel.log | grep PointWave
```

## Common Issues & Solutions

### Issue 1: Signature Verification Fails

**Cause:** Using wrong secret or modifying payload before verification

**Solution:**
- Use raw request body: `$request->getContent()`
- Don't parse JSON before verification
- Use exact secret from PointWave dashboard

### Issue 2: Timeout (504 Gateway Timeout)

**Cause:** Processing takes too long before returning 200

**Solution:**
- Return 200 OK immediately
- Queue heavy processing
- Don't wait for database writes

### Issue 3: Duplicate Processing

**Cause:** Webhook retries processing same transaction twice

**Solution:**
- Check `event_id` before processing
- Use database unique constraint
- Return 200 even for duplicates

## Webhook Retry Schedule

If your endpoint fails, PointWave will retry:

| Attempt | Delay |
|---------|-------|
| 1 | Immediate |
| 2 | +1 minute |
| 3 | +5 minutes |
| 4 | +15 minutes |
| 5 | +1 hour |
| 6+ | Dead Letter Queue |

After 5 failures, webhook moves to DLQ and stops retrying.

## Important Notes

1. **Always return 200 OK immediately** - Don't wait for processing
2. **Verify signature first** - Before any processing
3. **Handle idempotency** - Same event_id should not process twice
4. **Queue heavy work** - Database writes, API calls, notifications
5. **Log everything** - For debugging webhook issues

## Current Status

Your endpoint `https://app.kobopoint.com/webhooks/pointwave` is currently:
- ❌ Not responding with 200 OK
- ❌ Webhooks in DLQ

Once you implement the handler above:
- ✅ Webhooks will be verified
- ✅ Users will see deposits
- ✅ Automatic retries will work

## Need Help?

Contact PointWave support with:
- Your company ID: 4
- Webhook URL: https://app.kobopoint.com/webhooks/pointwave
- Error logs from your server
