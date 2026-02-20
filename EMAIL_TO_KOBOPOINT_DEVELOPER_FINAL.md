# Email to Kobopoint Developer - Final Solution

```
Subject: PointWave Integration - Complete Solution & API Guide

Dear Abubakar,

Thank you for your patience. We've identified the root cause of your integration issue and have prepared a complete solution for you.

═══════════════════════════════════════════════════════════════
THE ISSUE
═══════════════════════════════════════════════════════════════

You were attempting to call PalmPay API directly from your local development machine, which caused IP whitelist errors. This is NOT the correct integration approach.

❌ WRONG APPROACH (What you were doing):
Your Application → PalmPay API (BLOCKED - IP not whitelisted)

✅ CORRECT APPROACH (Professional standard):
Your Application → PointWave API → PalmPay API (WORKS from anywhere)

═══════════════════════════════════════════════════════════════
THE SOLUTION
═══════════════════════════════════════════════════════════════

Use PointWave Gateway API endpoints instead of calling PalmPay directly.

BASE URL: https://app.pointwave.ng/api/gateway

AUTHENTICATION HEADERS:
- X-API-Key: [your_api_key]
- X-Secret-Key: [your_secret_key]
- X-Business-ID: 3450968aa027e86e3ff5b0169dc17edd7694a846
- Content-Type: application/json

═══════════════════════════════════════════════════════════════
AVAILABLE ENDPOINTS
═══════════════════════════════════════════════════════════════

1. CREATE VIRTUAL ACCOUNT
   POST /api/gateway/virtual-accounts
   
   Request:
   {
     "userId": "unique_customer_id",
     "customerName": "John Doe",
     "customerEmail": "john@example.com",
     "customerPhone": "+2349012345678",
     "bvn": "22222222222"
   }
   
   Response:
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

2. GET VIRTUAL ACCOUNT
   GET /api/gateway/virtual-accounts/{userId}

3. INITIATE BANK TRANSFER
   POST /api/gateway/transfers
   
   Request:
   {
     "amount": 5000,
     "accountNumber": "0123456789",
     "bankCode": "058",
     "accountName": "Jane Smith",
     "narration": "Payment for services",
     "reference": "your_unique_ref_123"
   }

4. GET TRANSFER STATUS
   GET /api/gateway/transfers/{transactionId}

5. GET BANKS LIST
   GET /api/gateway/banks

6. VERIFY BANK ACCOUNT
   POST /api/gateway/banks/verify
   
   Request:
   {
     "accountNumber": "0123456789",
     "bankCode": "058"
   }

7. GET WALLET BALANCE
   GET /api/gateway/balance

8. VERIFY TRANSACTION
   GET /api/gateway/transactions/verify/{reference}

═══════════════════════════════════════════════════════════════
QUICK START - NODE.JS EXAMPLE
═══════════════════════════════════════════════════════════════

const axios = require('axios');

const pointwave = axios.create({
  baseURL: 'https://app.pointwave.ng/api/gateway',
  headers: {
    'X-API-Key': 'your_api_key',
    'X-Secret-Key': 'your_secret_key',
    'X-Business-ID': '3450968aa027e86e3ff5b0169dc17edd7694a846',
    'Content-Type': 'application/json'
  }
});

// Create virtual account
async function createVirtualAccount() {
  try {
    const response = await pointwave.post('/virtual-accounts', {
      userId: 'user_001',
      customerName: 'John Doe',
      customerEmail: 'john@example.com',
      customerPhone: '+2349012345678'
    });
    
    console.log('Success:', response.data);
    return response.data;
  } catch (error) {
    console.error('Error:', error.response?.data || error.message);
  }
}

// Initiate transfer
async function initiateTransfer() {
  try {
    const response = await pointwave.post('/transfers', {
      amount: 5000,
      accountNumber: '0123456789',
      bankCode: '058',
      narration: 'Payment for services'
    });
    
    console.log('Success:', response.data);
    return response.data;
  } catch (error) {
    console.error('Error:', error.response?.data || error.message);
  }
}

// Get balance
async function getBalance() {
  try {
    const response = await pointwave.get('/balance');
    console.log('Balance:', response.data);
    return response.data;
  } catch (error) {
    console.error('Error:', error.response?.data || error.message);
  }
}

═══════════════════════════════════════════════════════════════
WEBHOOKS
═══════════════════════════════════════════════════════════════

Configure your webhook URL in your PointWave dashboard to receive real-time notifications for:

- collection.success (Virtual account deposits)
- payout.success (Transfer completed)
- payout.failed (Transfer failed)

Example webhook payload:
{
  "event": "collection.success",
  "data": {
    "transactionId": "TXN_123456",
    "reference": "REF_789012",
    "userId": "user_001",
    "accountNumber": "6644694207",
    "amount": 10000,
    "fee": 70,
    "netAmount": 9930,
    "status": "success",
    "timestamp": "2026-02-20T10:00:00Z"
  }
}

═══════════════════════════════════════════════════════════════
TESTING
═══════════════════════════════════════════════════════════════

You can test immediately using cURL:

curl -X POST https://app.pointwave.ng/api/gateway/virtual-accounts \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your_api_key" \
  -H "X-Secret-Key: your_secret_key" \
  -H "X-Business-ID: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -d '{
    "userId": "test_user_001",
    "customerName": "Test Customer",
    "customerEmail": "test@example.com",
    "customerPhone": "+2349012345678"
  }'

═══════════════════════════════════════════════════════════════
BENEFITS OF THIS APPROACH
═══════════════════════════════════════════════════════════════

✅ Works from ANY location (no IP whitelist issues)
✅ Works from local development, staging, and production
✅ Consistent API interface
✅ Better error handling
✅ Webhook support built-in
✅ Rate limiting and security managed by PointWave
✅ You don't need to manage PalmPay credentials
✅ Professional and scalable architecture

═══════════════════════════════════════════════════════════════
COMPLETE DOCUMENTATION
═══════════════════════════════════════════════════════════════

Full API documentation: https://app.pointwave.ng/docs

Includes:
- Interactive API explorer
- Code examples in multiple languages
- Postman collection
- Webhook testing tools
- Error code reference

═══════════════════════════════════════════════════════════════
YOUR API CREDENTIALS
═══════════════════════════════════════════════════════════════

Business ID: 3450968aa027e86e3ff5b0169dc17edd7694a846

API Key and Secret Key: Please check your PointWave dashboard at:
https://app.pointwave.ng/dashboard/settings/api-credentials

If you don't have access to the dashboard, please let me know and I'll provide your credentials securely.

═══════════════════════════════════════════════════════════════
NEXT STEPS
═══════════════════════════════════════════════════════════════

1. Update your integration to use PointWave API endpoints
2. Test virtual account creation using the example above
3. Configure your webhook URL in the dashboard
4. Test transfers and balance checks
5. Go live!

═══════════════════════════════════════════════════════════════
SUPPORT
═══════════════════════════════════════════════════════════════

If you encounter any issues or have questions:

- Email: support@pointwave.ng
- Technical Support: tech@pointwave.ng
- Response Time: Within 2 hours during business hours

We're here to help you succeed!

═══════════════════════════════════════════════════════════════
SUMMARY
═══════════════════════════════════════════════════════════════

❌ Don't call PalmPay API directly
✅ Use PointWave Gateway API
✅ Works from anywhere (no IP issues)
✅ Complete documentation available
✅ Ready to test immediately

The integration is straightforward and professional. You should be able to complete testing within 30 minutes.

Please let me know once you've tested the virtual account creation endpoint, and I'll assist with any questions you may have.

Best regards,
PointWave Technical Team

---

P.S. Attached is a complete integration guide (DEVELOPER_INTEGRATION_COMPLETE_GUIDE.md) with all endpoints, examples, and best practices.
```
