# Transaction Display Issues - Analysis & Fix Plan

## Current Status ✅

### What's Working
1. **Fee Calculation** - Working perfectly! ✅
   - From logs: ₦1000 → ₦5 fee (0.5%) → ₦995 net
   - Fee service is calculating correctly
   - Webhook is storing fee data properly

2. **Backend Data Storage** - Complete! ✅
   - `balance_before` and `balance_after` are being saved
   - Sender information is being saved in metadata
   - All transaction data is in the database

## Issues Found ❌

### Issue 1: Missing Balance Display on Wallet Page
**Problem**: Old Balance and New Balance showing ₦0.00 on `/dashboard/wallet`

**Root Cause**: 
- Old transactions don't have `balance_before` and `balance_after` populated
- Only NEW transactions (after the webhook fix) have this data

**Solution**: 
- Backfill old transactions with balance data
- Update frontend to handle missing balance gracefully

### Issue 2: Missing Sender Information on Receipt
**Problem**: Receipt shows "N/A" for:
- Sender Name
- Sender Account  
- Sender Bank

**Root Cause**: Frontend is looking for wrong field names

**Current Frontend Code**:
```javascript
const senderName = metadata.sender_name || transaction.customer_name || 'N/A';
const senderAccount = metadata.sender_account || transaction.customer_account || 'N/A';
```

**What's Actually in Database** (from webhook logs):
```json
{
  "sender_name": "ABOKI TELECOMMUNICATION SERVICES",
  "sender_account": "7040540018",
  "sender_bank": "OPAY",
  "sender_account_name": "ABOKI TELECOMMUNICATION SERVICES",
  "sender_bank_name": "OPAY"
}
```

**Issue**: The API endpoint `/api/system/all/ra-history/records` is NOT returning the metadata properly!

### Issue 3: Hardcoded Balance Values
**Problem**: Balance fields might be hardcoded or not fetched from API

**Need to Check**: The API endpoint that returns transaction data

## Fix Plan

### Step 1: Check API Endpoint
Need to verify what `/api/system/all/ra-history/records` returns

### Step 2: Update Backend API (if needed)
Ensure the API returns:
- `metadata` (with sender info)
- `balance_before`
- `balance_after`
- `fee`
- `net_amount`

### Step 3: Update Frontend
Fix the receipt page to display:
- Sender information from metadata
- Balance before/after
- Fee breakdown

### Step 4: Backfill Old Transactions
Create a script to calculate and populate balance_before/balance_after for old transactions

## Test Transaction from Logs

```
Transaction Details:
- Amount: ₦1,000.00
- Fee: ₦5.00 (0.5%)
- Net: ₦995.00
- Sender: ABOKI TELECOMMUNICATION SERVICES
- Account: 7040540018
- Bank: OPAY
- VA: 6644694207
```

This transaction should display:
- ✅ Amount: ₦1,000.00
- ✅ Fee: ₦5.00
- ✅ Net: ₦995.00
- ❌ Sender Name: ABOKI TELECOMMUNICATION SERVICES (currently showing N/A)
- ❌ Sender Account: 7040540018 (currently showing N/A)
- ❌ Sender Bank: OPAY (currently showing N/A)
- ❌ Old Balance: (should show previous balance)
- ❌ New Balance: (should show new balance)

## Next Steps

1. Find and check the API controller that handles `/api/system/all/ra-history/records`
2. Verify it returns metadata and balance fields
3. Update frontend to display the data correctly
4. Create backfill script for old transactions
5. Test with the recent ₦1000 transaction

## Files to Check/Update

### Backend
- [ ] Find controller for `/api/system/all/ra-history/records`
- [ ] Verify it returns all transaction fields
- [ ] Create backfill script for old transactions

### Frontend
- [ ] `frontend/src/pages/dashboard/RATransactionDetails.js` - Update to show balance
- [ ] `frontend/src/pages/dashboard/RATransactions.js` - May need updates
- [ ] Check wallet page component (need to find it)

## Questions to Answer

1. What controller handles `/api/system/all/ra-history/records`?
2. Does it return the `metadata` field?
3. Does it return `balance_before` and `balance_after`?
4. Where is the Wallet page component located?
