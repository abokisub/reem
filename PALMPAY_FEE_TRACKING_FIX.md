# PalmPay Provider Fee Tracking Fix

## Problem
The system was not capturing or tracking the fees that PalmPay charges for processing transfers. When PalmPay processes a transfer, they return their fee in the response:

```json
{
  "data": {
    "amount": 10000,
    "fee": {
      "fee": 2500,  // ₦25.00 in kobo
      "vat": 0
    }
  }
}
```

This means:
- We charge customers our fee (e.g., ₦50)
- PalmPay charges us their fee (e.g., ₦25)
- We were not tracking PalmPay's fee, making it impossible to calculate actual profit margins

## Solution

### 1. Added `provider_fee` Column
Created migration to add a dedicated column for tracking provider fees:
- `provider_fee` - Stores the total fee charged by the payment provider (PalmPay)

### 2. Updated TransferService
Modified `app/Services/PalmPay/TransferService.php` to:
- Extract PalmPay fee from response: `$response['data']['fee']['fee']`
- Extract PalmPay VAT from response: `$response['data']['fee']['vat']`
- Convert from kobo to naira (divide by 100)
- Store in `provider_fee` column
- Store detailed breakdown in transaction metadata
- Log provider fees for monitoring

### 3. Updated Transaction Model
- Added `provider_fee` to fillable fields
- Added `provider_fee` to casts as decimal

## What Gets Tracked Now

For each transfer, we now track:
- `fee` - What we charge the customer (e.g., ₦50)
- `provider_fee` - What PalmPay charges us (e.g., ₦25)
- `metadata.palmpay_provider_fee` - PalmPay base fee
- `metadata.palmpay_vat` - PalmPay VAT
- `metadata.total_provider_fee` - Total provider fee

## Profit Calculation

Now you can calculate actual profit:
```
Gross Profit = fee - provider_fee
Example: ₦50 - ₦25 = ₦25 profit per transfer
```

## Deployment

```bash
git pull origin main
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
```

## Monitoring

Check logs for provider fee tracking:
```bash
tail -f storage/logs/laravel.log | grep "PalmPay Provider Fee Charged"
```

You'll see entries like:
```
PalmPay Provider Fee Charged {
  "transaction_id": "txn_xxx",
  "palmpay_fee": 25.00,
  "palmpay_vat": 0.00,
  "total_provider_fee": 25.00,
  "our_fee_charged_to_customer": 50.00
}
```

## Reports

You can now create reports showing:
- Total fees collected from customers
- Total fees paid to PalmPay
- Net profit from transfers
- Profit margin percentage

Example query:
```sql
SELECT 
  DATE(created_at) as date,
  COUNT(*) as transfers,
  SUM(fee) as customer_fees,
  SUM(provider_fee) as palmpay_fees,
  SUM(fee - provider_fee) as net_profit,
  ROUND((SUM(fee - provider_fee) / SUM(fee)) * 100, 2) as profit_margin_pct
FROM transactions
WHERE type = 'debit' 
  AND category = 'transfer_out'
  AND status = 'successful'
GROUP BY DATE(created_at)
ORDER BY date DESC;
```
