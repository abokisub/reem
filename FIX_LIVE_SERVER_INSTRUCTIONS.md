# Fix Live Server 500 Error - Instructions

## Problem
The live server is showing "500 | SERVER ERROR" because `routes/web.php` is trying to load `frontend/index.html` but the file is actually at `frontend/build/index.html`.

## Solution Steps

### Step 1: Pull Latest Code
```bash
cd app.pointwave.ng
git pull origin main
```

### Step 2: Clear Laravel Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Step 3: Verify Frontend Build Exists
```bash
ls -la frontend/build/index.html
```

If the file doesn't exist, you need to build the frontend:
```bash
cd frontend
npm install
npm run build
cd ..
```

### Step 4: Test the Fix
Open your browser and visit:
- https://app.pointwave.ng
- https://app.pointwave.ng/secure/kyc-pool

Both should now work without 500 errors.

### Step 5: Diagnose KYC Pool System
Run the diagnostic script to check the actual pool data:
```bash
php diagnose_live_kyc_pool.php
```

This will show you:
- Total pool entries (should show 68 if that's what you see in dashboard)
- Available vs exhausted vs blacklisted entries
- Company KYC health status
- Missing virtual accounts
- Whether the refresh button functionality is working

## What Was Fixed

1. **routes/web.php** - Changed line 69 from:
   ```php
   return file_get_contents(base_path('frontend/index.html'));
   ```
   To:
   ```php
   return file_get_contents(base_path('frontend/build/index.html'));
   ```

## About the KYC Pool Dashboard

The dashboard at `/secure/kyc-pool` shows:

1. **Pool Stats** - Total, Available, Exhausted, Blacklisted, NIns, BVNs
2. **Company KYC Health** - Shows each company's VA usage and when they need refresh
3. **Missing Virtual Accounts** - Lists customers without VAs
4. **Global KYC Pool Entries** - Full table of all pool entries with usage stats

### How the Refresh Button Works

When you click "Refresh" for a company:
1. System finds the least-used available NIN and BVN from the pool
2. Assigns them to the company's `director_nin` and `director_bvn`
3. Clears the company's KYC blacklist
4. Sets `kyc_refreshed_at` to current time
5. The company's VA counter resets (counts from refresh date)

The button should turn the company status to green (healthy) after refresh.

### Troubleshooting Dashboard Issues

If the dashboard shows 68 entries but they're not working correctly:

1. **Run the diagnostic script** to see actual database state
2. **Check if entries are blacklisted** - they auto-expire after 24 hours
3. **Check if entries are exhausted** - max_usage should be 130 per entry
4. **Verify entries are active** - is_active should be true

The diagnostic script will show you exactly what's in the database vs what the dashboard displays.

## Next Steps After Fix

1. Test the KYC Pool dashboard functionality
2. Run diagnostic script to verify data accuracy
3. Test the "Refresh" button on a company
4. Test the "Regenerate Missing VAs" functionality
5. Monitor the system to ensure new VAs are created successfully

## Support

If you still see issues after these steps, run the diagnostic script and share the output so we can see the actual database state.
