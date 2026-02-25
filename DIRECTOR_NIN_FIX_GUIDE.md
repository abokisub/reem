# Director BVN/NIN Fix Guide

## Problem Summary

When users register and complete business activation (uploading utility bill, ID card, and providing BVN/NIN), the data was being saved to the `users` table but NOT being copied to the `companies` table as `director_bvn` and `director_nin`.

This caused virtual account creation to fail because the system couldn't find the director's KYC information in the companies table.

## Root Cause

The `activateBusiness()` method in `AuthController.php` was saving user BVN/NIN to:
- `companies.bvn` ✅
- `companies.nin` ✅

But NOT to:
- `companies.director_bvn` ❌
- `companies.director_nin` ❌

## Fix Applied

The fix has been applied to `app/Http/Controllers/API/AuthController.php` (lines 1831-1832):

```php
'director_bvn' => $user->bvn, // Director BVN (same as user BVN for sole proprietors)
'director_nin' => $user->nin, // Director NIN (same as user NIN for sole proprietors)
```

This ensures that all NEW companies will have their director BVN/NIN saved correctly.

## Fixing Existing Companies

For companies that were activated BEFORE this fix was deployed, you need to run the fix scripts.

### Step 1: Check if Company Needs Fix

```bash
php check_user_nin.php
```

This will show you if Company ID 10 (Amtpay) has NIN in users table but not in companies table.

### Step 2: Fix Company ID 10

```bash
php fix_director_nin.php
```

This will copy the NIN from users table to `companies.director_nin` for Company ID 10.

### Step 3: Fix ALL Companies (Recommended)

```bash
php fix_all_director_kyc.php
```

This will:
- Check ALL companies in the database
- Copy BVN from `users.bvn` to `companies.director_bvn` (if missing)
- Copy NIN from `users.nin` to `companies.director_nin` (if missing)
- Show a summary of how many companies were fixed

## Verification

After running the fix, verify the data:

```bash
php check_company_bvn.php
```

You should see:
```
Company: Amtpay
Director BVN: 22500464896
Director NIN: [the NIN value]
RC Number: RC-9351002
```

## Future Prevention

All new companies will automatically have their director BVN/NIN saved correctly because the fix is now in the `activateBusiness()` method.

## Technical Details

### Data Flow

1. User registers → Data saved to `users` table
2. User completes business activation → `activateBusiness()` method runs
3. Method copies data from `users` to `companies` table:
   - `users.bvn` → `companies.bvn` AND `companies.director_bvn`
   - `users.nin` → `companies.nin` AND `companies.director_nin`
4. When admin activates company → Virtual account creation uses `companies.director_bvn` or `companies.director_nin`

### Virtual Account KYC Priority

The `VirtualAccountService` uses this priority for KYC:

1. Customer BVN (if provided)
2. Customer NIN (if provided)
3. **Director BVN** (aggregator model) ← This is what we fixed
4. **Director NIN** (aggregator model) ← This is what we fixed
5. RC Number (fallback for corporate)

## Files Modified

- `app/Http/Controllers/API/AuthController.php` - Added director_bvn and director_nin saving
- `check_user_nin.php` - Diagnostic script
- `fix_director_nin.php` - Fix script for Company ID 10
- `fix_all_director_kyc.php` - Fix script for all companies

## Deployment Steps

1. Pull latest code from GitHub: `git pull origin main`
2. Run fix script on production: `php fix_all_director_kyc.php`
3. Verify Company ID 10: `php check_company_bvn.php`
4. Try activating Company ID 10 again from admin panel

## Support

If you encounter any issues, check:
1. Is the company's user record in the `users` table?
2. Does the user have BVN or NIN in the `users` table?
3. Run `php check_user_nin.php` to diagnose
4. Run `php fix_all_director_kyc.php` to fix
