# Fix: RA Transactions Not Showing Transfers for Companies

## Issue Identified
Company transfers were not showing up in the RA Transactions page, even though they were visible in the admin panel.

## Root Cause
The `AllRATransactions` method in `app/Http/Controllers/API/Trans.php` was filtering for specific transaction types:
- `va_deposit`
- `api_transfer`
- `company_withdrawal`
- `refund`

However, transfers created by `TransferPurchase.php` were being assigned:
- `transaction_type = 'transfer'` (for regular transfers)
- `transaction_type = 'settlement_withdrawal'` (for settlement withdrawals)

These types were NOT in the filter list, so they were excluded from the RA Transactions page.

## Fix Applied
Updated the `AllRATransactions` filter to include:
```php
$query->whereIn('transaction_type', [
    'va_deposit', 
    'api_transfer', 
    'company_withdrawal', 
    'refund', 
    'transfer',                    // ← ADDED
    'settlement_withdrawal'        // ← ADDED
]);
```

## Files Changed
- `app/Http/Controllers/API/Trans.php` - Added transfer types to filter

## Deployment Instructions

### On Live Server:
```bash
cd app.pointwave.ng
git pull origin main
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Testing
After deployment, log in as a company user and:
1. Go to RA Transactions page
2. You should now see transfer/withdrawal transactions
3. Verify the transfers you made at 18:21:58 and 18:23:51 are visible

## Summary
✓ Fixed RA Transactions filter to include 'transfer' and 'settlement_withdrawal' types
✓ Company transfers now visible in RA Transactions page
✓ No database changes required
✓ Only backend cache clear needed
