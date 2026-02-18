# PalmPay Master Wallet Implementation

## Date: February 18, 2026

## Overview

Implemented automatic PalmPay master wallet generation for companies when they are activated by admin. This separates the funding account (PalmPay) from the settlement account (OPay/other banks).

## Database Changes

### New Fields in `companies` Table

```sql
palmpay_account_number  VARCHAR(255)  -- PalmPay master wallet account number
palmpay_account_name    VARCHAR(255)  -- Account name
palmpay_bank_name       VARCHAR(255)  -- Default: 'PalmPay'
palmpay_bank_code       VARCHAR(255)  -- Default: '100033'
```

### Field Separation

**PalmPay Master Wallet (Funding/Collection)**:
- `palmpay_account_number` - For receiving funds from customers
- `palmpay_account_name`
- `palmpay_bank_name` - Always "PalmPay"
- `palmpay_bank_code` - Always "100033"

**Settlement Account (Withdrawal)**:
- `account_number` - For withdrawing funds (OPay, GTB, etc.)
- `account_name`
- `bank_name` - Can be any bank
- `bank_code`

## Backend Implementation

### 1. Company Activation Hook

**File**: `app/Http/Controllers/Admin/CompanyKycController.php`

When admin activates a company (`is_active = true`), the system automatically:
1. Checks if company already has `palmpay_account_number`
2. If not, creates a PalmPay virtual account using director BVN
3. Saves the account details to company record

```php
// Auto-generate PalmPay virtual account when company is activated
if ($request->is_active && !$company->palmpay_account_number) {
    $virtualAccountService = new \App\Services\PalmPay\VirtualAccountService();
    
    $virtualAccount = $virtualAccountService->createVirtualAccount(
        $company->id,
        'company_master_' . $company->id,
        [
            'name' => $company->name,
            'email' => $company->email,
            'phone' => $company->phone,
        ],
        '100033',
        null
    );
    
    $company->update([
        'palmpay_account_number' => $virtualAccount->account_number,
        'palmpay_account_name' => $virtualAccount->account_name,
        'palmpay_bank_name' => 'PalmPay',
        'palmpay_bank_code' => '100033',
    ]);
}
```

### 2. Company Model Update

**File**: `app/Models/Company.php`

Added new fields to `$fillable` array:
- `palmpay_account_number`
- `palmpay_account_name`
- `palmpay_bank_name`
- `palmpay_bank_code`

## Frontend Implementation

### Wallet Page Update

**File**: `frontend/src/pages/dashboard/wallet-summary.js`

Updated to show PalmPay master wallet instead of settlement account:

**Before**:
```javascript
{user?.account_number}  // Showed settlement account (OPay)
{user?.bank_name}       // Showed settlement bank
```

**After**:
```javascript
{user?.palmpay_account_number}  // Shows PalmPay master wallet
{user?.palmpay_bank_name}       // Shows "PalmPay"
```

**Display Logic**:
- If `palmpay_account_number` exists: Show account with copy button
- If not: Show message "PalmPay account will be generated when your business is activated by admin"

## How It Works

### Company Activation Flow

```
1. Admin approves company KYC
   ↓
2. Admin clicks "Activate" (is_active = true)
   ↓
3. System checks: Does company have palmpay_account_number?
   ↓
4. If NO:
   - Create PalmPay virtual account using director BVN
   - Save account details to company record
   ↓
5. Company is activated
   ↓
6. Company sees PalmPay account on wallet page
```

### Customer Payment Flow

```
1. Customer wants to pay company
   ↓
2. Company shows their PalmPay master wallet account
   ↓
3. Customer transfers money to PalmPay account
   ↓
4. Funds appear in company's wallet balance
   ↓
5. Company can withdraw to their settlement account
```

## Account Types Explained

### Master Wallet (PalmPay)
- **Purpose**: Receive funds from customers
- **Bank**: PalmPay (always)
- **Shown on**: Wallet page
- **Used for**: Funding/Collection
- **Auto-generated**: Yes (when company activated)

### Settlement Account (Any Bank)
- **Purpose**: Withdraw funds
- **Bank**: OPay, GTB, Access, etc. (company choice)
- **Shown on**: Withdraw page
- **Used for**: Withdrawals
- **Auto-generated**: No (company provides)

## Current Status

### ✅ Completed
- [x] Database migration
- [x] Company model updated
- [x] Activation hook implemented
- [x] Wallet page updated
- [x] Withdraw page uses settlement account

### ⚠️ Pending
- [ ] IP whitelist with PalmPay (Error: OPEN_GW_000012)
- [ ] Test master wallet creation after IP whitelisting
- [ ] Verify wallet page displays correctly

## IP Whitelist Issue

**Current IP**: 105.112.30.197

**Error**: `request ip not in ip white list (Code: OPEN_GW_000012)`

**Action Required**: Contact PalmPay support to whitelist IP address 105.112.30.197

## Testing After IP Whitelisting

Run this command to create master wallet for PointWave Business:

```bash
php artisan tinker --execute="
use App\Services\PalmPay\VirtualAccountService;
use App\Models\Company;

\$company = Company::find(2);
\$service = new VirtualAccountService();

\$virtualAccount = \$service->createVirtualAccount(
    \$company->id,
    'company_master_' . \$company->id,
    [
        'name' => \$company->name,
        'email' => \$company->email,
        'phone' => \$company->phone,
    ],
    '100033',
    null
);

\$company->update([
    'palmpay_account_number' => \$virtualAccount->account_number,
    'palmpay_account_name' => \$virtualAccount->account_name,
    'palmpay_bank_name' => 'PalmPay',
    'palmpay_bank_code' => '100033',
]);

echo 'Master Wallet: ' . \$virtualAccount->account_number . PHP_EOL;
"
```

## Example Company Record

After activation, company record will have:

```
Settlement Account (Withdrawal):
- account_number: 7040540018
- bank_name: OPay
- account_name: PointWave Business

PalmPay Master Wallet (Funding):
- palmpay_account_number: 6612345678 (example)
- palmpay_bank_name: PalmPay
- palmpay_account_name: PointWave Business
```

## Benefits

1. **Clear Separation**: Funding and withdrawal accounts are separate
2. **Automatic Setup**: No manual account creation needed
3. **PalmPay Integration**: Uses director BVN strategy
4. **User-Friendly**: Companies see correct account on wallet page
5. **Flexible Withdrawals**: Companies can withdraw to any bank

## Next Steps

1. **Immediate**: Contact PalmPay to whitelist IP 105.112.30.197
2. **After Whitelisting**: Test master wallet creation
3. **Verify**: Check wallet page shows PalmPay account
4. **Test**: Send money to PalmPay account
5. **Confirm**: Verify funds appear in wallet balance

## Files Modified

### Backend
- `database/migrations/2026_02_18_095234_add_palmpay_master_wallet_to_companies_table.php` (NEW)
- `app/Models/Company.php` (UPDATED)
- `app/Http/Controllers/Admin/CompanyKycController.php` (UPDATED)

### Frontend
- `frontend/src/pages/dashboard/wallet-summary.js` (UPDATED)

## Summary

The system now automatically creates a PalmPay master wallet for each company when activated by admin. This wallet is used for receiving funds from customers, while the settlement account (which can be any bank) is used for withdrawals. The wallet page shows the PalmPay account, and the withdraw page defaults to the settlement account.

---

**Status**: Implementation Complete, Pending IP Whitelisting
**Date**: February 18, 2026
**Next Action**: Whitelist IP 105.112.30.197 with PalmPay
