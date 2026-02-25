# CRITICAL: Amtpay Webhook Security Fix - PRODUCTION

## ⚠️ URGENT SECURITY ISSUE

Your webhook signature verification is currently BYPASSED. This means:
- Anyone can send fake payment notifications to your system
- Attackers can credit fake money to accounts
- Your system is vulnerable to fraud

## THE FIX (5 Minutes)

You need to find where you're calculating the webhook signature and change ONE line of code.

### Step 1: Find Your Webhook Handler File

Look for the file that handles PointWave webhooks. It's likely one of these:
- `app/Http/Controllers/API/PointWaveWebhookController.php`
- `app/Jobs/ProcessPointWaveWebhook.php`
- Or search for: `BYPASSING signature check`

### Step 2: Find the Signature Calculation Code

Look for code that looks like this:

```php
// WRONG CODE (this is what you have now):
$data = $request->all();
$payload = json_encode($data);
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
```

OR

```php
// WRONG CODE (another variation):
$payload = json_encode($request->input());
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
```

### Step 3: Replace With This EXACT Code

```php
// CORRECT CODE - Use this instead:
$payload = $request->getContent();  // Get RAW body
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
```

### Step 4: Remove the Bypass Code

Find and DELETE these lines:

```php
// DELETE THIS:
Log::info('BYPASSING signature check - REMOVE THIS AFTER FIXING');
// And any code that skips signature verification
```

### Step 5: Keep the Signature Verification

Make sure you have this code (DON'T delete this):

```php
// KEEP THIS CODE:
$receivedSignature = $request->header('X-PointWave-Signature');

if (!hash_equals($expectedSignature, $receivedSignature)) {
    Log::warning('Invalid PointWave webhook signature');
    return response()->json(['error' => 'Invalid signature'], 401);
}
```

## Complete Working Example

Here's the COMPLETE correct code for your webhook handler:

```php
<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PointWaveWebhookController extends Controller
{
    public function handleWebhook(Request $request)
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
        
        // 5. Verify signature (DO NOT BYPASS THIS!)
        if (!hash_equals($expectedSignature, $receivedSignature)) {
            Log::warning('Invalid PointWave webhook signature', [
                'received' => substr($receivedSignature, 0, 20) . '...',
                'expected' => substr($expectedSignature, 0, 20) . '...',
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        // 6. Signature is valid - NOW parse the JSON
        $data = json_decode($payload, true);
        
        Log::info('PointWave webhook verified successfully', [
            'event' => $data['event'] ?? 'unknown'
        ]);
        
        // 7. Process the webhook
        // ... your existing processing code ...
        
        return response()->json(['status' => 'success'], 200);
    }
}
```

## What Changed?

### BEFORE (Wrong):
```php
$data = $request->all();
$payload = json_encode($data);  // ❌ This creates different JSON
```

### AFTER (Correct):
```php
$payload = $request->getContent();  // ✅ Gets exact bytes received
```

## Why This Matters

When you use `$request->all()` and then `json_encode()` it:
- Changes the order of JSON keys
- Changes spacing and formatting
- Creates a DIFFERENT signature than what PointWave sent
- That's why verification always fails

Using `$request->getContent()` gets the EXACT bytes that PointWave sent, so the signature matches.

## Testing After Fix

1. Save your changes
2. Wait for next webhook from PointWave (or trigger a test transaction)
3. Check your logs - you should see:
   - ✅ "PointWave webhook verified successfully"
   - ❌ NO "BYPASSING signature check" messages
   - ❌ NO "Invalid signature" warnings

## If It Still Fails

If you still see "Invalid signature" after this fix, check:

1. **Webhook secret is correct**: `whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24`
2. **No middleware modifying the request**: Check if any middleware is parsing/modifying the body
3. **Using correct header name**: `X-PointWave-Signature` (with capital W)

## Need Help?

Send us:
1. The exact code you're using for signature verification
2. A sample log entry showing the signature mismatch
3. Confirmation that you're using `$request->getContent()`

---

## Summary

**Change this ONE line:**
```php
// FROM:
$payload = json_encode($request->all());

// TO:
$payload = $request->getContent();
```

**Then remove the bypass code.**

That's it. This will secure your production system.
