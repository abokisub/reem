# Settlement Account N/A - Final Fix

## Problem Identified

The diagnostic shows:
- `settlement_account_number` = **7040540018** ✅ EXISTS
- But receipt shows **N/A**

## Root Cause

Looking at the data from the diagnostic:
```
Company Name: NULL  ← This is the problem!
```

The ReceiptService code tries to use `settlement_account_number`, but since `company_name` is NULL, it's falling back to an empty string somewhere.

## The Fix

We need to update the company record to have a proper `company_name` value, OR update the ReceiptService to handle the case where `company_name` is NULL.

## Quick Fix - Update Company Name

Run this on the server:

```bash
cd /home/aboksdfs/app.pointwave.ng
```

Create a file `fix_company_name.php`:

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;

$company = Company::find(2);
if ($company) {
    $company->company_name = $company->name; // Copy from 'name' field
    $company->save();
    echo "✅ Updated company_name to: {$company->company_name}\n";
} else {
    echo "❌ Company not found\n";
}
```

Then run:
```bash
php fix_company_name.php
php artisan view:clear
```

Then check the receipt again - it should now show the account number!

## Alternative: Fix in Code

If the above doesn't work, we need to update the ReceiptService to use `name` as fallback when `company_name` is NULL.
