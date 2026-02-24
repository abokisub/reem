# Deployment Instructions - Master Wallet Fix

## Overview
This deployment fixes the critical master wallet creation issue and adds admin company management features.

---

## Files Changed

### Backend Files:
1. `app/Models/VirtualAccount.php` - Added is_master to fillable/casts
2. `app/Http/Controllers/Admin/CompanyKycController.php` - Added update() method, fixed show()
3. `app/Http/Controllers/API/CompanyKycSubmissionController.php` - Added director KYC fields
4. `routes/api.php` - Added PUT /api/admin/companies/{id}
5. `database/migrations/2026_02_24_120000_add_is_master_and_provider_to_virtual_accounts.php` - Migration for new columns

### Frontend Files:
1. `frontend/src/pages/admin/companies/detail.js` - Complete rewrite with correct API

### Scripts:
1. `fix_all_activated_companies_master_wallets.php` - Fix existing companies

---

## Deployment Steps

### Step 1: Backup Database
```bash
cd /home/aboksdfs/app.pointwave.ng
mysqldump -u [user] -p [database] > backup_before_master_wallet_fix_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Pull Latest Code
```bash
git pull origin main
```

### Step 3: Run Migration
```bash
php artisan migrate
```

**Expected Output:**
```
Migrating: 2026_02_24_120000_add_is_master_and_provider_to_virtual_accounts
Migrated:  2026_02_24_120000_add_is_master_and_provider_to_virtual_accounts (125.47ms)
```

### Step 4: Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 5: Fix Existing Activated Companies
```bash
php fix_all_activated_companies_master_wallets.php
```

**This script will:**
- Find all activated companies
- Check if they have director_bvn, director_nin, or RC number
- Create missing company wallets
- Create missing master virtual accounts
- Show detailed progress and summary

### Step 6: Verify Fix
```bash
# Check a specific company (e.g., Amtpay)
php check_amtpay_company.php
```

**Expected Output:**
```
✅ Company Wallet EXISTS
✅ Master Virtual Account EXISTS
   Account Number: 8012345678
   Bank: PalmPay
   KYC Source: director_bvn
```

### Step 7: Test Frontend
1. Login to admin panel: https://app.pointwave.ng/secure/companies
2. Click on any company
3. Verify Preview button works
4. Click Edit button
5. Update company information
6. Save and verify changes

---

## What This Fix Does

### 1. Master Wallet Auto-Creation
When admin activates a company (`is_active = true`):
- ✅ Creates company wallet if missing
- ✅ Creates master virtual account using director BVN
- ✅ Marks account as `is_master=1` and `provider='pointwave'`
- ✅ Returns clear error if fails (no silent failures)

### 2. Aggregator Model for Customer Accounts
When company creates virtual account for customer:
- ✅ Uses company director's BVN automatically
- ✅ Customer doesn't need to provide BVN/NIN
- ✅ Falls back to director NIN or RC number if BVN not available

### 3. Admin Company Management
- ✅ Admin can view company details (Preview button)
- ✅ Admin can edit company information (Edit button)
- ✅ Admin can update director BVN/NIN
- ✅ Admin can update bank account details
- ✅ Admin can activate/suspend companies
- ✅ Admin can delete companies

### 4. KYC Submission
- ✅ Now saves `director_bvn`, `director_nin`, `business_registration_number`
- ✅ Companies can provide director KYC during registration
- ✅ Admin can edit if information is wrong

---

## Validation Checklist

After deployment, verify:

- [ ] Migration ran successfully
- [ ] Existing activated companies have master wallets
- [ ] New company registration works
- [ ] KYC submission saves director BVN
- [ ] Admin can activate company
- [ ] Master wallet is created on activation
- [ ] Customer virtual account creation uses director BVN
- [ ] Admin can view company details
- [ ] Admin can edit company information
- [ ] Frontend shows virtual accounts correctly

---

## Rollback Plan

If something goes wrong:

```bash
# Rollback migration
php artisan migrate:rollback --step=1

# Restore database backup
mysql -u [user] -p [database] < backup_before_master_wallet_fix_[timestamp].sql

# Revert code
git reset --hard [previous_commit_hash]
```

---

## Known Issues Fixed

1. ✅ Companies missing master wallets after activation
2. ✅ "No KYC available" error when creating master wallet
3. ✅ Preview button broken in admin panel
4. ✅ Admin cannot edit company information
5. ✅ Frontend/backend data structure mismatch
6. ✅ KYC submission not saving director BVN

---

## Support

If you encounter issues:
1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. Check migration status: `php artisan migrate:status`
3. Verify database columns: `DESCRIBE virtual_accounts;`
4. Run diagnostic script: `php check_amtpay_company.php`

---

## Post-Deployment Tasks

1. Monitor new company registrations
2. Verify master wallet creation for new activations
3. Check customer virtual account creation
4. Review admin activity logs
5. Update documentation if needed
