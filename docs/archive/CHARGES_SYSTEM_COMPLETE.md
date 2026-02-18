# Charges System - Complete Implementation

## Summary
Fixed the charges calculation system for PalmPay Virtual Account payments. The system now correctly applies the configured 0.5% charge (capped at ₦500) to all incoming payments.

## Problem Identified
1. **WebhookHandler was hardcoded** to set `'fee' => 0` for all transactions
2. **Charges were not being calculated** even though configuration was correct
3. **Net amount was not being tracked** (amount after deducting fees)

## Configuration
The charges are stored in the `service_charges` table:

```
Company ID: 1 (Global/Default)
Service Category: payment
Service Name: palmpay_va
Display Name: PalmPay Virtual Account
Charge Type: PERCENT
Charge Value: 0.50 (0.5%)
Charge Cap: 500.00 (₦500 max)
Active: Yes
```

## Charge Calculation Examples

| Amount | Fee (0.5%) | Net Amount | Notes |
|--------|-----------|------------|-------|
| ₦100 | ₦0.50 | ₦99.50 | Below cap |
| ₦1,000 | ₦5.00 | ₦995.00 | Below cap |
| ₦10,000 | ₦50.00 | ₦9,950.00 | Below cap |
| ₦100,000 | ₦500.00 | ₦99,500.00 | At cap |
| ₦200,000 | ₦500.00 | ₦199,500.00 | Capped at ₦500 |

## Implementation Details

### 1. WebhookHandler Changes
**File**: `app/Services/PalmPay/WebhookHandler.php`

**Changes Made**:
- Added charge calculation logic that reads from `service_charges` table
- Calculates fee based on charge type (PERCENT or FLAT)
- Applies cap if configured
- Stores charge details in transaction metadata
- Credits NET amount to wallet (not gross amount)
- Queues NET amount for settlement (not gross amount)

**Calculation Logic**:
```php
// Get charge configuration
$chargeConfig = DB::table('service_charges')
    ->where('company_id', $virtualAccount->company_id)
    ->where('service_category', 'payment')
    ->where('service_name', 'palmpay_va')
    ->where('is_active', true)
    ->first();

// Fallback to global settings
if (!$chargeConfig) {
    $chargeConfig = DB::table('service_charges')
        ->where('company_id', 1)
        ->where('service_category', 'payment')
        ->where('service_name', 'palmpay_va')
        ->where('is_active', true)
        ->first();
}

// Calculate fee
if ($chargeConfig->charge_type === 'PERCENT') {
    $fee = ($amount * $chargeConfig->charge_value) / 100;
    
    // Apply cap
    if ($chargeConfig->charge_cap && $fee > $chargeConfig->charge_cap) {
        $fee = $chargeConfig->charge_cap;
    }
} elseif ($chargeConfig->charge_type === 'FLAT') {
    $fee = $chargeConfig->charge_value;
}

$fee = round($fee, 2);
$netAmount = $amount - $fee;
```

### 2. Transaction Record
Transactions now store:
- `amount`: Gross amount (what customer paid)
- `fee`: Charge deducted
- `net_amount`: Net amount (what company receives)
- `total_amount`: Same as gross amount
- `metadata`: Includes charge configuration used

### 3. Settlement Integration
- Settlement queue uses NET amount (not gross)
- Company wallet is credited with NET amount (not gross)
- Fees are retained by the platform

## Admin Configuration
Charges can be configured at:
- **URL**: `/secure/discount/other`
- **Table**: `service_charges`
- **Scope**: Per company or global (company_id = 1)

## Verification

### Current Status
✓ Charge configuration is correct (0.5% capped at ₦500)
✓ WebhookHandler updated to calculate charges
✓ Settlement uses net amount
✓ Wallet credits use net amount

### Existing Transactions
Old transactions (before fix):
- Fee: ₦0.00 (charges not applied)
- Net Amount: NULL

New transactions (after fix):
- Fee: Calculated correctly
- Net Amount: Tracked properly

## Testing
To test the charges system:

1. **Send a test payment** to your PalmPay virtual account
2. **Check the transaction** in the database:
   ```sql
   SELECT transaction_id, amount, fee, net_amount, total_amount, metadata
   FROM transactions
   ORDER BY created_at DESC
   LIMIT 1;
   ```
3. **Verify the calculation**:
   - For ₦100: Fee should be ₦0.50, Net should be ₦99.50
   - For ₦100,000: Fee should be ₦500.00, Net should be ₦99,500.00

4. **Check wallet balance**:
   - Wallet should be credited with NET amount (not gross)

5. **Check settlement queue**:
   - Settlement amount should be NET amount (not gross)

## Files Modified
1. `app/Services/PalmPay/WebhookHandler.php` - Added charge calculation logic
2. `test_charge_calculation_complete.php` - Verification script

## Next Steps
1. Test with a real webhook (send ₦100 to your PalmPay account)
2. Verify fee is calculated correctly (₦0.50)
3. Verify wallet is credited with net amount (₦99.50)
4. Check logs for charge calculation details

## Notes
- Charges are configurable per company (company-specific overrides)
- Falls back to global settings (company_id = 1) if no company-specific config
- Supports both PERCENT and FLAT charge types
- Cap is optional (set to NULL or 0 for no cap)
- All amounts are rounded to 2 decimal places
