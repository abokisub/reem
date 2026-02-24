# Fix: Wallet Balance Showing ₦NaN

## Problem
The wallet page was showing "₦NaN" instead of the actual balance.

## Root Cause
The backend was returning balance as a formatted string with commas:
```php
'balance' => number_format($user->balance, 2), // Returns "1,631.60"
```

JavaScript's `fCurrency()` function couldn't parse the comma-separated string, resulting in NaN (Not a Number).

## Solution
Changed the backend to return balance as a number:
```php
'balance' => (float) $user->balance, // Returns 1631.60
'balance_formatted' => number_format($user->balance, 2), // Returns "1,631.60" for display if needed
```

## Files Changed
- `app/Http/Controllers/API/AuthController.php` - getUserDetails() method

## Deployment Steps

```bash
# 1. SSH into server
ssh aboksdfs@app.pointwave.ng
cd /home/aboksdfs/app.pointwave.ng

# 2. Pull latest fix
git pull origin main

# 3. Clear caches
php artisan config:clear && php artisan cache:clear && php artisan route:clear

# 4. Clear OPcache (get today's secret first)
php -r "echo 'Secret: ' . md5('pointwave_opcache_clear_' . date('Y-m-d')) . PHP_EOL;"
curl "https://app.pointwave.ng/clear-opcache.php?secret=YOUR_SECRET_HERE"

# 5. Test - logout and login again
# Balance should now show correctly: ₦1,631.60
```

## Verification
1. Logout from the dashboard
2. Login again
3. Check wallet page - balance should show correctly (e.g., ₦1,631.60)
4. No more "₦NaN"

## Additional Changes
Also added `balance_formatted` and `referral_balance_formatted` fields for cases where you need the pre-formatted string with commas.

---

**Status:** ✅ Fixed and pushed to GitHub
**Impact:** All users will see correct balance after logging in again
