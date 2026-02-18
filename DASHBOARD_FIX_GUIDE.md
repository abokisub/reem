# Dashboard Display Issue - Fix Guide

## Problem
Dashboard not showing transactions and webhooks even though:
- Backend is working correctly (webhooks received, logs show processing)
- Transaction exists in database with correct amounts
- Browser cache cleared
- Tested on multiple devices (desktop + phone)

## Root Cause Analysis
The issue is likely one of these:

1. **Frontend Cache Issue**: Old JavaScript bundle cached by browser or CDN
2. **API Authentication**: Frontend not sending correct auth tokens
3. **API Endpoint Issue**: Frontend calling wrong endpoint or endpoint returning empty data
4. **Database Sync Issue**: Transaction created but not committed to database

## Fix Steps

### On Live Server

Run the diagnostic script first:
```bash
cd /home/aboksdfs/app.pointwave.ng
./CHECK_LIVE_DASHBOARD.sh
```

This will:
- Pull latest code
- Check if transaction exists in database
- Show recent webhooks
- Clear all caches

### If Transaction Exists in Database

Run the full fix script:
```bash
cd /home/aboksdfs/app.pointwave.ng
./FIX_DASHBOARD_DISPLAY.sh
```

This will:
1. Pull latest backend code
2. Clear all Laravel caches
3. Check database for transactions
4. Rebuild frontend from source
5. Deploy new frontend to public directory

### If Transaction Does NOT Exist

The webhook processing failed. Check:
```bash
# View recent Laravel logs
tail -100 storage/logs/laravel.log

# Check for database errors
php artisan tinker --execute="
\$webhooks = \App\Models\PalmPayWebhook::orderBy('created_at', 'desc')->limit(5)->get();
foreach (\$webhooks as \$wh) {
    echo 'Webhook ' . \$wh->id . ': ' . \$wh->status . ' - ' . \$wh->error_message . '\n';
}
"
```

## Manual Testing

### Test API Endpoints Directly

1. Login to get auth token:
```bash
curl -X POST https://app.pointwave.ng/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"abokisub@gmail.com","password":"your_password"}'
```

2. Test transaction endpoint:
```bash
curl -X GET "https://app.pointwave.ng/api/transactions/deposits?limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

3. Test webhook logs endpoint:
```bash
curl -X GET "https://app.pointwave.ng/api/company/webhooks" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Check Frontend Console

1. Open browser console (F12)
2. Go to Network tab
3. Refresh dashboard
4. Look for API calls to `/api/transactions/deposits`
5. Check response - should show transactions array

## Common Issues & Solutions

### Issue 1: Empty Response from API
**Symptom**: API returns `{"status":"success","data":[]}`

**Solution**: 
- Check user's `active_company_id` matches transaction's `company_id`
- Verify user is logged in with correct account

### Issue 2: 401 Unauthorized
**Symptom**: API returns 401 error

**Solution**:
- Clear browser cookies
- Login again
- Check token expiration in `config/sanctum.php`

### Issue 3: Frontend Shows Old Data
**Symptom**: Dashboard shows old transactions but not new ones

**Solution**:
```bash
# Rebuild frontend
cd frontend
npm run build
cp -r build ../public/dashboard

# Clear browser cache
# Ctrl+Shift+Delete (Chrome/Firefox)
```

### Issue 4: CORS Errors
**Symptom**: Browser console shows CORS errors

**Solution**:
- Check `config/cors.php` allows your domain
- Verify `SANCTUM_STATEFUL_DOMAINS` in `.env`

## Verification Steps

After running fixes:

1. ✅ Check database has transactions:
```bash
php artisan tinker --execute="echo \App\Models\Transaction::where('company_id', 2)->count();"
```

2. ✅ Test API endpoint returns data:
```bash
curl -X GET "https://app.pointwave.ng/api/transactions/deposits?limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

3. ✅ Login to dashboard and verify transactions visible

4. ✅ Send test transaction (₦100) and verify it appears immediately

## Files Involved

### Backend (API)
- `app/Http/Controllers/API/Trans.php` - Transaction endpoints
- `app/Http/Controllers/API/CompanyLogsController.php` - Webhook logs
- `app/Http/Controllers/API/UserDashboardController.php` - Dashboard stats
- `app/Services/PalmPay/WebhookHandler.php` - Webhook processing

### Frontend
- `frontend/src/pages/dashboard/` - Dashboard pages
- `frontend/src/routes/index.js` - API routes
- Frontend calls `/api/transactions/deposits` to fetch transactions

## Support

If issue persists after running all fixes:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console for JavaScript errors
3. Verify database has transactions for company_id=2
4. Test API endpoints directly with curl
5. Try incognito/private browsing mode

## Recent Changes

- ✅ Added `net_amount` and `total_amount` columns to transactions
- ✅ Fixed `DataPurchased` function (removed 'data' table query)
- ✅ Added AdminMiddleware for admin routes
- ✅ All changes pushed to GitHub and deployed

## Next Steps

1. Run `CHECK_LIVE_DASHBOARD.sh` on live server
2. If transaction exists, run `FIX_DASHBOARD_DISPLAY.sh`
3. Clear browser cache and test
4. Send another test transaction to verify real-time updates
