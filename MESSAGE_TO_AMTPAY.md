# Message to Amtpay - Webhook Signature Fix

## Current Issue

Your webhook handler at `https://app.amtpay.com.ng/webhooks/pointwave` is correctly receiving webhooks from PointWave, but signature verification is failing.

**From your logs:**
- ✅ Header received correctly: `x-pointwave-signature: d69dafe51391ef8ec63ede3c120a32c315baa74797def1697f34a60a245a6295`
- ✅ Payload length: 519 bytes
- ❌ Signature mismatch: You're calculating `8f3724b628ca7e724799...` but PointWave sent `d69dafe51391ef8ec63e...`

## Root Cause

The signature mismatch means your webhook handler is NOT using the raw request body when calculating the signature. This is the most common mistake.

## The Fix

You MUST use the **raw request body** (the exact bytes received), not parsed/decoded JSON.

### ❌ WRONG - Don't Do This:

```php
// This will FAIL - don't use parsed data
$data = $request->all();
$payload = json_encode($data);  // ❌ This creates different JSON than what was sent
$signature = hash_hmac('sha256', $payload, $webhookSecret);
```

### ✅ CORRECT - Do This:

```php
// Use raw request body
$payload = $request->getContent();  // Laravel
// OR
$payload = file_get_contents('php://input');  // Plain PHP

// Get signature from header
$receivedSignature = $request->header('X-PointWave-Signature');

// Your webhook secret from .env
$webhookSecret = 'whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24';

// Calculate expected signature
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

// Verify using secure comparison
if (!hash_equals($expectedSignature, $receivedSignature)) {
    // Log for debugging
    Log::warning('Invalid signature', [
        'received' => $receivedSignature,
        'expected' => $expectedSignature,
        'payload_length' => strlen($payload),
        'payload_preview' => substr($payload, 0, 100)
    ]);
    
    return response()->json(['error' => 'Invalid signature'], 401);
}

// Signature valid - now you can parse the JSON
$data = json_decode($payload, true);
```

## Complete Working Example

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PointWaveWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Get webhook secret from .env
        $webhookSecret = env('POINTWAVE_WEBHOOK_SECRET');
        
        // 2. Get RAW request body (CRITICAL - must be raw!)
        $payload = $request->getContent();
        
        // 3. Get signature from header
        $receivedSignature = $request->header('X-PointWave-Signature');
        
        if (!$receivedSignature) {
            Log::warning('PointWave webhook missing signature');
            return response()->json(['error' => 'Missing signature'], 401);
        }
        
        // 4. Calculate expected signature using RAW body
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        
        // 5. Verify signature
        if (!hash_equals($expectedSignature, $receivedSignature)) {
            Log::warning('Invalid PointWave webhook signature', [
                'received' => $receivedSignature,
                'expected' => $expectedSignature,
                'payload_length' => strlen($payload),
                'first_100_chars' => substr($payload, 0, 100)
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        // 6. Signature is valid - NOW parse the JSON
        $data = json_decode($payload, true);
        
        Log::info('PointWave webhook verified successfully', [
            'event' => $data['event'] ?? 'unknown',
            'reference' => $data['data']['reference'] ?? null
        ]);
        
        // 7. Process the webhook
        try {
            switch ($data['event'] ?? '') {
                case 'payment.received':
                case 'va_deposit':
                    $this->handlePaymentReceived($data['data']);
                    break;
                    
                case 'transfer.success':
                    $this->handleTransferSuccess($data['data']);
                    break;
                    
                case 'transfer.failed':
                    $this->handleTransferFailed($data['data']);
                    break;
                    
                default:
                    Log::warning('Unknown webhook event', ['event' => $data['event'] ?? 'none']);
            }
            
            return response()->json(['status' => 'success'], 200);
            
        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage());
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
    
    private function handlePaymentReceived($data)
    {
        // Your business logic
        Log::info('Processing payment', ['data' => $data]);
    }
    
    private function handleTransferSuccess($data)
    {
        // Your business logic
        Log::info('Processing transfer success', ['data' => $data]);
    }
    
    private function handleTransferFailed($data)
    {
        // Your business logic
        Log::info('Processing transfer failure', ['data' => $data]);
    }
}
```

## Key Points

1. **Use `$request->getContent()`** - This gets the raw body exactly as received
2. **Don't use `$request->all()` or `$request->input()`** - These parse the JSON and will create different output when re-encoded
3. **Don't use `json_encode($request->all())`** - JSON encoding order and spacing may differ
4. **Use `hash_equals()`** - Prevents timing attacks
5. **Your webhook secret is correct** - `whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24`

## Testing After Fix

After updating your code:

1. Save the changes
2. Wait for the next webhook from PointWave (or trigger a test transaction)
3. Check your logs - you should see "PointWave webhook verified successfully" instead of "Invalid signature"

## Debug Tips

If it still fails after the fix, add this debug logging temporarily:

```php
// Add this right after getting the payload
Log::info('Webhook debug', [
    'payload_length' => strlen($payload),
    'payload_md5' => md5($payload),
    'first_200_chars' => substr($payload, 0, 200),
    'received_signature' => $receivedSignature,
    'calculated_signature' => hash_hmac('sha256', $payload, $webhookSecret)
]);
```

This will help identify if:
- The payload is being modified somehow
- The webhook secret is incorrect
- There's middleware interfering with the request

## Need Help?

If the issue persists after implementing this fix, send us:
1. The debug log output from above
2. Your webhook handler code (with secrets redacted)
3. Any middleware that might be processing the request

---

**Summary:** Use `$request->getContent()` to get the raw body, not `json_encode($request->all())`. This is the most common webhook signature verification mistake.
