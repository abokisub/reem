# Production .env Fixes Required

## Critical Changes Needed on Live Server

SSH into your production server and edit the `.env` file:

```bash
nano /home/aboksdfs/app.pointwave.ng/.env
```

### Change These Lines:

```env
# CHANGE THIS:
APP_ENV=local
# TO THIS:
APP_ENV=production

# CHANGE THIS:
APP_DEBUG=true
# TO THIS:
APP_DEBUG=false

# CHANGE THIS:
APP_URL=http://192.168.1.160:8000
# TO THIS:
APP_URL=https://app.pointwave.ng

# CHANGE THIS (remove trailing slash):
HABUKHAN_APP_KEY=http://localhost:3000,http://127.0.0.1:3000,http://localhost:8080,http://127.0.0.1:8080,http://localhost:8000,http://127.0.0.1:8000,https://app.pointwave.ng/,http://app.pointwave.ng
# TO THIS (no trailing slash):
HABUKHAN_APP_KEY=http://localhost:3000,http://127.0.0.1:3000,http://localhost:8080,http://127.0.0.1:8080,http://localhost:8000,http://127.0.0.1:8000,https://app.pointwave.ng,http://app.pointwave.ng
```

### After Making Changes:

```bash
# Clear and rebuild config cache
php artisan config:clear
php artisan config:cache
php artisan cache:clear

# Check if webhook endpoint is accessible
curl -X POST https://app.pointwave.ng/api/webhooks/palmpay \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

## Why These Changes Matter:

1. **APP_ENV=production**: Enables production optimizations and security
2. **APP_DEBUG=false**: Prevents exposing sensitive error details to users (SECURITY)
3. **APP_URL**: Ensures correct URLs in emails, webhooks, and redirects
4. **HABUKHAN_APP_KEY**: Ensures CORS works correctly for frontend

## After Fixing:

1. Send another test payment to PalmPay account: **6644694207**
2. Check webhook logs at: https://app.pointwave.ng/secure/webhooks
3. If still no webhooks, check Laravel logs: `tail -f storage/logs/laravel.log`

## PalmPay Configuration Confirmed ✅

Your PalmPay config is correct:
- ✅ PALMPAY_BASE_URL: https://open-gw-prod.palmpay-inc.com
- ✅ PALMPAY_MERCHANT_ID: 126020209274801
- ✅ PALMPAY_APP_ID: L260202154361881198161
- ✅ PALMPAY_PUBLIC_KEY: Present
- ✅ PALMPAY_PRIVATE_KEY: Present
- ✅ IP Whitelist: Configured in PalmPay dashboard
- ✅ Webhook URL: Configured in PalmPay dashboard

The issue is NOT with PalmPay config - it's with Laravel environment settings!
