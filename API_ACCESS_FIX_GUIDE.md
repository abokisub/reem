# API Access Lock Fix Guide

## Problem
Company getting error: **"API access is locked. Please unlock it in your dashboard."** when trying to create customers or use virtual accounts.

## Root Cause
The `MerchantAuth` middleware checks two conditions:
1. `status` field must be `'active'`
2. `is_active` field must be `true` (1)

If either is false, API access is blocked with a 403 error.

## Solution

### Step 1: Pull Latest Code on Live Server
```bash
cd /path/to/your/project
git pull origin main
```

### Step 2: Check Company Status
```bash
php check_api_access.php
```

Enter the company name (e.g., "Amtpay") when prompted. The script will show:
- Current `status` and `is_active` values
- Whether API access is locked or unlocked
- Recent virtual accounts
- SQL command to fix if needed

### Step 3: Unlock API Access
```bash
php unlock_api_access.php
```

Enter the company name or ID when prompted, then confirm with "yes".

### Alternative: Manual Database Fix
If you prefer to fix directly in the database:

```sql
-- Check current status
SELECT id, name, status, is_active, kyc_status 
FROM companies 
WHERE name LIKE '%Amtpay%';

-- Unlock API access
UPDATE companies 
SET status = 'active', is_active = 1 
WHERE id = [company_id];
```

### Alternative: Using Artisan Tinker
```bash
php artisan tinker --execute="
\$c = \App\Models\Company::where('name', 'LIKE', '%Amtpay%')->first();
\$c->status = 'active';
\$c->is_active = true;
\$c->save();
echo 'API unlocked for ' . \$c->name;
"
```

## Verification

After unlocking, the company should be able to:
1. Create customers via API
2. Create virtual accounts
3. Make transfers
4. Use all API endpoints

Test by retrying the failed API call that returned the 403 error.

## Dashboard Toggle

The dashboard has an "API Access" toggle in the company settings. Make sure:
- The toggle shows "Unlocked" 
- Both database fields are set correctly (not just the UI)

## Prevention

When approving company KYC or activating companies, ensure both fields are set:
```php
$company->status = 'active';
$company->is_active = true;
$company->save();
```

## Related Files
- `app/Http/Middleware/V1/MerchantAuth.php` - Authentication middleware
- `app/Models/Company.php` - Company model with `isActive()` method
- `check_api_access.php` - Diagnostic tool
- `unlock_api_access.php` - Fix tool
