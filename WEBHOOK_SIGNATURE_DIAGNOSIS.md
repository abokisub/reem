# WEBHOOK SIGNATURE MISMATCH - ROOT CAUSE IDENTIFIED

## THE ISSUE

PointWave sends webhooks to Kobopoint with signature verification, but they're failing with "Invalid signature" errors.

## WHAT'S HAPPENING

### PointWave Side (Sending):
```
X-PointWave-Signature: sha256=1daac64e83e9bf9b3078...
```

### Kobopoint Side (Receiving):
```
received: "1daac64e83e9bf9b3078..."  (NO sha256= prefix!)
expected: "14872ebf923bf277b5a7..."  (Different hash!)
```

## ROOT CAUSE

**The signatures don't match because PointWave and Kobopoint are hashing DIFFERENT data!**

This is NOT about the `sha256=` prefix. The actual hash values are completely different:
- PointWave sends: `1daac64e83e9bf9b3078...`
- Kobopoint expects: `14872ebf923bf277b5a7...`

## POSSIBLE CAUSES

### 1. Different Webhook Secrets
PointWave and Kobopoint are using different secrets to compute the HMAC.

**Check:**
- PointWave: `companies` table, `webhook_secret` column for company_id=4
- Kobopoint: `.env` file, `POINTWAVE_WEBHOOK_SECRET` value

### 2. Different Payload Format
PointWave might be sending the payload in a different format than Kobopoint expects.

**PointWave sends:**
```php
$payload = json_encode($this->webhookLog->payload);
$signature = hash_hmac('sha256', $payload, $secret);
```

**Kobopoint might be doing:**
```php
$payload = $request->getContent();  // Raw body
$signature = hash_hmac('sha256', $payload, $secret);
```

If PointWave's `json_encode()` produces different spacing/formatting than what Kobopoint receives, the signatures won't match.

### 3. Payload Modification in Transit
Something (proxy, load balancer, web server) might be modifying the JSON payload between PointWave and Kobopoint.

## THE FIX

### Option 1: Use Raw Payload (RECOMMENDED)

**PointWave Side - Modify `SendOutgoingWebhook.php`:**

```php
// Current (WRONG):
$payload = json_encode($this->webhookLog->payload);
$signature = hash_hmac('sha256', $payload, $secret);

$response = Http::timeout(15)
    ->withHeaders([
        'Content-Type' => 'application/json',
        'User-Agent' => 'PointWave-Webhook/1.0',
        'X-PointWave-Signature' => 'sha256=' . $signature,
    ])
    ->post($this->webhookLog->webhook_url, $this->webhookLog->payload);

// Fixed (CORRECT):
$payloadArray = $this->webhookLog->payload;
$payloadJson = json_encode($payloadArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$signature = hash_hmac('sha256', $payloadJson, $secret);

$response = Http::timeout(15)
    ->withHeaders([
        'Content-Type' => 'application/json',
        'User-Agent' => 'PointWave-Webhook/1.0',
        'X-PointWave-Signature' => $signature,  // Remove sha256= prefix
    ])
    ->withBody($payloadJson, 'application/json')  // Send raw JSON
    ->post($this->webhookLog->webhook_url);
```

**Kobopoint Side - Webhook Handler:**

```php
// Get raw payload exactly as sent
$payload = $request->getContent();

// Get signature (no prefix expected)
$receivedSignature = $request->header('X-PointWave-Signature');

// Get secret from .env
$secret = env('POINTWAVE_WEBHOOK_SECRET');

// Compute expected signature
$expectedSignature = hash_hmac('sha256', $payload, $secret);

// Compare
if (!hash_equals($expectedSignature, $receivedSignature)) {
    Log::warning('Invalid PointWave webhook signature', [
        'received' => substr($receivedSignature, 0, 20) . '...',
        'expected' => substr($expectedSignature, 0, 20) . '...',
        'payload_length' => strlen($payload),
        'payload_sample' => substr($payload, 0, 100)
    ]);
    return response()->json(['error' => 'Invalid signature'], 401);
}
```

### Option 2: Store Webhook Secret Correctly

Run this on PointWave server to check the webhook secret:

```bash
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\$company = App\Models\Company::find(4);
echo 'Webhook Secret: ' . \$company->webhook_secret . PHP_EOL;
"
```

Then verify Kobopoint has the EXACT same secret in `.env`:
```
POINTWAVE_WEBHOOK_SECRET=<exact_same_value>
```

## IMMEDIATE ACTION REQUIRED

1. **Run debug script on PointWave:**
   ```bash
   php debug_webhook_signature.php
   ```
   This will show the exact payload and signature being sent.

2. **Check webhook secret on both systems:**
   - PointWave: Query `companies` table for company_id=4
   - Kobopoint: Check `.env` file

3. **Apply the fix to PointWave's `SendOutgoingWebhook.php`**

4. **Retry failed webhooks:**
   ```bash
   php retry_failed_company_webhooks.php
   ```

## TESTING

After the fix, you should see:
- ✅ Kobopoint logs: "PointWave webhook received"
- ✅ HTTP 200 responses
- ✅ Customer balances updating on Kobopoint

NOT:
- ❌ "Invalid PointWave webhook signature"
- ❌ HTTP 401 errors
