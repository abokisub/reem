# VA Deposit Fee Configuration Fix - Complete Solution

## THE PROBLEM

You updated the fee to 0.70% in admin panel at `/secure/discount/banks` under "Funding with Bank Transfer", but VA deposits still charge 0.5%.

## ROOT CAUSE

1. **Admin Panel** updates `transfer_charge_*` columns when you change "Funding with Bank Transfer"
2. **FeeService** looks for `virtual_funding_*` columns for VA deposits
3. **These columns don't exist** in the database
4. **FeeService falls back** to hardcoded 0.5%

## THE SOLUTION

### 1. Add Missing Columns
Created migration `2026_02_20_180000_add_virtual_funding_columns_to_settings.php`:
- Adds `virtual_funding_type`, `virtual_funding_value`, `virtual_funding_cap` columns
- Copies current `transfer_charge_*` values as initial values

### 2. Sync Admin Panel Updates
Updated `AdminController::updateBankCharges()`:
- When you update "Funding with Bank Transfer" in admin panel
- It now updates BOTH `transfer_charge_*` AND `virtual_funding_*` columns
- This keeps them in sync

### 3. FeeService Already Correct
`FeeService.php` already looks for `virtual_funding_*` for VA deposits:
```php
$typeMap = [
    'va_deposit' => 'virtual_funding',  // ✅ Correct
    'transfer' => 'transfer_charge',
    ...
];
```

## HOW IT WORKS NOW

```
Admin Panel: "Funding with Bank Transfer"
    ↓
Updates: transfer_charge_* AND virtual_funding_*
    ↓
VA Deposit Webhook
    ↓
FeeService reads: virtual_funding_*
    ↓
Charges correct fee (0.70% or whatever you set)
```

## DEPLOYMENT STEPS

### 1. Push to GitHub
```bash
./FIX_VA_DEPOSIT_FEE_COMPLETE.sh
```

### 2. Deploy on Server
```bash
ssh aboksdfs@server350.web-hosting.com
cd /home/aboksdfs/app.pointwave.ng
git pull origin main
php artisan migrate
php artisan cache:clear
php artisan config:clear
```

### 3. Verify
- Go to admin panel `/secure/discount/banks`
- Check "Funding with Bank Transfer" shows 0.70%
- Make a test VA deposit
- Check the fee charged matches 0.70%

## FILES MODIFIED

1. **database/migrations/2026_02_20_180000_add_virtual_funding_columns_to_settings.php** (NEW)
   - Adds virtual_funding_* columns

2. **app/Http/Controllers/API/AdminController.php**
   - Line ~3860: Now syncs transfer_charge_* to virtual_funding_*

3. **app/Services/FeeService.php**
   - No changes needed (already correct)

## VERIFICATION

After deployment, check logs for new deposit:
```bash
tail -f storage/logs/laravel.log | grep "Virtual Account Credited"
```

Look for:
```json
{
  "charge_config": {
    "model": "system_default_percentage",  // NOT "hardcoded_fallback"
    "type": "PERCENT",
    "value": 0.7,
    "fee": 0.7,
    "net": 99.3
  }
}
```

## WHY THIS APPROACH?

- **Separation of Concerns**: VA deposits can have different fees than other transfers
- **Backward Compatible**: Existing code continues to work
- **Admin Panel Friendly**: You only update one place ("Funding with Bank Transfer")
- **Future Proof**: Can add separate VA deposit fee section later if needed

## WHAT YOU SEE IN ADMIN PANEL

The admin panel at `/secure/discount/banks` has 4 sections:

1. **Funding with Bank Transfer** → Controls VA deposit fees (virtual_funding_*)
2. **Internal Transfer (Wallet)** → Controls wallet transfers (wallet_charge_*)
3. **Settlement Withdrawal (PalmPay)** → Controls PalmPay payouts (payout_palmpay_charge_*)
4. **External Transfer (Other Banks)** → Controls bank transfers (payout_bank_charge_*)

When you update #1, it now updates the correct columns that FeeService reads for VA deposits.
