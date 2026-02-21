# KoboPoint Integration - Troubleshooting Guide

---

## Common Issues When Testing Transfers

### Issue: "Insufficient balance. Required: 115 (Amount: 100 + Fee: 15.00)"

**What this means:**
Your KoboPoint wallet doesn't have enough balance to cover the transfer amount plus PointWave's fee.

**Breakdown:**
- Transfer Amount: ₦100
- PointWave Fee: ₦15
- **Total Required: ₦115**

**Solution:**
Fund your KoboPoint wallet with at least ₦115 before attempting the transfer.

---

## How to Fund Your KoboPoint Wallet

### Option 1: Via Virtual Account
1. Log into your KoboPoint dashboard
2. Go to "Fund Wallet" or "Deposit"
3. You'll see a virtual account number
4. Transfer money from your bank to that account
5. Your wallet will be credited automatically

### Option 2: Via Bank Transfer
1. Contact KoboPoint support for your funding account details
2. Transfer funds from your bank account
3. Wait for confirmation (usually instant)

### Option 3: Via Card Payment
1. Use the "Fund Wallet" option in your dashboard
2. Pay with your debit/credit card
3. Funds reflect immediately

---

## Testing Checklist

Before testing transfers, ensure:

✅ **Wallet Balance**: Your KoboPoint wallet has sufficient balance
- For ₦100 transfer, you need at least ₦115
- For ₦1,000 transfer, you need at least ₦1,150
- Formula: `Transfer Amount + (Transfer Amount × 15%)`

✅ **API Credentials**: All three credentials are correct
- `x-business-id`
- `x-api-key`
- `Authorization: Bearer {secret_key}`

✅ **Account Verification**: Test account verification first
```bash
curl -X POST https://app.pointwave.ng/api/v1/banks/verify \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d '{
    "account_number": "7040540018",
    "bank_code": "000004"
  }'
```

✅ **Valid Bank Account**: The recipient account exists and is active

---

## Understanding PointWave Fees

### Transfer Fees
- **Flat Fee**: ₦15 per transfer (regardless of amount)
- **Charged to**: The sender (you)
- **Deducted from**: Your KoboPoint wallet balance

### Example Calculations

| Transfer Amount | PointWave Fee | Total Deducted | Recipient Gets |
|----------------|---------------|----------------|----------------|
| ₦100 | ₦15 | ₦115 | ₦100 |
| ₦500 | ₦15 | ₦515 | ₦500 |
| ₦1,000 | ₦15 | ₦1,015 | ₦1,000 |
| ₦5,000 | ₦15 | ₦5,015 | ₦5,000 |
| ₦10,000 | ₦15 | ₦10,015 | ₦10,000 |

**Note:** The recipient always receives the full transfer amount. The fee is only charged to you.

---

## Error Messages & Solutions

### 1. "Insufficient balance"
**Cause:** Your wallet balance is less than (amount + fee)

**Solution:** Fund your wallet with more money

---

### 2. "Account not found"
**Cause:** The recipient account number doesn't exist

**Solution:** 
- Verify the account number is correct
- Use the account verification endpoint first
- Check if the bank code is correct

---

### 3. "Invalid API credentials"
**Cause:** One or more of your API credentials is incorrect

**Solution:**
- Double-check your `x-business-id`
- Verify your `x-api-key`
- Confirm your `Bearer` token (secret key)
- Ensure all three match the same environment (production/sandbox)

---

### 4. "Bank not supported"
**Cause:** The bank code doesn't exist or isn't supported

**Solution:**
- Get the banks list: `GET /api/v1/banks`
- Use the correct `code` field from the response
- Common codes: UBA (000004), GTBank (000013), Access (000014)

---

### 5. "Service temporarily unavailable"
**Cause:** PointWave or the banking provider is experiencing issues

**Solution:**
- Wait a few minutes and retry
- Check PointWave status page
- Contact PointWave support if issue persists

---

## Testing Best Practices

### 1. Start Small
Test with small amounts first (₦100-₦500) before processing larger transfers.

### 2. Verify First
Always verify the account before initiating a transfer:
```javascript
// Step 1: Verify account
const verification = await verifyAccount(accountNumber, bankCode);

if (verification.success) {
  // Step 2: Show account name to user for confirmation
  console.log(`Transfer to: ${verification.data.account_name}`);
  
  // Step 3: Initiate transfer
  const transfer = await initiateTransfer({
    account_number: accountNumber,
    bank_code: bankCode,
    account_name: verification.data.account_name,
    amount: 100,
    narration: "Test transfer"
  });
}
```

### 3. Handle Errors Gracefully
```javascript
try {
  const result = await initiateTransfer(transferData);
  console.log('Transfer successful:', result);
} catch (error) {
  if (error.response) {
    // API returned an error
    const errorCode = error.response.data.error_code;
    const errorMessage = error.response.data.error;
    
    switch (errorCode) {
      case 'INSUFFICIENT_BALANCE':
        showError('Please fund your wallet to continue');
        break;
      case 'ACCOUNT_NOT_FOUND':
        showError('Invalid account number');
        break;
      case 'INVALID_CREDENTIALS':
        showError('Authentication failed. Please check your API keys');
        break;
      default:
        showError(errorMessage);
    }
  }
}
```

### 4. Monitor Your Balance
Check your wallet balance before each transfer:
```bash
curl -X GET https://app.pointwave.ng/api/v1/balance \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID" \
  -H "x-api-key: YOUR_API_KEY"
```

---

## Quick Test Script

Use this to test your integration:

```bash
#!/bin/bash

# Your API credentials
SECRET_KEY="your_secret_key"
API_KEY="your_api_key"
BUSINESS_ID="your_business_id"

BASE_URL="https://app.pointwave.ng/api/v1"

# 1. Check balance
echo "1. Checking wallet balance..."
curl -X GET "$BASE_URL/balance" \
  -H "Authorization: Bearer $SECRET_KEY" \
  -H "x-business-id: $BUSINESS_ID" \
  -H "x-api-key: $API_KEY"

echo -e "\n\n"

# 2. Get banks list
echo "2. Getting banks list..."
curl -X GET "$BASE_URL/banks" \
  -H "Authorization: Bearer $SECRET_KEY" \
  -H "x-business-id: $BUSINESS_ID" \
  -H "x-api-key: $API_KEY"

echo -e "\n\n"

# 3. Verify account
echo "3. Verifying account..."
curl -X POST "$BASE_URL/banks/verify" \
  -H "Authorization: Bearer $SECRET_KEY" \
  -H "x-business-id: $BUSINESS_ID" \
  -H "x-api-key: $API_KEY" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d '{
    "account_number": "7040540018",
    "bank_code": "000004"
  }'

echo -e "\n\n"

# 4. Initiate transfer (only if balance is sufficient)
echo "4. Initiating transfer..."
curl -X POST "$BASE_URL/banks/transfer" \
  -H "Authorization: Bearer $SECRET_KEY" \
  -H "x-business-id: $BUSINESS_ID" \
  -H "x-api-key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "bank_code": "000004",
    "account_number": "7040540018",
    "account_name": "ABOKI TELECOMMUNICATION SERVICES",
    "amount": 100,
    "narration": "Test transfer"
  }'
```

---

## Need Help?

If you're still experiencing issues:

1. **Check your wallet balance** - Ensure you have sufficient funds
2. **Verify your API credentials** - All three must be correct
3. **Test account verification first** - Confirm the account exists
4. **Contact PointWave Support** - Provide:
   - Your business ID
   - The error message you're seeing
   - The endpoint you're testing
   - Your request (with credentials masked)

---

**PointWave Team**  
February 21, 2026
