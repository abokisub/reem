# Check API Error on Server

The API is returning a 500 error. We need to check the Laravel logs to see what's wrong.

## Run this on the server:

```bash
ssh aboksdfs@server350.web-hosting.com
cd /home/aboksdfs/app.pointwave.ng

# Check the last 50 lines of the Laravel log
tail -50 storage/logs/laravel.log
```

## Common Issues to Check:

1. **Missing Middleware** - The V1 MerchantAuth middleware might not be found
2. **Namespace Issue** - Controller namespace might be wrong
3. **Route Cache** - Routes might not be cleared properly

## Quick Fix Commands:

```bash
# Clear all caches again
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Check if the controller file exists
ls -la app/Http/Controllers/API/V1/MerchantApiController.php

# Check if the middleware exists
ls -la app/Http/Middleware/V1/MerchantAuth.php
```

## If Middleware is Missing:

The middleware file should be at: `app/Http/Middleware/V1/MerchantAuth.php`

If it's missing, we need to check if it was committed to GitHub.

## Alternative Test (Direct Route):

Try testing a simpler endpoint first to see if the V1 routes are working at all:

```bash
curl -X GET "https://app.pointwave.ng/api/v1/transactions" \
  -H "Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c" \
  -H "x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba" \
  -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846"
```

## What to Send Me:

Please run the commands above and send me:
1. The last 50 lines of `storage/logs/laravel.log`
2. The output of the `ls` commands to verify files exist
3. Any error messages you see

This will help me identify the exact issue.
