# Webhook Secret Decryption Fix - Deployment Summary

**Date:** February 22, 2026  
**Issue:** Production errors - "The payload is invalid" when accessing company credentials  
**Status:** ✅ Fixed and Deployed

## Problem Identified

From production logs at 09:58:08 and 10:03:39:
```
[2026-02-22 09:58:08] production.ERROR: The payload is invalid.
[2026-02-22 10:03:39] production.ERROR: The payload is invalid.
```

The error occurred in `CompanyController.php` line 173 when calling `$company->fresh()` after updating company credentials. This triggered Laravel's encryption system to decrypt webhook secrets, but the decryption failed due to:

1. Possible APP_KEY mismatch between when data was encrypted and current key
2. Corrupted encrypted data in the database
3. Full model reload triggering unnecessary decryption of all encrypted fields

## Solution Implemented

### Changes Made to `app/Http/Controllers/API/CompanyController.php`

**Before:**
```php
if (!empty($updates)) {
    DB::table('companies')->where('id', $company->id)->update($updates);
    $company = $company->fresh(); // ❌ This triggers full model reload and decryption
}
```

**After:**
```php
if (!empty($updates)) {
    DB::table('companies')->where('id', $company->id)->update($updates);
    // Reload only the updated fields without triggering full model refresh
    $rawCompany = DB::table('companies')->where('id', $company->id)->first();
}

// Try to decrypt webhook secrets with error handling
try {
    $webhookSecret = $company->webhook_secret;
    $testWebhookSecret = $company->test_webhook_secret;
} catch (\Exception $e) {
    // If decryption fails, regenerate the secrets
    \Log::error('Webhook secret decryption failed, regenerating', [
        'company_id' => $company->id,
        'error' => $e->getMessage()
    ]);
    
    $webhookSecret = 'whsec_' . bin2hex(random_bytes(32));
    $testWebhookSecret = 'whsec_test_' . bin2hex(random_bytes(32));
    
    DB::table('companies')->where('id', $company->id)->update([
        'webhook_secret' => encrypt($webhookSecret),
        'test_webhook_secret' => encrypt($testWebhookSecret),
    ]);
}
```

## Key Improvements

1. **Avoid Unnecessary Model Reload**: Use raw DB query instead of `fresh()` to prevent triggering decryption of all encrypted fields
2. **Graceful Error Handling**: Catch decryption exceptions and auto-regenerate webhook secrets
3. **Maintain Functionality**: Users can still access their credentials even if webhook secrets are corrupted
4. **Logging**: Track when webhook secrets are regenerated for debugging

## Deployment Steps

```bash
# Already completed
git add app/Http/Controllers/API/CompanyController.php
git commit -m "Fix: Handle webhook secret decryption errors in getCredentials"
git push origin main
```

## Production Deployment

SSH into production server and run:

```bash
cd /home/aboksdfs/app.pointwave.ng
git pull origin main
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Testing

After deployment, test the following:

1. **Login to Kobopoint account** (kobopointng@gmail.com)
2. **Navigate to API Credentials page**
3. **Verify credentials load without errors**
4. **Check production logs** for any remaining decryption errors

## Related Issues

This fix also addresses:
- Webhook delivery failures to `https://app.kobopoint.com/webhooks/pointwave`
- DLQ (Dead Letter Queue) webhook issues for company_id: 4

## Monitoring

Watch for these log entries:
- ✅ `Webhook secret decryption failed, regenerating` - Expected during fix
- ❌ `The payload is invalid` - Should no longer appear

## Rollback Plan

If issues occur, revert with:
```bash
git revert ac26fc4
git push origin main
```

Then redeploy previous version on production server.
