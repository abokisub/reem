# Testing Refund & Notification Features

## Overview
This guide helps you test the new refund and notification features for RA Transactions.

## Backend Changes Deployed

### 1. New Migration
- **File**: `database/migrations/2026_02_18_180000_add_refund_columns_to_transactions.php`
- **Adds**: `is_refunded` and `refund_transaction_id` columns to transactions table

### 2. Updated Model
- **File**: `app/Models/Transaction.php`
- **Added**: `is_refunded` and `refund_transaction_id` to fillable fields

### 3. API Endpoints (Already Created)
- **POST** `/api/transactions/{id}/refund` - Initiate refund
- **POST** `/api/transactions/{id}/resend-notification` - Resend webhook
- **GET** `/api/system/all/ra-history/records/{id}/secure/export` - Export CSV

## Deployment Steps

### On Production Server

```bash
# 1. Pull latest code
cd /home/aboksdfs/app.pointwave.ng
git pull origin main

# 2. Run the deployment script
./DEPLOY_AND_TEST_REFUND.sh
```

This script will:
- Run the migration to add refund columns
- Test the refund/notification logic
- Show you test transaction details
- Verify wallet balances
- Check webhook configuration

## Frontend Changes

### Updated File
- **File**: `frontend/src/pages/dashboard/RATransactions.js`
- **Features**:
  - Professional styling with green amounts
  - Customer names displayed (from metadata)
  - View modal with complete transaction details
  - "Initiate Refund" button (red, only for successful transactions)
  - "Resend Notification" button (blue)
  - "Export" button (downloads CSV)
  - Real-time search functionality
  - Settlement status chips

### Build & Deploy Frontend

```bash
# On your local machine or server
cd frontend
npm run build

# Upload the build folder to:
# /home/aboksdfs/app.pointwave.ng/public/
```

## Testing the Features

### 1. Test Refund Feature

**Steps:**
1. Login as company user (abokisub@gmail.com)
2. Go to RA Transactions page
3. Find a successful transaction
4. Click "Initiate Refund" button
5. Confirm the action

**Expected Result:**
- Success message appears
- New refund transaction created (type: debit, category: refund)
- Original transaction marked as refunded
- Wallet balance decreased by refund amount
- Refund button disabled for that transaction

**What Happens Behind the Scenes:**
```
1. Creates refund transaction:
   - Transaction ID: RFD_xxxxx
   - Type: debit
   - Category: refund
   - Amount: Same as original
   - Reference: REFUND_{original_reference}

2. Updates original transaction:
   - is_refunded = true
   - refund_transaction_id = {new_refund_id}

3. Updates wallet:
   - Debits balance by refund amount
   - Debits ledger_balance by refund amount
```

### 2. Test Resend Notification Feature

**Prerequisites:**
- Company must have webhook URL configured
- Transaction must exist

**Steps:**
1. Login as company user
2. Go to RA Transactions page
3. Click "Resend Notification" button on any transaction
4. Check webhook logs

**Expected Result:**
- Success message appears
- Webhook sent to company's webhook URL
- Entry created in webhook_logs table
- Company receives webhook notification

**Webhook Payload:**
```json
{
  "event": "transaction.success",
  "data": {
    "transaction_id": "txn_xxxxx",
    "reference": "REFxxxxx",
    "amount": 100.00,
    "fee": 0.00,
    "net_amount": 100.00,
    "status": "success",
    "type": "credit",
    "category": "virtual_account_credit",
    "description": "...",
    "created_at": "2026-02-18T...",
    "metadata": {
      "sender_name": "Customer Name",
      "sender_account": "1234567890"
    }
  }
}
```

### 3. Test Export Feature

**Steps:**
1. Login as company user
2. Go to RA Transactions page
3. Click "Export" button

**Expected Result:**
- CSV file downloads automatically
- Filename: `transactions_YYYY-MM-DD_HHMMSS.csv`
- Contains all transactions with customer names

**CSV Columns:**
- Transaction ID
- Reference
- Customer Name
- Amount
- Fee
- Net Amount
- Status
- Type
- Category
- Description
- Date

## Verification Checklist

### Backend
- [ ] Migration ran successfully
- [ ] `is_refunded` column exists in transactions table
- [ ] `refund_transaction_id` column exists in transactions table
- [ ] Routes are accessible (check with test script)
- [ ] TransactionController methods work

### Frontend
- [ ] Build completed without errors
- [ ] Build folder uploaded to server
- [ ] RA Transactions page loads
- [ ] Customer names display correctly
- [ ] Buttons are visible and styled correctly
- [ ] Search functionality works
- [ ] View modal opens and shows details

### Functionality
- [ ] Refund creates new transaction
- [ ] Refund updates wallet balance
- [ ] Refund marks original transaction
- [ ] Refund button disables after use
- [ ] Webhook sends successfully
- [ ] Webhook logs are created
- [ ] Export downloads CSV file
- [ ] CSV contains correct data

## Troubleshooting

### Refund Fails

**Error: "Only successful transactions can be refunded"**
- Solution: Only transactions with status='success' can be refunded

**Error: "Transaction already refunded"**
- Solution: Transaction has already been refunded, check is_refunded column

**Error: "Insufficient balance"**
- Solution: Company wallet doesn't have enough balance for refund

### Webhook Fails

**Error: "No webhook URL configured"**
- Solution: Set webhook_url in companies table for the company

**Error: "Webhook delivery failed"**
- Solution: Check if webhook URL is accessible and responding

### Export Fails

**Error: "Export failed"**
- Solution: Check Laravel logs for detailed error message

## API Testing with cURL

### Test Refund Endpoint
```bash
curl -X POST https://app.pointwave.ng/api/transactions/1/refund \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### Test Resend Notification
```bash
curl -X POST https://app.pointwave.ng/api/transactions/1/resend-notification \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### Test Export
```bash
curl -X GET https://app.pointwave.ng/api/system/all/ra-history/records/2/secure/export \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -o transactions.csv
```

## Database Queries for Verification

### Check Refund Columns
```sql
SHOW COLUMNS FROM transactions LIKE 'is_refunded';
SHOW COLUMNS FROM transactions LIKE 'refund_transaction_id';
```

### Find Refunded Transactions
```sql
SELECT id, transaction_id, amount, status, is_refunded, refund_transaction_id
FROM transactions
WHERE is_refunded = 1;
```

### Find Refund Transactions
```sql
SELECT id, transaction_id, amount, category, reference
FROM transactions
WHERE category = 'refund'
ORDER BY id DESC;
```

### Check Webhook Logs
```sql
SELECT id, company_id, event, url, status_code, status, created_at
FROM webhook_logs
ORDER BY id DESC
LIMIT 10;
```

## Important Notes

1. **Refund Debits Wallet**: Refund will deduct money from company wallet
2. **Authentication Required**: All endpoints require Sanctum token
3. **Company Verification**: Endpoints verify transaction belongs to user's company
4. **Webhook URL**: Must be configured for resend notification to work
5. **Frontend Build**: Must rebuild and upload after any React changes

## Support

If you encounter any issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Run test script: `php test_refund_notification.php`
3. Check database structure
4. Verify API routes are registered
5. Check frontend console for errors
