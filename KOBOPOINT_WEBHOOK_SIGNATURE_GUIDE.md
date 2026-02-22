# KoboPoint Webhook Signature Verification Guide

## Good News!

Your webhook endpoint is now working! We can see it's accepting POST requests. However, you're checking for a signature that needs to be configured properly.

## What We're Sending

PointWave sends webhooks with these headers:

```
Content-Type: application/json
User-Agent: PointWave-Webhook/1.0
X-PointWave-Signature: sha256=<signature_hash>
```

## How to Verify the Signature

### Step 1: Get Your Webhook Secret

1. Login to PointWave dashboard: https://app.pointwave.ng
2. Go to **Settings** → **API Configuration**
3. Find your **Webhook Secret** (looks like: `whsec_abc123...`)
4. Copy this secret - you'll need it to verify signatures

### Step 2: Verify Signature in Your Code

#### PHP Example

```php
<?php
// File: app/Http/Controllers/WebhookController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handlePointWaveWebhook(Request $request)
    {
        // Get the signature from header
        $receivedSignature = $request->header('X-PointWave-Signature');
        
        // Get your webhook secret from config or database
        $webhookSecret = config('services.pointwave.webhook_secret'); // or from database
        
        // Get raw request body
        $payload = $request->getContent();
        
        // Calculate expected signature
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $webhookSecret);
        
        // Verify signature
        if (!hash_equals($expectedSignature, $receivedSignature)) {
            Log::warning('PointWave webhook signature mismatch', [
                'expected' => $expectedSignature,
                'received' => $receivedSignature,
                'ip' => $request->ip()
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        // Signature is valid - process the webhook
        $data = $request->json()->all();
        
        Log::info('PointWave webhook received', $data);
        
        // Process based on event type
        switch ($data['event_type']) {
            case 'payment.success':
                $this->handlePaymentSuccess($data);
                break;
                
            case 'transfer.success':
                $this->handleTransferSuccess($data);
                break;
                
            case 'transfer.failed':
                $this->handleTransferFailed($data);
                break;
        }
        
        // Return 200 OK
        return response()->json(['status' => 'received'], 200);
    }
    
    private function handlePaymentSuccess($data)
    {
        // Credit customer account
        $transactionRef = $data['data']['transaction_ref'];
        $amount = $data['data']['net_amount'];
        
        // Update your database...
    }
}
```

#### Laravel Config (config/services.php)

```php
return [
    // ... other services
    
    'pointwave' => [
        'webhook_secret' => env('POINTWAVE_WEBHOOK_SECRET'),
    ],
];
```

#### .env File

```env
POINTWAVE_WEBHOOK_SECRET=whsec_your_actual_secret_here
```

### Step 3: Important Security Notes

1. **Always verify the signature** - Don't process webhooks without verification
2. **Use hash_equals()** - Prevents timing attacks
3. **Use raw request body** - Don't parse JSON before verifying signature
4. **Log failures** - Track invalid signature attempts
5. **Return 401 for invalid signatures** - Don't return 200

## Testing Your Implementation

### Option 1: Use PointWave Test Mode

1. Get a test webhook secret from PointWave dashboard
2. Configure test webhook URL
3. Make a test transaction
4. Check your logs

### Option 2: Manual Test with cURL

```bash
# Get your webhook secret
WEBHOOK_SECRET="whsec_your_secret"

# Create test payload
PAYLOAD='{"event_type":"payment.success","data":{"transaction_ref":"TEST_123","amount":1000}}'

# Calculate signature
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$WEBHOOK_SECRET" | sed 's/^.* //')

# Send test webhook
curl -X POST https://app.kobopoint.com/webhooks/pointwave \
  -H "Content-Type: application/json" \
  -H "X-PointWave-Signature: sha256=$SIGNATURE" \
  -d "$PAYLOAD"
```

## Current Issue

Your logs show:
```
[2026-02-22 08:35:13] production.WARNING: PointWave webhook missing signature
```

This means one of these:

1. **You're checking for the signature but it's not configured**
   - Solution: Add your webhook secret to your config

2. **You're looking for a different header name**
   - We send: `X-PointWave-Signature`
   - Make sure you're checking for this exact header name

3. **The signature verification is failing**
   - Make sure you're using the raw request body
   - Make sure you're using the correct webhook secret

## Quick Fix

If you want to temporarily disable signature verification for testing:

```php
public function handlePointWaveWebhook(Request $request)
{
    // TEMPORARY: Skip signature verification for testing
    // TODO: Enable signature verification in production
    
    $data = $request->json()->all();
    
    // Process webhook...
    
    return response()->json(['status' => 'received'], 200);
}
```

**But remember to enable signature verification before going live!**

## What Happens Next

Once you fix the signature verification:

1. PointWave will send webhook → Your endpoint receives it
2. You verify signature → Signature is valid
3. You process the webhook → Credit customer account
4. You return 200 OK → PointWave marks webhook as delivered
5. Everyone is happy! 🎉

## Need Help?

If you're still having issues:

1. Check your logs for the exact error message
2. Verify you're using the correct webhook secret
3. Make sure you're checking the correct header name: `X-PointWave-Signature`
4. Test with the cURL command above

---

**PointWave Team**  
February 22, 2026
