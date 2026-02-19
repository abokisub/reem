# PalmPay Error Handling Fix

## Problem Identified

When PalmPay returns error codes like "MC200001: Account balance is insufficient", the system was treating these as "ambiguous" errors and leaving transactions in "processing" status instead of marking them as "failed" and refunding the customer.

### What Was Happening

1. User initiates transfer (₦100 + ₦15 fee = ₦115 deducted from wallet)
2. PalmPay API returns error: `MC200001 - Account balance is insufficient`
3. PalmPayClient throws an exception
4. TransferService catches the exception in the generic catch block
5. Transaction stays in "processing" status (appears as "successful" or "pending" in UI)
6. Customer's money is stuck - not refunded, not transferred

### Root Cause

The `processPalmPayTransfer` method in `TransferService.php` had two paths:

1. **Success path**: Checks `orderStatus` from response data
2. **Exception path**: Catches all exceptions and treats them as "ambiguous"

When PalmPay returns an error code, the PalmPayClient throws an exception BEFORE the TransferService can check the orderStatus. This exception was caught in the generic catch block, which assumed all exceptions were network/timeout issues and left the transaction in "processing" status.

## Fix Applied

Modified `app/Services/PalmPay/TransferService.php` to:

1. Parse the exception message for PalmPay error codes
2. Identify definitive failure codes (MC200001, MC200002, etc.)
3. Immediately trigger refund for definitive failures
4. Only treat network/timeout errors as ambiguous

### Definitive Failure Codes

These error codes now trigger immediate refund:
- `MC200001` - Insufficient balance (PalmPay merchant account)
- `MC200002` - Invalid account
- `MC200003` - Account not found
- `MC200004` - Invalid beneficiary
- `MC200005` - Transaction rejected

## How It Works Now

### Scenario 1: PalmPay Insufficient Balance
1. User initiates transfer
2. PalmPay returns MC200001 error
3. System detects definitive failure
4. Transaction marked as "failed"
5. Customer refunded immediately (₦115 back to wallet)
6. Customer sees "Failed" status with error message

### Scenario 2: Network Timeout
1. User initiates transfer
2. Network timeout occurs
3. System treats as ambiguous
4. Transaction stays in "processing"
5. Webhook or reconciliation will resolve later

## Testing

After deploying, test by:
1. Making a transfer when your PalmPay merchant account has insufficient balance
2. Check transaction status - should be "failed"
3. Check wallet balance - should be refunded
4. Check transaction details - should show error message

## Deployment Steps

```bash
cd app.pointwave.ng
git pull origin main
# No database changes needed
# No cache clear needed
```

## Files Modified

1. `app/Services/PalmPay/TransferService.php` - Added error code detection in catch block

## Summary

- **Problem**: PalmPay error codes were treated as ambiguous, leaving transactions stuck in "processing"
- **Fix**: Parse exception messages for definitive failure codes and trigger immediate refund
- **Result**: Customers get immediate refunds when PalmPay returns definitive errors
