# PointWave Developer Integration - Complete Guide

## 🎯 IMPORTANT: Use PointWave API, NOT PalmPay Directly!

**CRITICAL**: Developers should NEVER call PalmPay API directly. All requests must go through PointWave Gateway API.

```
✅ CORRECT: Your App → PointWave API → PalmPay
❌ WRONG:   Your App → PalmPay API (will fail with IP whitelist errors)
```

---

## 🔐 Authentication

All API requests require these headers:

```http
X-API-Key: your_api_key_here
X-Secret-Key: your_secret_key_here
X-Business-ID: your_business_id_here
Content-Type: application/json
```

**Base URL**: `https://app.pointwave.ng/api/gateway`

---

## 📋 Available Endpoints

### 1. Virtual Accounts

#### Create Virtual Account
```http
POST /api/gateway/virtual-accounts
```

**Request Body:**
```json
{
  "userId": "unique_customer_id",
  "customerName": "John Doe",
  "customerEmail": "john@example.com",
  "customerPhone": "+2349012345678",
  "bvn": "22222222222"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Virtual account created successfully",
  "data": {
    "accountId": "VA_123456",
    "accountNumber": "6644694207",
    "accountName": "YourBusiness-John Doe",
    "bankName": "PalmPay",
    "userId": "unique_customer_id",
    "status": "active",
    "createdAt": "2026-02-20T10:30:00Z"
  }
}
```

#### Get Virtual Account
```http
GET /api/gateway/virtual-accounts/{userId}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "accountId": "VA_123456",
    "accountNumber": "6644694207",
    "accountName": "YourBusiness-John Doe",
    "bankName": "PalmPay",
    "userId": "unique_customer_id",
    "customerName": "John Doe",
    "customerEmail": "john@example.com",
    "customerPhone": "+2349012345678",
    "status": "active",
    "createdAt": "2026-02-20T10:30:00Z"
  }
}
```

#### Update Virtual Account Status
```http
PUT /api/gateway/virtual-accounts/{userId}
```

**Request Body:**
```json
{
  "status": "Enabled"
}
```

#### Delete Virtual Account
```http
DELETE /api/gateway/virtual-accounts/{userId}
```

---

### 2. Bank Transfers

#### Initiate Transfer
```http
POST /api/gateway/transfers
```

**Request Body:**
```json
{
  "amount": 5000,
  "accountNumber": "0123456789",
  "bankCode": "058",
  "accountName": "Jane Smith",
  "narration": "Payment for services",
  "reference": "your_unique_ref_123"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Transfer initiated successfully",
  "data": {
    "transactionId": "TXN_789012",
    "reference": "REF_456789",
    "amount": 5000,
    "fee": 50,
    "totalAmount": 5050,
    "status": "pending",
    "recipientAccount": "0123456789",
    "recipientName": "Jane Smith",
    "createdAt": "2026-02-20T10:35:00Z"
  }
}
```

#### Get Transfer Status
```http
GET /api/gateway/transfers/{transactionId}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "transactionId": "TXN_789012",
    "reference": "REF_456789",
    "externalReference": "your_unique_ref_123",
    "amount": 5000,
    "fee": 50,
    "totalAmount": 5050,
    "status": "success",
    "type": "PAYOUT",
    "category": "transfer",
    "recipientAccount": "0123456789",
    "recipientName": "Jane Smith",
    "recipientBank": "GTBank",
    "description": "Payment for services",
    "errorMessage": null,
    "createdAt": "2026-02-20T10:35:00Z",
    "processedAt": "2026-02-20T10:35:15Z"
  }
}
```

---

### 3. Banks

#### Get Banks List
```http
GET /api/gateway/banks
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "bankCode": "058",
      "bankName": "Guaranty Trust Bank",
      "supportsTransfers": true,
      "supportsVerification": true
    },
    {
      "bankCode": "044",
      "bankName": "Access Bank",
      "supportsTransfers": true,
      "supportsVerification": true
    }
  ]
}
```

#### Verify Bank Account
```http
POST /api/gateway/banks/verify
```

**Request Body:**
```json
{
  "accountNumber": "0123456789",
  "bankCode": "058"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "accountNumber": "0123456789",
    "accountName": "JANE SMITH",
    "bankCode": "058"
  }
}
```

#### Verify PalmPay Account
```http
POST /api/gateway/palmpay/verify
```

**Request Body:**
```json
{
  "accountNumber": "6644694207"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "accountNumber": "6644694207",
    "accountName": "JOHN DOE",
    "available": true
  }
}
```

---

### 4. Transactions

#### Verify Transaction
```http
GET /api/gateway/transactions/verify/{reference}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "transactionId": "TXN_123456",
    "reference": "REF_789012",
    "externalReference": "your_ref_123",
    "status": "success",
    "amount": 10000,
    "fee": 70,
    "totalAmount": 10070,
    "type": "COLLECTION",
    "category": "virtual_account_deposit",
    "createdAt": "2026-02-20T10:00:00Z",
    "processedAt": "2026-02-20T10:00:05Z"
  }
}
```

---

### 5. Wallet Balance

#### Get Balance
```http
GET /api/gateway/balance
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "balance": 150000.00,
    "ledgerBalance": 150000.00,
    "pendingBalance": 5000.00,
    "availableBalance": 145000.00,
    "currency": "NGN"
  }
}
```

---

## 🔔 Webhooks

Configure your webhook URL in your dashboard to receive real-time notifications.

### Webhook Events

#### Virtual Account Deposit (Collection)
```json
{
  "event": "collection.success",
  "data": {
    "transactionId": "TXN_123456",
    "reference": "REF_789012",
    "userId": "unique_customer_id",
    "accountNumber": "6644694207",
    "amount": 10000,
    "fee": 70,
    "netAmount": 9930,
    "status": "success",
    "settlementStatus": "unsettled",
    "timestamp": "2026-02-20T10:00:00Z"
  }
}
```

#### Transfer Success
```json
{
  "event": "payout.success",
  "data": {
    "transactionId": "TXN_789012",
    "reference": "REF_456789",
    "externalReference": "your_ref_123",
    "amount": 5000,
    "fee": 50,
    "status": "success",
    "recipientAccount": "0123456789",
    "recipientName": "Jane Smith",
    "timestamp": "2026-02-20T10:35:15Z"
  }
}
```

#### Transfer Failed
```json
{
  "event": "payout.failed",
  "data": {
    "transactionId": "TXN_789012",
    "reference": "REF_456789",
    "externalReference": "your_ref_123",
    "amount": 5000,
    "status": "failed",
    "errorMessage": "Insufficient funds",
    "timestamp": "2026-02-20T10:35:15Z"
  }
}
```

### Webhook Security

Verify webhook signatures using your secret key:

```javascript
const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, secretKey) {
  const hash = crypto
    .createHmac('sha256', secretKey)
    .update(JSON.stringify(payload))
    .digest('hex');
  
  return hash === signature;
}

// In your webhook handler
app.post('/webhooks/pointwave', (req, res) => {
  const signature = req.headers['x-pointwave-signature'];
  const isValid = verifyWebhookSignature(req.body, signature, YOUR_SECRET_KEY);
  
  if (!isValid) {
    return res.status(401).json({ error: 'Invalid signature' });
  }
  
  // Process webhook
  console.log('Webhook received:', req.body);
  res.status(200).json({ received: true });
});
```

---

## ⚠️ Error Handling

### Common Error Responses

#### 401 Unauthorized
```json
{
  "success": false,
  "message": "Invalid API credentials"
}
```

#### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "amount": ["The amount must be at least 100"],
    "accountNumber": ["The account number must be 10 digits"]
  }
}
```

#### 404 Not Found
```json
{
  "success": false,
  "message": "Virtual account not found"
}
```

#### 500 Server Error
```json
{
  "success": false,
  "message": "Failed to create virtual account",
  "error": "PalmPay Error: Insufficient permissions"
}
```

---

## 🧪 Testing

### Test Credentials

Contact support to get your test credentials:
- Test API Key
- Test Secret Key
- Test Business ID

### Test Environment

Use the same base URL for testing: `https://app.pointwave.ng/api/gateway`

Your account will be configured in sandbox mode for testing.

---

## 📊 Rate Limits

- 60 requests per minute per API key
- Burst limit: 100 requests per minute
- Rate limit headers included in responses:
  - `X-RateLimit-Limit`: Maximum requests per minute
  - `X-RateLimit-Remaining`: Remaining requests
  - `X-RateLimit-Reset`: Time when limit resets

---

## 💰 Fees

### Virtual Account Deposits
- Fee: 0.70% (configurable by admin)
- Cap: ₦1,000 per transaction
- Charged to company wallet

### Bank Transfers
- Fee: ₦50 per transfer
- Deducted from company wallet

---

## 🕐 Settlement Schedule

### Virtual Account Deposits (Collections)
- Settlement: T+1 (Next business day)
- Time: 3:00 AM Nigerian time (WAT)
- Excludes: Weekends and Nigerian public holidays
- Status: `unsettled` → `settled`

### Bank Transfers (Payouts)
- Processing: Real-time
- Status updates via webhook

---

## 📚 Complete API Documentation

Visit: https://app.pointwave.ng/docs

Includes:
- Interactive API explorer
- Code examples in multiple languages
- Postman collection
- Webhook testing tools

---

## 🆘 Support

- Email: support@pointwave.ng
- Technical Support: tech@pointwave.ng
- Documentation: https://app.pointwave.ng/docs

---

## ✅ Integration Checklist

- [ ] Obtain API credentials (API Key, Secret Key, Business ID)
- [ ] Test virtual account creation
- [ ] Test bank transfer
- [ ] Configure webhook URL
- [ ] Test webhook reception
- [ ] Implement webhook signature verification
- [ ] Test error handling
- [ ] Review rate limits
- [ ] Understand settlement schedule
- [ ] Go live!

---

## 🚀 Quick Start Example (Node.js)

```javascript
const axios = require('axios');

const pointwave = axios.create({
  baseURL: 'https://app.pointwave.ng/api/gateway',
  headers: {
    'X-API-Key': 'your_api_key',
    'X-Secret-Key': 'your_secret_key',
    'X-Business-ID': 'your_business_id',
    'Content-Type': 'application/json'
  }
});

// Create virtual account
async function createVirtualAccount(userId, customerName, email, phone) {
  try {
    const response = await pointwave.post('/virtual-accounts', {
      userId,
      customerName,
      customerEmail: email,
      customerPhone: phone
    });
    
    console.log('Virtual Account Created:', response.data);
    return response.data;
  } catch (error) {
    console.error('Error:', error.response?.data || error.message);
    throw error;
  }
}

// Initiate transfer
async function initiateTransfer(amount, accountNumber, bankCode, narration) {
  try {
    const response = await pointwave.post('/transfers', {
      amount,
      accountNumber,
      bankCode,
      narration
    });
    
    console.log('Transfer Initiated:', response.data);
    return response.data;
  } catch (error) {
    console.error('Error:', error.response?.data || error.message);
    throw error;
  }
}

// Get wallet balance
async function getBalance() {
  try {
    const response = await pointwave.get('/balance');
    console.log('Balance:', response.data);
    return response.data;
  } catch (error) {
    console.error('Error:', error.response?.data || error.message);
    throw error;
  }
}

// Usage
(async () => {
  // Create virtual account
  await createVirtualAccount(
    'user_001',
    'John Doe',
    'john@example.com',
    '+2349012345678'
  );
  
  // Check balance
  await getBalance();
  
  // Initiate transfer
  await initiateTransfer(
    5000,
    '0123456789',
    '058',
    'Payment for services'
  );
})();
```

---

## 🎉 You're Ready!

Your integration is complete when:
1. ✅ Virtual accounts are created successfully
2. ✅ Deposits are received and webhooks fire
3. ✅ Transfers are processed successfully
4. ✅ Balance checks work correctly
5. ✅ Error handling is implemented

**Remember**: Always call PointWave API, never PalmPay directly!
