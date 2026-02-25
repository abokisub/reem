# Next Steps for Amtpay Webhook Testing

## Current Status

✅ **Code Fix Deployed**: The webhook signature fix has been pushed to GitHub and pulled to the live server
✅ **Test Scripts Ready**: Comprehensive test scripts are available on the server
⚠️ **Scripts Not Running**: The test scripts ran but produced no output (likely PHP error or silent exit)

## What Just Happened

The user ran these commands on the live server:
```bash
cd app.pointwave.ng
git pull origin main
php check_amtpay_webhook_secret.php    # No output
php test_amtpay_webhook_live.php       # No output
```

Both scripts ran but produced no output, which indicates either:
1. PHP error that's being suppressed
2. Laravel bootstrap failing silently
3. Missing dependencies or configuration issue

## Immediate Action Required

### Step 1: Run Basic Diagnostic
```bash
cd app.pointwave.ng
git pull origin main
php test_basic.php
```

This will show:
- PHP version
- Whether Laravel can bootstrap
- Whether database connection works
- Whether Amtpay company can be loaded

### Step 2: Check for Errors
If `test_basic.php` also produces no output, check PHP error logs:
```bash
tail -f storage/logs/laravel.log
# or
tail -f /var/log/php-fpm/error.log
```

### Step 3: Try Direct PHP Execution
```bash
php -r "echo 'PHP is working\n';"
```

If this doesn't print anything, there's a PHP configuration issue.

## Alternative: Manual Testing

If the scripts won't run, you can manually test the webhook:

### 1. Check Webhook Secret in Database
```bash
php artisan tinker
```

Then in tinker:
```php
$company = App\Models\Company::find(10);
echo "Name: " . $company->name . "\n";
echo "Webhook URL: " . $company->webhook_url . "\n";
$secret = $company->webhook_secret;
echo "Secret length: " . strlen($secret) . "\n";
echo "Secret (first 30): " . substr($secret, 0, 30) . "\n";

// Check if serialized
if (strpos($secret, 's:') === 0) {
    echo "SECRET IS SERIALIZED\n";
    $unserialized = unserialize($secret);
    echo "After unserialize: " . substr($unserialized, 0, 30) . "\n";
}
```

### 2. Test Signature Generation
In tinker:
```php
$payload = '{"test":"data"}';
$secret = 'whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24';
$signature = hash_hmac('sha256', $payload, $secret);
echo "Signature: " . $signature . "\n";
```

### 3. Send Test Webhook Manually
```bash
curl -X POST https://amtpay-webhook-url.com/webhook \
  -H "Content-Type: application/json" \
  -H "X-PointWave-Signature: YOUR_SIGNATURE_HERE" \
  -H "X-PointWave-Event-ID: test-123" \
  -H "X-PointWave-Event-Type: payment.success" \
  -d '{"event":"payment.success","data":{"amount":100}}'
```

## What We're Testing

The fix we deployed does this:

```php
// In OutgoingWebhookService.php and TransactionController.php
$webhookSecret = $company->webhook_secret;

// NEW CODE: Ensure webhook secret is plain string (not serialized)
if (is_string($webhookSecret) && (strpos($webhookSecret, 's:') === 0 || strpos($webhookSecret, 'a:') === 0)) {
    $webhookSecret = unserialize($webhookSecret);
}

// Then calculate signature
$signature = hash_hmac('sha256', $jsonPayload, $webhookSecret);
```

This fixes the issue where Laravel's encrypted cast was returning serialized format like:
```
s:70:"whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24";
```

Instead of plain text:
```
whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24
```

## Expected Outcome

Once the scripts run successfully:

1. **check_amtpay_webhook_secret.php** should show:
   - Whether secret is serialized or plain text
   - Whether it matches Amtpay's expected secret
   - Whether code fix is deployed

2. **test_amtpay_webhook_live.php** should:
   - Send actual webhook to Amtpay's URL
   - Show HTTP response status
   - Indicate if signature was accepted

3. **If successful (200 status)**:
   - Amtpay can remove their bypass code
   - Test with real transaction
   - Monitor webhook logs

4. **If failed (400/401/403)**:
   - Need to restart PHP-FPM to clear OPcache
   - Verify webhook secrets match
   - Check Amtpay's logs for exact error

## Critical Issue: PHP-FPM Restart

Even though the code is deployed, PHP-FPM might be running old code from memory (OPcache).

The user tried to restart but doesn't have sudo access:
```bash
sudo systemctl restart php-fpm
# bash: sudo: command not found
```

**Solutions:**
1. Use cPanel: MultiPHP Manager → Restart PHP-FPM
2. Contact hosting provider to restart PHP
3. Use hosting control panel's PHP restart feature
4. Wait for PHP-FPM to naturally reload (may take hours)

## Contact Amtpay

Once tests are successful, tell Amtpay:

> "We've fixed the webhook signature issue. The problem was that our system was using a serialized version of the webhook secret instead of the plain text version. We've deployed a fix that automatically unserializes the secret before calculating the signature.
>
> We've tested the webhook delivery to your endpoint and confirmed the signature now matches. You can now:
> 1. Remove the signature bypass code from your webhook handler
> 2. Enable full signature verification
> 3. Test with a real transaction
>
> The signature is calculated as: `hash_hmac('sha256', $rawPayload, $webhookSecret)`
> Where `$rawPayload` is the raw JSON body from `$request->getContent()`"

## Files on Server

These files are now available on the live server:
- `test_basic.php` - Basic diagnostic test
- `check_amtpay_webhook_secret.php` - Check webhook secret status
- `test_amtpay_webhook_live.php` - Send test webhook to Amtpay
- `verify_code_deployed.php` - Verify fix is deployed
- `RUN_AMTPAY_WEBHOOK_TESTS.txt` - Detailed instructions

## Summary

The fix is deployed, but we need to:
1. Figure out why the test scripts aren't producing output
2. Restart PHP-FPM to load the new code
3. Test actual webhook delivery to Amtpay
4. Confirm signature validation works
5. Have Amtpay remove their bypass code

The core issue is solved in the code - we just need to get it running in production.
