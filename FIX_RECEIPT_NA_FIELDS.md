# Fix Receipt N/A Fields

## Problem
When viewing transaction receipts, several fields were showing "N/A" in the SENDER DETAILS and RECIPIENT DETAILS sections, especially for transfer/withdrawal transactions.

## Root Cause
The receipt component (`RATransactionDetails.js`) was designed only for deposit transactions and was trying to extract sender information from metadata. For transfer/withdrawal transactions:
- There is no "sender" in metadata because the company IS the sender
- The recipient information wasn't being displayed properly
- The component didn't differentiate between credit (deposits) and debit (transfers) transactions

## Solution
Updated the receipt component to properly handle both transaction types:

### For Credit Transactions (Deposits):
- **Sender Details**: Shows the external sender's information from metadata
- **Recipient Details**: Shows the company's virtual account information

### For Debit Transactions (Transfers/Withdrawals):
- **Sender Details**: Shows the company's virtual account information (the company is sending)
- **Recipient Details**: Shows the recipient's bank account information

## Files Changed
- `frontend/src/pages/dashboard/RATransactionDetails.js` - Updated sender/recipient logic

## Deployment Steps

### 1. Pull Latest Code
```bash
cd ~/app.pointwave.ng
git pull origin main
```

### 2. Build Frontend
```bash
cd frontend
npm run build
```

### 3. Clear Browser Cache
After deployment, clear your browser cache or do a hard refresh (Ctrl+Shift+R or Cmd+Shift+R) to see the changes.

## Expected Behavior After Fix

### Deposit Receipt (Credit Transaction):
**Sender Details:**
- Name: Customer's name (from bank transfer)
- Account: Customer's account number
- Bank: Customer's bank name

**Recipient Details:**
- Account Name: Virtual account name
- Account Number: Virtual account number
- Bank: PalmPay

### Transfer/Withdrawal Receipt (Debit Transaction):
**Sender Details:**
- Name: Company name or virtual account name
- Account: Company's virtual account number
- Bank: PalmPay

**Recipient Details:**
- Account Name: Recipient's account name
- Account Number: Recipient's account number
- Bank: Recipient's bank name

## Testing
After deployment:
1. View a deposit transaction receipt - should show sender bank details
2. View a transfer transaction receipt - should show recipient bank details
3. Verify no "N/A" values appear (except for truly missing data)

## Notes
- The fix uses the `transaction.type` field to determine if it's a credit or debit transaction
- Falls back to company/virtual account info when specific data is missing
- All fields now have proper fallback values to avoid "N/A" display
