# Fix API V1 500 Error

The API is returning a 500 error. Follow these steps to diagnose and fix it.

## Step 1: Upload Diagnostic Script

Upload `diagnose_api_v1_error.php` to the server:

```bash
scp diagnose_api_v1_error.php aboksdfs@server350.web-hosting.com:/home/aboksdfs/app.pointwave.ng/
```

## Step 2: Run Diagnostic

SSH to server and run the diagnostic:

```bash
ssh aboksdfs@server350.web-hosting.com
cd /home/aboksdfs/app.pointwave.ng
php diagnose_api_v1_error.php
```

This will check:
- ✅ Controller file exists
- ✅ Middleware file exists
- ✅ Routes are configured
- ✅ Laravel log errors
- ✅ Composer autoload
- ✅ Cache files

## Step 3: Quick Fix (Most Likely Solution)

The most common issue is that Composer needs to reload the autoload files:

```bash
cd /home/aboksdfs/app.pointwave.ng

# Regenerate Composer autoload
composer dump-autoload

# Clear all caches
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Restart PHP-FPM (if you have access)
# Or just wait 30 seconds for opcache to clear
```

## Step 4: Test Again

After running the fix commands, test the API:

```bash
curl -X POST "https://app.pointwave.ng/api/v1/customers" \
  -H "Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c" \
  -H "x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba" \
  -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: test_$(date +%s)" \
  -d '{
    "first_name": "Test",
    "last_name": "User",
    "email": "test_'$(date +%s)'@example.com",
    "phone_number": "08012345678"
  }'
```

Expected result:
```json
{
  "status": "success",
  "message": "Customer created successfully",
  "data": {
    "customer_id": "cust_...",
    "email": "test_...@example.com",
    ...
  }
}
```

## Alternative: Check Laravel Log Directly

If the diagnostic script doesn't work, check the log manually:

```bash
tail -100 storage/logs/laravel.log
```

Look for errors related to:
- `Class not found`
- `MerchantApiController`
- `MerchantAuth`
- `Namespace`

## Common Issues & Solutions

### Issue 1: Class Not Found
```
Error: Class 'App\Http\Controllers\API\V1\MerchantApiController' not found
```

**Solution:**
```bash
composer dump-autoload
```

### Issue 2: Middleware Not Found
```
Error: Class 'App\Http\Middleware\V1\MerchantAuth' not found
```

**Solution:**
```bash
# Check if file exists
ls -la app/Http/Middleware/V1/MerchantAuth.php

# If missing, pull again
git pull origin main

# Then reload autoload
composer dump-autoload
```

### Issue 3: Route Not Found
```
Error: Route [api/v1/customers] not defined
```

**Solution:**
```bash
php artisan route:clear
php artisan route:list | grep "v1/customers"
```

### Issue 4: Stale Cache
```
Error: Various weird errors
```

**Solution:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## What to Send Me

If the issue persists, send me:

1. Output of `php diagnose_api_v1_error.php`
2. Last 50 lines of `storage/logs/laravel.log`
3. Output of `php artisan route:list | grep v1`

This will help me identify the exact problem.

## Quick Summary

Most likely fix (run these 4 commands):

```bash
cd /home/aboksdfs/app.pointwave.ng
composer dump-autoload
php artisan route:clear && php artisan config:clear && php artisan cache:clear
curl -X POST "https://app.pointwave.ng/api/v1/customers" -H "Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c" -H "x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba" -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846" -H "Content-Type: application/json" -H "Idempotency-Key: test_123" -d '{"first_name":"Test","last_name":"User","email":"test@example.com","phone_number":"08012345678"}'
```

If you see `"status":"success"`, it's working!
