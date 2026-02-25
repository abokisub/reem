# Amtpay Webhook Signature Fix Guide

## Problem
Amtpay's webhook handler is failing signature verification with error: "Invalid signature"

**Root Cause**: The webhook handler is looking for the wrong header name.

## Current Situation

### What PointWave Sends:
- Header: `X-PointWave-Signature` (with capital W)
- Value: `d69dafe51391ef8ec63ede3c120a32c315baa74797def1697f34a60a245a6295`
- Webhook Secret: `whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24`

### What Amtpay's Handler is Doing:
- Looking for wrong header name (likely `X-Webhook-Signature` instead of `X-PointWave-Signature`)
- This causes signature mismatch: received `d69dafe51391ef8ec63e...` vs expected `8f3724b628ca7e724799...`

## The Fix

Amtpay needs to update their webhook handler code at `https://app.amtpay.com.ng/webhooks/pointwave`

### Correct PHP Code:

```php
<?php

// 1. Get webhook secret from .env
$webhookSecret = 'whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24';

// 2. Get raw request body (IMPORTANT: Must be raw, not parsed)
$payload = file_get_contents('php://input');

// 3. Get signature from CORRECT header name
$receivedSignature = $_SERVER['HTTP_X_POINTWAVE_SIGNATURE'] ?? '';

// 4. Compute expected signature using HMAC SHA-256
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

// 5. Verify signature using secure comparison
if (!hash_equals($expectedSignature, $receivedSignature)) {
    // Log for debugging
    error_log('Invalid PointWave webhook signature');
    error_log('Received: ' . $receivedSignature);
    error_log('Expected: ' . $expectedSignature);
    error_log('Payload length: ' . strlen($payload));
    
    http_response_code(401);
    die(json_encode(['error' => 'Invalid signature']));
}

// Signature is valid, process webhook
$data = json_decode($payload, true);

// Handle the event
switch ($data['event'] ?? '') {
    case 'payment.received':
    case 'va_deposit':
        handlePaymentReceived($data);
        break;
    case 'transfer.success':
        handleTransferSuccess($data);
        break;
    case 'transfer.failed':
        handleTransferFailed($data);
        break;
    default:
        error_log('Unknown webhook event: ' . ($data['event'] ?? 'none'));
}

// Return 200 OK
http_response_code(200);
echo json_encode(['status' => 'success']);
```

### If Using Laravel Request Object:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PointWaveWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Get webhook secret from config or .env
        $webhookSecret = config('services.pointwave.webhook_secret') 
            ?? env('POINTWAVE_WEBHOOK_SECRET');
        
        // 2. Get raw request body (IMPORTANT: Use getContent(), not all() or input())
        $payload = $request->getContent();
        
        // 3. Get signature from CORRECT header name (case-insensitive in Laravel)
        $receivedSignature = $request->header('X-PointWave-Signature');
        
        if (!$receivedSignature) {
            Log::warning('PointWave webhook missing signature header');
            return response()->json(['error' => 'Missing signature'], 401);
        }
        
        // 4. Compute expected signature
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        
        // 5. Verify signature using secure comparison
        if (!hash_equals($expectedSignature, $receivedSignature)) {
            Log::warning('Invalid PointWave webhook signature', [
                'received' => substr($receivedSignature, 0, 20) . '...',
                'expected' => substr($expectedSignature, 0, 20) . '...',
                'payload_length' => strlen($payload),
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        // Signature is valid, process webhook
        $data = json_decode($payload, true);
        
        Log::info('PointWave webhook received', [
            'event' => $data['event'] ?? 'unknown',
            'reference' => $data['data']['reference'] ?? null,
        ]);
        
        // Handle the event
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
                    Log::warning('Unknown PointWave webhook event', ['event' => $data['event'] ?? 'none']);
            }
            
            return response()->json(['status' => 'success'], 200);
            
        } catch (\Exception $e) {
            Log::error('PointWave webhook processing error: ' . $e->getMessage());
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
    
    private function handlePaymentReceived($data)
    {
        // Your business logic here
        // Update customer balance, send notifications, etc.
    }
    
    private function handleTransferSuccess($data)
    {
        // Your business logic here
    }
    
    private function handleTransferFailed($data)
    {
        // Your business logic here
    }
}
```

## Key Points to Remember

1. **Header Name**: Must be `X-PointWave-Signature` (with capital W)
   - In PHP: `$_SERVER['HTTP_X_POINTWAVE_SIGNATURE']`
   - In Laravel: `$request->header('X-PointWave-Signature')`

2. **Raw Body**: Must use raw request body, not parsed JSON
   - In PHP: `file_get_contents('php://input')`
   - In Laravel: `$request->getContent()` (NOT `$request->all()` or `$request->input()`)

3. **HMAC Algorithm**: Must be SHA-256
   - `hash_hmac('sha256', $payload, $webhookSecret)`

4. **Secure Comparison**: Must use `hash_equals()` to prevent timing attacks
   - `hash_equals($expectedSignature, $receivedSignature)`

5. **Webhook Secret**: From Amtpay's .env file
   - `whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24`

## Testing After Fix

After updating the code, test by:

1. Trigger a test webhook from PointWave
2. Check Amtpay's logs - should see "success" instead of "Invalid signature"
3. Verify the webhook event is processed correctly

## Common Mistakes to Avoid

❌ **Wrong header name**: `X-Webhook-Signature` (missing "PointWave")
❌ **Using parsed body**: `$request->all()` or `json_encode($request->input())`
❌ **Wrong algorithm**: MD5 or SHA-1 instead of SHA-256
❌ **Insecure comparison**: `==` or `===` instead of `hash_equals()`
❌ **Wrong secret**: Using API key instead of webhook secret

✅ **Correct**: Use `X-PointWave-Signature`, raw body, SHA-256, `hash_equals()`, and webhook secret

## Need Help?

If the issue persists after this fix:
1. Add detailed logging to see what signature is being calculated
2. Log the raw payload length to ensure it matches what PointWave sent (519 bytes in recent logs)
3. Verify the webhook secret matches exactly (no extra spaces or characters)
4. Check if any middleware is modifying the request body before it reaches the handler
