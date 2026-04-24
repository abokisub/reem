# Quick Fix Reference - Missing Virtual Accounts

## 🚀 Quick Start (On Live Server)

```bash
# 1. Pull latest code
git pull origin main

# 2. Run the fix
chmod +x FIX_MISSING_VIRTUAL_ACCOUNTS.sh
./FIX_MISSING_VIRTUAL_ACCOUNTS.sh
```

## 📋 One-Liner Commands

### Fix Oyitipay (with fresh KYC)
```bash
php artisan kyc:regenerate-missing-accounts --company-name="Oyitipay" --assign-fresh-kyc
```

### Dry Run First (Safe Preview)
```bash
php artisan kyc:regenerate-missing-accounts --company-name="Oyitipay" --dry-run
```

### Check Status After Fix
```bash
php artisan tinker --execute="
\$company = \App\Models\Company::where('name', 'LIKE', '%Oyitipay%')->first();
echo 'Users without VA: ' . \App\Models\CompanyUser::where('company_id', \$company->id)->whereDoesntHave('virtualAccounts')->count();
"
```

## 🔍 Quick Diagnostics

### Check Global KYC Pool
```bash
php artisan tinker --execute="print_r((new \App\Services\GlobalKycService())->getUsageStats());"
```

### Find All Companies with Missing VAs
```bash
php artisan tinker --execute="
\$companies = \App\Models\Company::whereHas('companyUsers', function(\$q) {
    \$q->whereDoesntHave('virtualAccounts');
})->get(['id', 'name']);
foreach (\$companies as \$c) {
    \$count = \App\Models\CompanyUser::where('company_id', \$c->id)->whereDoesntHave('virtualAccounts')->count();
    echo \$c->id . ': ' . \$c->name . ' - ' . \$count . ' missing' . PHP_EOL;
}
"
```

### View Recent Logs
```bash
tail -100 storage/logs/laravel.log | grep -i "virtual\|kyc"
```

## ⚡ Emergency Fix (If Script Fails)

```bash
# Manual KYC assignment
php artisan tinker --execute="
\$company = \App\Models\Company::find(17); // Oyitipay
\$kyc = \App\Models\GlobalKycPool::available()->first();
\$company->backup_director_1_nin = \$kyc->kyc_number;
\$company->save();
echo 'Assigned KYC: ' . \$kyc->kyc_number;
"

# Then regenerate
php artisan kyc:regenerate-missing-accounts --company-id=17
```

## 📊 What Each Option Does

| Command | What It Does |
|---------|--------------|
| `--dry-run` | Shows what will happen, makes NO changes |
| `--assign-fresh-kyc` | Gets new KYC from pool and assigns to company |
| `--company-name="Name"` | Finds company by name (partial match) |
| `--company-id=17` | Uses specific company ID |

## ✅ Success Indicators

After running the fix, you should see:
- ✅ Success count matches missing users count
- ✅ "Users without VA: 0" in status check
- ✅ No errors in `storage/logs/laravel.log`

## ❌ Common Errors & Fixes

### "No available KYC in global pool"
```bash
# Add new KYC to pool
php artisan tinker --execute="
(new \App\Services\GlobalKycService())->addGlobalKyc('22222222222', 'bvn', 'Emergency add');
"
```

### "licenseNumber duplicate" still appears
```bash
# Check which KYC is being used and blacklist it
php artisan tinker --execute="
\$service = new \App\Services\GlobalKycService();
\$service->blacklistKyc(1, 24, 'Hit limit');
"
```

### Rate limit errors
```bash
# The command has built-in 1-second delays
# If still hitting limits, run in smaller batches
```

## 🎯 For Oyitipay Specifically

Based on logs, user `allahkayibash@gmail.com` (phone: 07065669871) failed with AC100009 error.

```bash
# Quick fix for Oyitipay
php artisan kyc:regenerate-missing-accounts --company-name="Oyitipay" --assign-fresh-kyc

# Verify the specific user
php artisan tinker --execute="
\$user = \App\Models\CompanyUser::where('email', 'allahkayibash@gmail.com')->first();
\$va = \App\Models\VirtualAccount::where('company_user_id', \$user->id)->first();
echo \$va ? 'Has VA: ' . \$va->account_number : 'Still missing VA';
"
```

## 📞 Support Info

- **Logs Location:** `storage/logs/laravel.log`
- **Command Help:** `php artisan kyc:regenerate-missing-accounts --help`
- **Full Instructions:** See `VIRTUAL_ACCOUNT_FIX_INSTRUCTIONS.md`
