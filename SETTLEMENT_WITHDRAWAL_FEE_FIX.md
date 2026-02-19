# Settlement Withdrawal Fee Handling Fix

## Problem
When companies withdraw their settled funds (via `/api/v1/merchant/transfer`), the system was:
- NOT charging the configured payout fee (`payout_palmpay_charge_type` and `payout_palmpay_charge_value`)
- NOT tracking PalmPay's provider fee
- NOT calculating profit margins

This meant:
- Companies were withdrawing for free (losing revenue)
- No visibility into PalmPay's charges for withdrawals
- Impossible to calculate actual profit from withdrawal fees

## Solution

### 1. Updated MerchantApiController::initiateTransfer()

Now the withdrawal endpoint:

#### Charges Configured Payout Fee
```php
// Get from settings
$chargeType = $settings->payout_palmpay_charge_type; // FLAT or PERCENT
$chargeValue = $settings->payout_palmpay_charge_value; // e.g., 15
$chargeCap = $settings->payout_palmpay_charge_cap; // optional cap

// Calculate fee
if ($chargeType === 'PERCENT') {
    $payoutFee = ($amount * $chargeValue) / 100;
    if ($chargeCap && $payoutFee > $chargeCap) {
        $payoutFee = $chargeCap;
    }
} else {
    $payoutFee = $chargeValue; // Flat fee
}
```

#### Deducts Total Amount
```php
$totalDeduction = $amount + $payoutFee;
// Deducts from company wallet
```

#### Tracks PalmPay Provider Fee
```php
// Extract from PalmPay response
$palmpayFee = $response['data']['fee']['fee'] / 100;
$palmpayVat = $response['data']['fee']['vat'] / 100;
$totalProviderFee = $palmpayFee + $palmpayVat;
```

#### Stores Complete Fee Breakdown
```php
Transaction::create([
    'amount' => $amount,
    'fee' => $payoutFee,              // What we charge company
    'provider_fee' => $totalProviderFee, // What PalmPay charges us
    'total_amount' => $totalDeduction,
    'metadata' => [
        'payout_fee_charged' => $payoutFee,
        'palmpay_provider_fee' => $palmpayFee,
        'palmpay_vat' => $palmpayVat,
        'total_provider_fee' => $totalProviderFee,
        'net_profit' => $payoutFee - $totalProviderFee
    ]
]);
```

#### Logs Profit Calculation
```php
Log::info('Company Payout - PalmPay Provider Fee', [
    'company_id' => $company->id,
    'our_fee_charged' => $payoutFee,
    'palmpay_fee' => $palmpayFee,
    'total_provider_fee' => $totalProviderFee,
    'net_profit' => $payoutFee - $totalProviderFee
]);
```

## Example Scenarios

### Scenario 1: Flat Fee (Default)
Admin configured: `payout_palmpay_charge_type = FLAT`, `payout_palmpay_charge_value = 15`

Company withdraws ₦10,000:
- Amount: ₦10,000
- Our Fee: ₦15 (flat)
- PalmPay Fee: ₦25 (from response)
- Total Deducted: ₦10,015
- Net Profit: ₦15 - ₦25 = -₦10 (loss)

### Scenario 2: Percentage Fee
Admin configured: `payout_palmpay_charge_type = PERCENT`, `payout_palmpay_charge_value = 0.5`, `payout_palmpay_charge_cap = 100`

Company withdraws ₦10,000:
- Amount: ₦10,000
- Our Fee: ₦50 (0.5% of 10,000)
- PalmPay Fee: ₦25 (from response)
- Total Deducted: ₦10,050
- Net Profit: ₦50 - ₦25 = ₦25 (profit)

Company withdraws ₦50,000:
- Amount: ₦50,000
- Our Fee: ₦100 (0.5% = ₦250, but capped at ₦100)
- PalmPay Fee: ₦25 (from response)
- Total Deducted: ₦50,100
- Net Profit: ₦100 - ₦25 = ₦75 (profit)

## API Response Updated

Now returns fee breakdown:
```json
{
  "status": true,
  "message": "Transfer successful",
  "data": {
    "reference": "PWV_OUT_ABC123",
    "status": "successful",
    "amount": 10000,
    "fee": 15,
    "total_deducted": 10015
  }
}
```

## Balance Check Updated

Now checks for total amount including fee:
```
Required Balance = Amount + Fee
Error: "Insufficient balance. Required: 10015 (Amount: 10000 + Fee: 15)"
```

## Deployment

```bash
git pull origin main
php artisan config:clear
php artisan cache:clear
```

No migration needed - just code changes.

## Monitoring

Check logs for withdrawal profit tracking:
```bash
tail -f storage/logs/laravel.log | grep "Company Payout - PalmPay Provider Fee"
```

## Profit Reports

Now you can generate reports showing:
- Total withdrawal fees collected
- Total PalmPay fees paid for withdrawals
- Net profit from withdrawals

```sql
SELECT 
  DATE(created_at) as date,
  COUNT(*) as withdrawals,
  SUM(amount) as total_withdrawn,
  SUM(fee) as fees_collected,
  SUM(provider_fee) as palmpay_fees_paid,
  SUM(fee - provider_fee) as net_profit,
  ROUND((SUM(fee - provider_fee) / SUM(fee)) * 100, 2) as profit_margin_pct
FROM transactions
WHERE category = 'transfer_out'
  AND status = 'success'
  AND is_test = 0
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

## Admin Configuration

Admins can adjust withdrawal fees via:
- Admin Panel → Settings → Bank Charges
- Look for "Settlement Withdrawal (PalmPay)" section
- Set Type (FLAT or PERCENT), Value, and optional Cap
