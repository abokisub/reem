# Charges System - Ready for Testing

## ✅ Implementation Complete

The charges calculation system has been fully implemented and is ready for testing with real webhooks.

## What Was Fixed

### 1. Problem Identified
- WebhookHandler was hardcoded to set `fee = 0` for all incoming payments
- Charges were configured correctly (0.5% capped at ₦500) but not being applied
- Net amount (amount after fees) was not being tracked

### 2. Solution Implemented
- Updated `WebhookHandler.php` to use the existing `ChargeCalculator` service
- Charges are now calculated automatically for all incoming PalmPay payments
- Net amount is tracked and used for wallet credits and settlements
- Charge details are stored in transaction metadata for audit trail

## Current Configuration

**PalmPay Virtual Account Charge:**
- Type: PERCENT
- Value: 0.50% (half a percent)
- Cap: ₦500 (maximum charge)
- Status: Active

## Charge Examples

| Customer Pays | Platform Fee | Company Receives |
|--------------|-------------|------------------|
| ₦100 | ₦0.50 | ₦99.50 |
| ₦1,000 | ₦5.00 | ₦995.00 |
| ₦10,000 | ₦50.00 | ₦9,950.00 |
| ₦100,000 | ₦500.00 | ₦99,500.00 |
| ₦200,000 | ₦500.00 | ₦199,500.00 |

## How to Test

### Step 1: Send Test Payment
Send ₦100 to your PalmPay virtual account:
- Account Number: `6644694207`
- Bank: PalmPay

### Step 2: Check Transaction
After webhook is received, check the transaction:

```bash
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$txn = \Illuminate\Support\Facades\DB::table('transactions')
    ->orderBy('created_at', 'desc')
    ->first();

echo \"Transaction: {\$txn->transaction_id}\n\";
echo \"Gross Amount: ₦{\$txn->amount}\n\";
echo \"Fee: ₦{\$txn->fee}\n\";
echo \"Net Amount: ₦{\$txn->net_amount}\n\";
echo \"Status: {\$txn->status}\n\";
"
```

### Step 3: Verify Calculations
For ₦100 payment:
- ✓ Gross Amount should be: ₦100.00
- ✓ Fee should be: ₦0.50 (0.5% of 100)
- ✓ Net Amount should be: ₦99.50

### Step 4: Check Wallet Balance
Verify wallet was credited with NET amount (not gross):

```bash
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$wallet = \Illuminate\Support\Facades\DB::table('company_wallets')
    ->where('company_id', 2)
    ->where('currency', 'NGN')
    ->first();

echo \"Wallet Balance: ₦{\$wallet->balance}\n\";
"
```

### Step 5: Check Logs
View the webhook processing logs:

```bash
tail -f storage/logs/laravel.log | grep "Virtual Account Credited"
```

You should see:
```
Virtual Account Credited
- gross_amount: 100
- fee: 0.5
- net_amount: 99.5
- charge_config: {type: PERCENT, value: 0.5, cap: 500}
```

## Files Modified

1. **app/Services/PalmPay/WebhookHandler.php**
   - Added `use App\Services\ChargeCalculator;`
   - Replaced hardcoded `'fee' => 0` with dynamic calculation
   - Uses `ChargeCalculator::getServiceCharge()` method
   - Credits NET amount to wallet (not gross)
   - Queues NET amount for settlement (not gross)
   - Stores charge details in transaction metadata

## Database Schema

### Transactions Table
```
- amount: Gross amount (what customer paid)
- fee: Platform charge
- net_amount: Net amount (what company receives)
- total_amount: Same as gross amount
- metadata: JSON with charge details
```

### Service Charges Table
```
- company_id: 1 (global) or specific company
- service_category: 'payment'
- service_name: 'palmpay_va'
- charge_type: 'PERCENT' or 'FLAT'
- charge_value: 0.50 (for 0.5%)
- charge_cap: 500.00 (max charge)
- is_active: 1
```

## Admin Configuration

To change charges:
1. Go to: `/secure/discount/other`
2. Update "PalmPay Virtual Account" charge
3. Changes apply immediately to new transactions

## Other Charges

The system also supports:
- **Pay with Wallet**: 1.2% capped at ₦1,000
- **Payout to Bank**: ₦30 flat
- **Payout to PalmPay**: ₦15 flat

These are configured in the `settings` table and used by other parts of the system.

## Next Steps

1. ✅ Configuration verified (0.5% capped at ₦500)
2. ✅ Code updated to calculate charges
3. ✅ Settlement integration updated
4. ⏳ **TEST WITH REAL WEBHOOK** (send ₦100 to PalmPay account)
5. ⏳ Verify fee calculation is correct
6. ⏳ Verify wallet is credited with net amount
7. ⏳ Deploy to production

## Support

If charges are not being applied:
1. Check `service_charges` table has the record
2. Check `is_active = 1` for the charge
3. Check logs for "Virtual Account Credited" message
4. Verify `ChargeCalculator` service is working

## Notes

- Old transactions (before fix) will have `fee = 0` and `net_amount = NULL`
- New transactions (after fix) will have correct fees and net amounts
- Charges are per-company configurable (falls back to global if not set)
- All amounts are rounded to 2 decimal places
- Fees are retained by the platform (not credited to company wallet)
