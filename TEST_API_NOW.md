# 🚀 Test Your API Now

## Quick Instructions

### Step 1: Get Your Credentials (2 minutes)

1. Open https://app.pointwave.ng
2. Login to your dashboard
3. Click **Settings** → **API Keys**
4. Copy these 3 values:
   - **Secret Key** (starts with `sk_live_...`)
   - **API Key** (starts with `pk_live_...`)
   - **Business ID** (40-character string)

### Step 2: Add Credentials to Test Script (1 minute)

Open `test_v1_api_complete.php` and replace these lines:

```php
$SECRET_KEY = 'YOUR_SECRET_KEY_HERE';  // Paste your Secret Key
$API_KEY = 'YOUR_API_KEY_HERE';        // Paste your API Key
$BUSINESS_ID = 'YOUR_BUSINESS_ID_HERE'; // Paste your Business ID
```

### Step 3: Run the Test (30 seconds)

```bash
php test_v1_api_complete.php
```

### What the Test Does

The script will automatically:

1. ✅ **Create Customer** - Creates a test customer with random email
2. ✅ **Get Customer** - Retrieves the customer details
3. ✅ **Update Customer** - Updates phone and address
4. ✅ **Create Virtual Account** - Creates a static virtual account
5. ✅ **Update VA Status** - Deactivates the virtual account
6. ✅ **Get Transactions** - Retrieves transaction history
7. ⏭️ **Skip Transfer** - Skipped to avoid balance deduction

### Expected Output

```
============================================================
  PointWave V1 API Complete Test
============================================================
Base URL: https://app.pointwave.ng/api/v1
Test Mode: YES (Sandbox)
Time: 2026-02-20 15:30:00

============================================================
  TEST 1: Create Customer
============================================================

HTTP Code: 201
Response: {
    "status": "success",
    "message": "Customer created successfully",
    "data": {
        "customer_id": "cust_abc123xyz456",
        ...
    }
}
✅ Customer created: cust_abc123xyz456

============================================================
  TEST 2: Get Customer Details
============================================================
...
✅ Customer details retrieved

============================================================
  TEST 3: Update Customer
============================================================
...
✅ Customer updated

============================================================
  TEST 4: Create Virtual Account
============================================================
...
✅ Virtual Account created: 9876543210 (ID: va_xyz789)

============================================================
  TEST 5: Update Virtual Account Status
============================================================
...
✅ Virtual Account status updated to deactivated

============================================================
  TEST 6: Get Transactions
============================================================
...
✅ Retrieved 5 transactions

============================================================
  CLEANUP
============================================================
ℹ️  Created resources:
  - Customer ID: cust_abc123xyz456
  - Virtual Account ID: va_xyz789 (Status: deactivated)

============================================================
  TEST SUMMARY
============================================================
✅ All tests completed!

Tested Endpoints:
  ✅ POST /api/v1/customers (Create Customer)
  ✅ GET /api/v1/customers/{id} (Get Customer)
  ✅ PUT /api/v1/customers/{id} (Update Customer)
  ✅ POST /api/v1/virtual-accounts (Create Virtual Account)
  ✅ PUT /api/v1/virtual-accounts/{id} (Update Virtual Account)
  ✅ GET /api/v1/transactions (Get Transactions)
  ⏭️  POST /api/v1/transfers (Skipped - requires balance)

============================================================
Documentation ready to send to developers!
File: SEND_THIS_TO_DEVELOPERS.md
============================================================
```

## If You See Errors

### Error: "Invalid API credentials"
- Double-check you copied all 3 credentials correctly
- Make sure there are no extra spaces
- Verify credentials are from Settings → API Keys

### Error: "Customer not found"
- This is normal if testing GET before CREATE
- The script creates customer first, so this shouldn't happen

### Error: "Insufficient balance"
- Only happens if you uncomment the transfer test
- Fund your wallet from dashboard

## After Successful Test

### ✅ All Tests Pass?

Great! Your API is working perfectly. Now you can:

1. **Send Documentation to Developers**
   - File: `SEND_THIS_TO_DEVELOPERS.md`
   - This file has everything they need
   - No further explanation required

2. **Review What Was Created**
   - 1 test customer (with random email)
   - 1 virtual account (deactivated)
   - These remain in your database
   - You can delete them from dashboard if needed

3. **Enable Live Mode**
   - Change `$IS_TEST = false;` in test script
   - Run again to test with real PalmPay integration
   - This will create real virtual accounts

## Test with Real Integration

To test with actual PalmPay (not sandbox):

1. Open `test_v1_api_complete.php`
2. Change line: `$IS_TEST = false;`
3. Run: `php test_v1_api_complete.php`

This will:
- Create real customer in your database
- Create real virtual account with PalmPay
- Account will be active and can receive deposits

## Cleanup Test Data

The script automatically deactivates the virtual account, but the customer remains.

To delete test customer:
1. Login to dashboard
2. Go to Customers
3. Find customer by email (test_TIMESTAMP@example.com)
4. Click Delete

## What Developers Will Get

When you send `SEND_THIS_TO_DEVELOPERS.md`, they get:

- ✅ Complete API documentation
- ✅ Authentication guide
- ✅ 13 documented endpoints
- ✅ Code examples in PHP, Python, Node.js
- ✅ Error handling guide
- ✅ Webhook setup instructions
- ✅ Nigerian bank codes
- ✅ Best practices
- ✅ Integration checklist

## Support

If you encounter any issues:

1. Check the HTTP response code and message
2. Verify all 3 credentials are correct
3. Ensure you have internet connection
4. Check if API is accessible: `curl https://app.pointwave.ng/api/v1/transactions`

## Summary

```bash
# 1. Get credentials from dashboard
# 2. Edit test_v1_api_complete.php
# 3. Run test
php test_v1_api_complete.php

# 4. If all tests pass, send documentation
# File: SEND_THIS_TO_DEVELOPERS.md
```

That's it! 🎉
