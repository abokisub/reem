# Deploy VA Deposit Fee Fix to Server

## What Was Fixed

The 0.5% fallback issue for VA deposits. Now when you update "Funding with Bank Transfer" in admin panel, it will actually work!

## Deploy Steps

### 1. SSH to Server
```bash
ssh aboksdfs@server350.web-hosting.com
```

### 2. Navigate to App Directory
```bash
cd /home/aboksdfs/app.pointwave.ng
```

### 3. Pull Latest Code
```bash
git pull origin main
```

### 4. Run Migration
```bash
php artisan migrate
```

This will add the `virtual_funding_type`, `virtual_funding_value`, and `virtual_funding_cap` columns.

### 5. Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### 6. Verify Settings
```bash
php check_live_fee_columns.php
```

You should see the new `virtual_funding_*` columns with values copied from `transfer_charge_*`.

## Test

1. Go to admin panel: `/secure/discount/banks`
2. Update "Funding with Bank Transfer" to 0.70% (or whatever you want)
3. Make a test VA deposit
4. Check the fee charged - it should match what you set!

## Check Logs

```bash
tail -f storage/logs/laravel.log | grep "Virtual Account Credited"
```

Look for `"model": "system_default_percentage"` instead of `"model": "hardcoded_fallback"`

## What Changed

- **Migration**: Added `virtual_funding_*` columns to settings table
- **AdminController**: Now syncs `transfer_charge_*` → `virtual_funding_*` when you update admin panel
- **FeeService**: Already reads from `virtual_funding_*` for VA deposits (no change needed)

✅ Done!
