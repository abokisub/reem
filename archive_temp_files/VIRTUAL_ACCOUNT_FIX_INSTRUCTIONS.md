# Virtual Account Fix Instructions

## Problem
Some company end users are missing virtual accounts due to KYC limits being reached (PalmPay Error: `licenseNumber duplicate (Code: AC100009)`).

## Solution
This fix includes:
1. A new artisan command to regenerate missing virtual accounts
2. Automatic assignment of fresh KYC from the global pool
3. Safe dry-run mode to preview changes
4. Interactive script for easy execution

## Files Created
- `app/Console/Commands/RegenerateMissingVirtualAccounts.php` - Artisan command
- `FIX_MISSING_VIRTUAL_ACCOUNTS.sh` - Interactive deployment script
- `VIRTUAL_ACCOUNT_FIX_INSTRUCTIONS.md` - This file

## Deployment Steps

### 1. Push to GitHub
```bash
git add app/Console/Commands/RegenerateMissingVirtualAccounts.php
git add FIX_MISSING_VIRTUAL_ACCOUNTS.sh
git add VIRTUAL_ACCOUNT_FIX_INSTRUCTIONS.md
git commit -m "Add command to regenerate missing virtual accounts with fresh KYC"
git push origin main
```

### 2. On Live Server
```bash
# Pull latest changes
cd /path/to/your/app
git pull origin main

# Make script executable
chmod +x FIX_MISSING_VIRTUAL_ACCOUNTS.sh

# Run the interactive script
./FIX_MISSING_VIRTUAL_ACCOUNTS.sh
```

## Usage Options

### Option 1: Interactive Script (Recommended)
```bash
./FIX_MISSING_VIRTUAL_ACCOUNTS.sh
```
This will:
- Show global KYC pool status
- List companies with missing virtual accounts
- Provide interactive menu to fix specific companies

### Option 2: Direct Artisan Command

#### Dry Run (Preview Only)
```bash
php artisan kyc:regenerate-missing-accounts --company-name="Oyitipay" --dry-run
```

#### Fix Oyitipay with Fresh KYC Assignment
```bash
php artisan kyc:regenerate-missing-accounts --company-name="Oyitipay" --assign-fresh-kyc
```

#### Fix Oyitipay without Fresh KYC Assignment
```bash
php artisan kyc:regenerate-missing-accounts --company-name="Oyitipay"
```

#### Fix by Company ID
```bash
php artisan kyc:regenerate-missing-accounts --company-id=17 --assign-fresh-kyc
```

## Command Options

| Option | Description |
|--------|-------------|
| `--company-id=ID` | Specific company ID to process |
| `--company-name=NAME` | Company name to search for (partial match) |
| `--dry-run` | Show what would be done without making changes |
| `--assign-fresh-kyc` | Assign fresh KYC from global pool before regenerating |

## What the Command Does

1. **Finds the company** - By ID or name
2. **Displays company info** - Shows current KYC status
3. **Checks global KYC pool** - Shows available KYC numbers
4. **Finds users without VAs** - Lists all users missing virtual accounts
5. **Assigns fresh KYC** (if `--assign-fresh-kyc` flag is used)
   - Selects optimal KYC from global pool (prefers NIN over BVN)
   - Assigns to company's backup director slot
6. **Regenerates virtual accounts** - Creates missing accounts with proper KYC fallback

## Safety Features

- **Dry run mode** - Preview changes before applying
- **Interactive confirmation** - Asks before making changes
- **Progress bar** - Shows regeneration progress
- **Detailed logging** - All operations logged to `storage/logs/laravel.log`
- **Rate limiting** - 1 second delay between account creations
- **Error handling** - Continues on failure, reports at end

## Checking Results

### View logs
```bash
tail -f storage/logs/laravel.log
```

### Check specific company
```bash
php artisan tinker --execute="
\$company = \App\Models\Company::where('name', 'LIKE', '%Oyitipay%')->first();
\$usersWithoutVA = \App\Models\CompanyUser::where('company_id', \$company->id)
    ->whereDoesntHave('virtualAccounts')
    ->count();
echo 'Users without VA: ' . \$usersWithoutVA . PHP_EOL;
"
```

### Check global KYC pool usage
```bash
php artisan tinker --execute="
\$stats = (new \App\Services\GlobalKycService())->getUsageStats();
print_r(\$stats);
"
```

## Troubleshooting

### Error: "No available KYC in global pool"
**Solution:** Add more KYC to the global pool
```bash
php artisan tinker --execute="
\$service = new \App\Services\GlobalKycService();
\$service->addGlobalKyc('22222222222', 'bvn', 'Added for Oyitipay fix');
"
```

### Error: "licenseNumber duplicate" still occurs
**Cause:** The assigned KYC has reached its limit
**Solution:** 
1. Check which KYC is being used
2. Assign a different KYC from the pool
3. Or add new KYC to the pool

### Users still missing virtual accounts
**Check:**
1. View logs: `tail -f storage/logs/laravel.log`
2. Check PalmPay API status
3. Verify company has valid KYC assigned
4. Run with `--assign-fresh-kyc` flag

## Example: Fix Oyitipay

```bash
# Step 1: Check current status (dry run)
php artisan kyc:regenerate-missing-accounts --company-name="Oyitipay" --dry-run

# Step 2: Fix with fresh KYC assignment
php artisan kyc:regenerate-missing-accounts --company-name="Oyitipay" --assign-fresh-kyc

# Step 3: Verify
php artisan tinker --execute="
\$company = \App\Models\Company::where('name', 'LIKE', '%Oyitipay%')->first();
echo 'Company: ' . \$company->name . PHP_EOL;
echo 'Users without VA: ' . \App\Models\CompanyUser::where('company_id', \$company->id)->whereDoesntHave('virtualAccounts')->count() . PHP_EOL;
"
```

## Notes

- The command uses the existing `VirtualAccountService` which has built-in KYC fallback logic
- Fresh KYC is assigned to `backup_director_1_bvn` or `backup_director_1_nin` slots
- The global KYC pool automatically tracks usage and success rates
- Failed KYC numbers are automatically blacklisted if success rate drops below 20%

## Support

If you encounter issues:
1. Check `storage/logs/laravel.log` for detailed error messages
2. Run with `--dry-run` to preview without changes
3. Verify global KYC pool has available numbers
4. Contact support with the request_id from logs
