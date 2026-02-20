# Fresh Testing Guide - Complete System Test

## Overview

This guide will help you perform a complete end-to-end test of the system after resetting all transactions and balances.

---

## Step 1: Reset the System

Run the reset script on the server:

```bash
cd /home/aboksdfs/app.pointwave.ng
php RESET_FOR_TESTING.php
```

This will:
- ✅ Delete all transactions
- ✅ Clear webhook logs
- ✅ Reset all balances to zero
- ✅ Clear settlement queue
- ✅ Preserve company and user accounts

After reset, clear Laravel caches:

```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

---

## Step 2: Verify Clean State

### Company Dashboard

1. Login to company dashboard: https://app.pointwave.ng
2. Go to **Wallet** page
3. Verify:
   - ✅ Balance shows ₦0.00
   - ✅ Account number is displayed
   - ✅ Bank details shown
   - ✅ **Transaction history section is HIDDEN**

4. Go to **RA Transactions** page
5. Verify:
   - ✅ No transactions displayed
   - ✅ "No data found" message shown
   - ✅ All 11 columns visible (no cut-off)

### Admin Dashboard

1. Login to admin dashboard: https://app.pointwave.ng/secure
2. Go to **Statement** page
3. Verify:
   - ✅ No transactions displayed
   - ✅ Summary shows all zeros
   - ✅ All 12 columns visible (no cut-off)

4. Go to **Webhook Logs** page
5. Verify:
   - ✅ No webhook events displayed
   - ✅ All 11 columns visible

---

## Step 3: Test Deposit Flow (VA Deposit)

### 3.1 Make a Deposit

1. Get your PalmPay virtual account number from Wallet page
2. Send money to this account from your bank app or USSD
3. Amount: ₦1,000 (for testing)

### 3.2 Verify Webhook Reception

**Admin Dashboard > Webhook Logs:**

1. Wait 5-10 seconds after deposit
2. Refresh the page
3. Verify webhook event appears:
   - ✅ Event Type: `TRANSFER.CREDIT` or `TRANSFER.RECEIVED`
   - ✅ Status: `success`
   - ✅ Direction: `incoming`
   - ✅ Company: Your company name
   - ✅ Amount: ₦1,000
   - ✅ Raw Payload: Click expand, verify JSON data
   - ✅ Response: Click expand, verify response

### 3.3 Verify Transaction Created

**Company Dashboard > RA Transactions:**

1. Refresh the page
2. Verify transaction appears:
   - ✅ Transaction Ref: Shows unique reference
   - ✅ Session ID: Shows session ID
   - ✅ Type: "VA Deposit" (green badge)
   - ✅ Customer: Shows customer name
   - ✅ Amount: ₦1,000 (green, with + sign)
   - ✅ Fee: ₦0 or calculated fee
   - ✅ Net Amount: ₦1,000 minus fee
   - ✅ Status: "SUCCESSFUL" (green badge)
   - ✅ Settlement: "Unsettled" or "Settled" (based on delay)
   - ✅ Date: Shows correct timestamp
   - ✅ Actions: Eye icon (view) and download icon (receipt)

3. Click the **eye icon** to view details
4. Verify all transaction details display correctly

5. Click the **download icon** to get receipt
6. Verify PDF receipt downloads with:
   - ✅ Transaction reference
   - ✅ Amount
   - ✅ Fee
   - ✅ Net amount
   - ✅ Status
   - ✅ Settlement status
   - ✅ Date/time
   - ✅ No "N/A" values

### 3.4 Verify Balance Updated

**Company Dashboard > Wallet:**

1. Refresh the page
2. Verify:
   - ✅ Balance shows ₦1,000 (or ₦1,000 minus fee)
   - ✅ Balance card displays correctly
   - ✅ **Transaction history still HIDDEN**

### 3.5 Verify Admin View

**Admin Dashboard > Statement:**

1. Refresh the page
2. Verify transaction appears:
   - ✅ All 12 columns visible
   - ✅ Transaction Type: "VA Deposit"
   - ✅ Company: Your company name
   - ✅ Customer: Customer name
   - ✅ Amount, Fee, Net Amount all correct
   - ✅ Status: "successful"
   - ✅ Settlement status correct

---

## Step 4: Test Transfer Flow (API Transfer)

### 4.1 Make a Transfer

**Company Dashboard > Transfer:**

1. Click "Transfer" in sidebar
2. Fill in transfer details:
   - Bank: Select any bank
   - Account Number: Enter valid account
   - Amount: ₦500
   - Narration: "Test transfer"
3. Click "Transfer"
4. Confirm the transfer

### 4.2 Verify Transaction Created

**Company Dashboard > RA Transactions:**

1. Refresh the page
2. Verify new transaction appears:
   - ✅ Transaction Ref: Shows unique reference
   - ✅ Session ID: Shows session ID
   - ✅ Type: "Transfer" (blue badge)
   - ✅ Customer: Beneficiary name
   - ✅ Amount: ₦500 (red, with - sign)
   - ✅ Fee: Transfer fee (e.g., ₦10)
   - ✅ Net Amount: ₦510 (amount + fee)
   - ✅ Status: "SUCCESSFUL" or "PROCESSING"
   - ✅ Settlement: "Not Applicable" (transfers don't settle)
   - ✅ Date: Shows correct timestamp

### 4.3 Verify Balance Deducted

**Company Dashboard > Wallet:**

1. Refresh the page
2. Verify:
   - ✅ Balance shows ₦490 (₦1,000 - ₦500 - ₦10 fee)
   - ✅ Balance updated correctly

### 4.4 Verify Webhook Sent (if configured)

**Admin Dashboard > Webhook Logs:**

1. Refresh the page
2. If webhook URL configured, verify:
   - ✅ Event Type: `TRANSFER.COMPLETED` or similar
   - ✅ Direction: `outgoing`
   - ✅ Status: `success` or `pending`
   - ✅ Retry Count: 0 (if successful)

---

## Step 5: Test Settlement Flow

### 5.1 Check Settlement Delay

**Admin Dashboard > Settings:**

1. Go to Settings
2. Check "Settlement Delay" value
3. Note: If set to 0, settlements process immediately

### 5.2 Wait for Settlement

If settlement delay is > 0:
1. Wait for the delay period to pass
2. Or run settlement command manually:
   ```bash
   php artisan settlements:process
   ```

### 5.3 Verify Settlement Status Updated

**Company Dashboard > RA Transactions:**

1. Refresh the page
2. Find the VA deposit transaction
3. Verify:
   - ✅ Settlement status changed from "Unsettled" to "Settled"
   - ✅ Settlement indicator shows green dot

**Admin Dashboard > Statement:**

1. Refresh the page
2. Verify settlement status updated

---

## Step 6: Test Responsive Design

### 6.1 Wallet Page

**Desktop (>1400px):**
- ✅ Balance card full width
- ✅ Account details display properly
- ✅ No transaction history section

**Mobile (<768px):**
- ✅ Balance card stacks vertically
- ✅ Account details readable
- ✅ Withdraw button accessible

### 6.2 RA Transactions Page

**Desktop (>1600px):**
- ✅ All 11 columns visible without scrolling
- ✅ No content cut off
- ✅ Table uses full width

**Tablet (1000-1600px):**
- ✅ Horizontal scroll available
- ✅ All columns accessible
- ✅ Smooth scrolling

**Mobile (<1000px):**
- ✅ Horizontal scroll works
- ✅ All data accessible
- ✅ Actions column visible

### 6.3 Admin Statement Page

**Desktop (>1800px):**
- ✅ All 12 columns visible without scrolling
- ✅ Company and customer columns show full names
- ✅ No content cut off

**Tablet/Mobile:**
- ✅ Horizontal scroll available
- ✅ All columns accessible

---

## Step 7: Test Edge Cases

### 7.1 Failed Transaction

1. Make a transfer to invalid account
2. Verify:
   - ✅ Status shows "FAILED" (red badge)
   - ✅ Balance not deducted
   - ✅ Error message displayed

### 7.2 Pending Transaction

1. Make a transfer during bank downtime
2. Verify:
   - ✅ Status shows "PROCESSING" or "PENDING" (yellow badge)
   - ✅ Balance deducted
   - ✅ Can track status

### 7.3 Refund Transaction

1. Request a refund (if applicable)
2. Verify:
   - ✅ Type shows "Refund" (red badge)
   - ✅ Amount shows with + sign (credit)
   - ✅ Balance increased
   - ✅ Settlement status: "Not Applicable"

---

## Step 8: Test Webhook Retry (Admin Only)

### 8.1 Simulate Failed Webhook

**Admin Dashboard > Webhook Logs:**

1. Find a webhook event with status "failed"
2. Click "Retry" button
3. Verify:
   - ✅ Retry count incremented
   - ✅ Status updated
   - ✅ Response logged

### 8.2 Check Automatic Retry

1. Wait for automatic retry (1min, 5min, 15min, 1hr, 6hrs)
2. Refresh webhook logs
3. Verify:
   - ✅ Retry count incremented automatically
   - ✅ Status updated if successful

---

## Step 9: Test Data Integrity

### 9.1 Transaction Normalization

For each transaction, verify:
- ✅ `transaction_ref` is unique and not null
- ✅ `session_id` is populated
- ✅ `transaction_type` is one of: va_deposit, api_transfer, company_withdrawal, refund
- ✅ `fee` is calculated correctly
- ✅ `net_amount` = amount ± fee (depending on type)
- ✅ `settlement_status` is one of: settled, unsettled, not_applicable, failed
- ✅ `status` is one of: successful, failed, processing, pending

### 9.2 Balance Consistency

1. Check company balance in database:
   ```bash
   php artisan tinker
   >>> $company = App\Models\Company::find(1);
   >>> $company->balance;
   ```

2. Calculate expected balance:
   - Sum of all successful deposits
   - Minus sum of all successful transfers/withdrawals
   - Minus all fees

3. Verify:
   - ✅ Database balance matches calculated balance
   - ✅ Dashboard balance matches database balance

---

## Step 10: Performance Testing

### 10.1 Load Test

1. Create 100+ transactions (via API or manual)
2. Verify:
   - ✅ RA Transactions page loads in <2 seconds
   - ✅ Pagination works correctly
   - ✅ Search/filter works
   - ✅ No timeout errors

### 10.2 Webhook Processing

1. Send multiple webhooks simultaneously
2. Verify:
   - ✅ All webhooks processed
   - ✅ No duplicate transactions
   - ✅ Idempotency works (same webhook ID = same transaction)

---

## Troubleshooting

### Issue: Webhook not received

**Check:**
1. PalmPay webhook URL configured correctly
2. Server firewall allows incoming webhooks
3. Laravel logs: `storage/logs/laravel.log`
4. Webhook signature validation passing

### Issue: Transaction not appearing

**Check:**
1. Webhook processed successfully (check webhook logs)
2. Transaction created in database:
   ```sql
   SELECT * FROM transactions ORDER BY created_at DESC LIMIT 10;
   ```
3. Laravel logs for errors
4. Clear browser cache (Ctrl+Shift+R)

### Issue: Balance not updating

**Check:**
1. Transaction status is "successful"
2. Balance calculation in transaction service
3. Database balance vs displayed balance
4. Clear Laravel caches

### Issue: Settlement not processing

**Check:**
1. Settlement delay configured correctly
2. Cron job running: `php artisan settlements:process`
3. Settlement queue table has entries
4. Laravel logs for settlement errors

---

## Success Criteria

All tests pass if:

✅ **Deposits:**
- Webhooks received and logged
- Transactions created with correct data
- Balances updated accurately
- Settlement status tracked

✅ **Transfers:**
- Transactions created with correct data
- Balances deducted accurately
- Fees calculated correctly
- Status tracked properly

✅ **UI/UX:**
- Wallet page clean (no transaction history)
- RA Transactions shows all data
- All columns visible (no cut-off)
- Responsive design works

✅ **Admin:**
- Full visibility of all transactions
- Webhook logs complete
- Can retry failed webhooks
- Statement shows all data

✅ **Data Integrity:**
- No null values in UI
- All normalized fields populated
- Balances consistent
- Settlement status accurate

---

## Next Steps After Testing

If all tests pass:

1. ✅ System is production-ready
2. ✅ Monitor for 24 hours
3. ✅ Check settlement processing
4. ✅ Review webhook logs daily
5. ✅ Monitor balance consistency

If issues found:
1. Document the issue
2. Check Laravel logs
3. Review transaction data
4. Fix and retest

---

**Happy Testing! 🚀**

The system is now ready for comprehensive end-to-end testing with a clean slate!
