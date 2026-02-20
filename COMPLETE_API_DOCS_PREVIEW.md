# PointWave API Documentation - Complete Preview

This document shows you EXACTLY what the complete documentation will look like. Review this first, then I'll create all the actual files.

---

## 📋 DOCUMENTATION STRUCTURE

```
1. Home (index.blade.php) ✅ CREATED
   - Overview
   - Quick Start
   - Integration Flow (Customer → Virtual Account → Transfers)
   - Environments

2. Authentication (authentication.blade.php)
   - Required Headers
   - Code Examples (PHP, Python, Node.js)
   - Security Best Practices

3. Customers (customers.blade.php) ⭐ REQUIRED FIRST
   - Create Customer
   - Update Customer  
   - Get Customer Details

4. Virtual Accounts (virtual-accounts.blade.php)
   - Create Virtual Account (requires customer_id)
   - Update Virtual Account
   - Static vs Dynamic

5. Transfers (transfers.blade.php)
   - Verify Bank Account
   - Initiate Transfer
   - Check Transfer Status
   - Get Supported Banks

6. Webhooks (webhooks.blade.php)
   - Webhook Format
   - Signature Verification (3 languages)
   - Event Types

7. Banks (banks.blade.php)
   - Supported Banks List
   - Bank Codes

8. Errors (errors.blade.php)
   - Error Codes
   - Troubleshooting

9. Sandbox (sandbox.blade.php)
   - Testing Guide
   - Test Credentials
```

---

## 🔐 AUTHENTICATION PAGE PREVIEW

### Endpoint
```
All API requests require authentication headers
```

### Required Headers

| Header | Value | Description |
|--------|-------|-------------|
| `Authorization` | `Bearer {SECRET_KEY}` | Your secret key (120 chars) |
| `x-api-key` | `{API_KEY}` | Your API key (40 chars) |
| `x-business-id` | `{BUSINESS_ID}` | Your business ID (40 chars) |
| `Content-Type` | `application/json` | JSON content type |
| `Idempotency-Key` | `{UNIQUE_ID}` | Unique request ID (for POST/PUT) |

### Example Request (PHP)
```php
<?php

$businessId = 'your_business_id_here';
$apiKey = 'your_api_key_here';
$secretKey = 'your_secret_key_here';

$ch = curl_init('https://app.pointwave.ng/api/gateway/customers');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'x-api-key: ' . $apiKey,
        'x-business-id: ' . $businessId,
        'Content-Type: application/json',
        'Idempotency-Key: ' . uniqid('req_', true)
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone_number' => '08012345678'
    ])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
print_r($data);
?>
```

### Example Request (Python/Django)
```python
import requests
import uuid

business_id = 'your_business_id_here'
api_key = 'your_api_key_here'
secret_key = 'your_secret_key_here'

headers = {
    'Authorization': f'Bearer {secret_key}',
    'x-api-key': api_key,
    'x-business-id': business_id,
    'Content-Type': 'application/json',
    'Idempotency-Key': str(uuid.uuid4())
}

payload = {
    'first_name': 'John',
    'last_name': 'Doe',
    'email': 'john@example.com',
    'phone_number': '08012345678'
}

response = requests.post(
    'https://app.pointwave.ng/api/gateway/customers',
    headers=headers,
    json=payload
)

print(response.status_code)
print(response.json())
```

### Example Request (Node.js)
```javascript
const axios = require('axios');
const { v4: uuidv4 } = require('uuid');

const businessId = 'your_business_id_here';
const apiKey = 'your_api_key_here';
const secretKey = 'your_secret_key_here';

const headers = {
    'Authorization': `Bearer ${secretKey}`,
    'x-api-key': apiKey,
    'x-business-id': businessId,
    'Content-Type': 'application/json',
    'Idempotency-Key': uuidv4()
};

const payload = {
    first_name: 'John',
    last_name: 'Doe',
    email: 'john@example.com',
    phone_number: '08012345678'
};

axios.post('https://app.pointwave.ng/api/gateway/customers', payload, { headers })
    .then(response => {
        console.log(response.status);
        console.log(response.data);
    })
    .catch(error => {
        console.error(error.response.data);
    });
```

---

## 🔖 CUSTOMERS PAGE PREVIEW (STEP 1 - REQUIRED)

### Overview
**⚠️ IMPORTANT:** You MUST create a customer first before creating virtual accounts. This ensures better data quality and control.

### Create Customer

**Endpoint:**
```
POST /v1/customers
```

**Description:**
Creates a new customer in your PointWave account. This is the first step in the integration flow.

**Request Headers:**
| Header | Value | Required |
|--------|-------|----------|
| `Authorization` | `Bearer {SECRET_KEY}` | ✅ Required |
| `x-api-key` | `{API_KEY}` | ✅ Required |
| `x-business-id` | `{BUSINESS_ID}` | ✅ Required |
| `Content-Type` | `application/json` | ✅ Required |
| `Idempotency-Key` | `{UNIQUE_ID}` | ✅ Required |

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `first_name` | string | ✅ Required | Customer's first name |
| `last_name` | string | ✅ Required | Customer's last name |
| `email` | string | ✅ Required | Customer's email address |
| `phone_number` | string | ✅ Required | Customer's phone (e.g., 08012345678) |
| `id_type` | string | ❌ Optional | `bvn` or `nin` for verification |
| `id_number` | string | ❌ Optional | BVN or NIN number |
| `external_reference` | string | ❌ Optional | Your unique reference |

**Example Request (JSON):**
```json
{
  "first_name": "Jamil",
  "last_name": "Abubakar",
  "email": "jamil@example.com",
  "phone_number": "08078889419",
  "id_type": "bvn",
  "id_number": "22490148602",
  "external_reference": "CUST-12345"
}
```

**Example Request (PHP):**
```php
<?php
$ch = curl_init('https://app.pointwave.ng/api/gateway/customers');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'x-api-key: ' . $apiKey,
        'x-business-id: ' . $businessId,
        'Content-Type: application/json',
        'Idempotency-Key: ' . uniqid('req_', true)
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'first_name' => 'Jamil',
        'last_name' => 'Abubakar',
        'email' => 'jamil@example.com',
        'phone_number' => '08078889419',
        'id_type' => 'bvn',
        'id_number' => '22490148602',
        'external_reference' => 'CUST-12345'
    ])
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

// Store customer_id for next step
$customerId = $data['data']['customer_id'];
echo "Customer ID: " . $customerId;
?>
```

**✅ Successful Response (201 Created):**
```json
{
  "status": true,
  "request_id": "f01cd2ef-5de9-4a16-a3b2-ed273851bb4a",
  "message": "Customer created successfully",
  "data": {
    "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
    "first_name": "Jamil",
    "last_name": "Abubakar",
    "email": "jamil@example.com",
    "phone_number": "08078889419",
    "verification_status": "verified",
    "created_at": "2026-02-20T10:30:00Z"
  }
}
```

**❌ Error Response (422 Validation Error):**
```json
{
  "status": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "phone_number": ["The phone number format is invalid."]
  }
}
```

**📌 Notes:**
- Store the `customer_id` - you'll need it for creating virtual accounts
- Email and phone must be unique per business
- BVN/NIN verification is optional but recommended for higher limits
- Use `external_reference` to link with your system

---

## 🏘️ VIRTUAL ACCOUNTS PAGE PREVIEW (STEP 2)

### Overview
**⚠️ PREREQUISITE:** You must create a customer first using `/v1/customers` endpoint.

### Create Virtual Account

**Endpoint:**
```
POST /v1/virtual-accounts
```

**Description:**
Creates a virtual bank account for an existing customer. The customer can receive payments to this account.

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `customer_id` | string | ✅ Required | Customer ID from step 1 |
| `account_type` | string | ✅ Required | `static` or `dynamic` |
| `amount` | number | ⚠️ Required if dynamic | Expected amount for dynamic accounts |
| `bank_codes` | array | ❌ Optional | Array of bank codes (default: ["999129"]) |

**Account Types:**
| Type | Description | Use Case |
|------|-------------|----------|
| `static` | Permanent account, accepts any amount | Wallets, recurring payments |
| `dynamic` | Temporary account for specific amount | One-time payments, invoices |

**Example Request (Static Account):**
```json
{
  "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
  "account_type": "static",
  "bank_codes": ["999129"]
}
```

**Example Request (Dynamic Account):**
```json
{
  "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
  "account_type": "dynamic",
  "amount": 50000,
  "bank_codes": ["999129"]
}
```

**Example Request (PHP):**
```php
<?php
// Use customer_id from previous step
$customerId = '1efdfc4845a7327bc9271ff0daafdae551d07524';

$ch = curl_init('https://app.pointwave.ng/api/gateway/virtual-accounts');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'x-api-key: ' . $apiKey,
        'x-business-id: ' . $businessId,
        'Content-Type: application/json',
        'Idempotency-Key: ' . uniqid('req_', true)
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'customer_id' => $customerId,
        'account_type' => 'static',
        'bank_codes' => ['999129']
    ])
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

// Store account details
$accountNumber = $data['data']['virtual_accounts'][0]['account_number'];
$accountName = $data['data']['virtual_accounts'][0]['account_name'];

echo "Account Number: " . $accountNumber . "\n";
echo "Account Name: " . $accountName;
?>
```

**✅ Successful Response (201 Created):**
```json
{
  "status": true,
  "request_id": "abc123-def456",
  "message": "Virtual account created successfully",
  "data": {
    "customer": {
      "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
      "name": "Jamil Abubakar",
      "email": "jamil@example.com"
    },
    "virtual_accounts": [
      {
        "bank_code": "999129",
        "bank_name": "PointWave MFB",
        "account_number": "6690945661",
        "account_name": "YourBusiness-Jamil Abubakar",
        "account_type": "static",
        "virtual_account_id": "PWV_VA_71B1A38C2F",
        "status": "active"
      }
    ]
  }
}
```

**Partners Bank Codes:**
| Bank Name | Bank Code | Status |
|-----------|-----------|--------|
| PointWave MFB | 999129 | ✅ Active (Default) |
| Alternative Bank | 090743 | ✅ Active |

---

## 💷 TRANSFERS PAGE PREVIEW

### Verify Bank Account (Recommended)

**Endpoint:**
```
POST /v1/transfers/verify
```

**Description:**
Verify bank account details before initiating a transfer. This prevents failed transfers.

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `bank_code` | string | ✅ Required | Bank code (e.g., "058") |
| `account_number` | string | ✅ Required | 10-digit account number |

**Example Request (JSON):**
```json
{
  "bank_code": "058",
  "account_number": "0123456789"
}
```

**Example Request (PHP):**
```php
<?php
$ch = curl_init('https://app.pointwave.ng/api/gateway/transfers/verify');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'x-api-key: ' . $apiKey,
        'x-business-id: ' . $businessId,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'bank_code' => '058',
        'account_number' => '0123456789'
    ])
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

if ($data['status']) {
    echo "Account Name: " . $data['data']['account_name'];
    echo "Bank Name: " . $data['data']['bank_name'];
}
?>
```

**✅ Successful Response:**
```json
{
  "status": true,
  "message": "Account verified successfully",
  "data": {
    "account_name": "JOHN DOE",
    "account_number": "0123456789",
    "bank_name": "Guaranty Trust Bank",
    "bank_code": "058"
  }
}
```

### Initiate Transfer

**Endpoint:**
```
POST /v1/transfers
```

**Description:**
Send money from your PointWave wallet to any Nigerian bank account.

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `amount` | number | ✅ Required | Amount in NGN (minimum: 100) |
| `bank_code` | string | ✅ Required | Destination bank code |
| `account_number` | string | ✅ Required | 10-digit account number |
| `account_name` | string | ✅ Required | Account holder name |
| `narration` | string | ❌ Optional | Transfer description |
| `reference` | string | ❌ Optional | Your unique reference |

**Example Request (JSON):**
```json
{
  "amount": 25000,
  "bank_code": "058",
  "account_number": "0123456789",
  "account_name": "JOHN DOE",
  "narration": "Wallet withdrawal",
  "reference": "TXN-12345"
}
```

**Example Request (PHP):**
```php
<?php
$ch = curl_init('https://app.pointwave.ng/api/gateway/transfers');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'x-api-key: ' . $apiKey,
        'x-business-id: ' . $businessId,
        'Content-Type: application/json',
        'Idempotency-Key: ' . uniqid('txn_', true)
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'amount' => 25000,
        'bank_code' => '058',
        'account_number' => '0123456789',
        'account_name' => 'JOHN DOE',
        'narration' => 'Wallet withdrawal',
        'reference' => 'TXN-' . time()
    ])
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

if ($data['status']) {
    echo "Transfer Reference: " . $data['data']['reference'];
    echo "Status: " . $data['data']['status'];
}
?>
```

**✅ Successful Response (201 Created):**
```json
{
  "status": true,
  "message": "Transfer initiated successfully",
  "data": {
    "transaction_id": "txn_699898366854b34349",
    "reference": "TF_6998983665137",
    "amount": 25000,
    "fee": 15,
    "total_amount": 25015,
    "status": "processing",
    "recipient_account": "0123456789",
    "recipient_name": "JOHN DOE",
    "recipient_bank": "Guaranty Trust Bank",
    "created_at": "2026-02-20T18:21:58Z"
  }
}
```

**📌 Transfer Flow:**
1. Verify account details (recommended)
2. Deduct amount + fee from wallet
3. Initiate transfer
4. Receive webhook notification when complete
5. Transfer typically completes in 1-5 minutes

---

## 🔏 WEBHOOKS PAGE PREVIEW

### Webhook Notification Format

When events occur (payment received, transfer completed, etc.), PointWave sends a POST request to your webhook URL.

**Webhook Payload:**
```json
{
  "event_type": "virtual_account.payment_received",
  "transaction_id": "txn_699892391a43e16704",
  "amount_paid": 100.00,
  "settlement_amount": 99.40,
  "settlement_fee": 0.60,
  "transaction_status": "success",
  "sender": {
    "name": "ABOKI TELECOMMUNICATION SERVICES",
    "account_number": "****0018",
    "bank": "OPAY"
  },
  "receiver": {
    "name": "YourBusiness-Jamil Abubakar",
    "account_number": "6690945661",
    "bank": "PointWave MFB"
  },
  "customer": {
    "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
    "name": "Jamil Abubakar",
    "email": "jamil@example.com"
  },
  "description": "Payment received successfully",
  "timestamp": "2026-02-20T17:56:25Z"
}
```

### Webhook Signature Verification

**⚠️ IMPORTANT:** Always verify webhook signatures to ensure requests are from PointWave.

**PHP Example:**
```php
<?php
// Get raw POST body
$payload = file_get_contents('php://input');

// Get signature from header
$signatureHeader = $_SERVER['HTTP_X_POINTWAVE_SIGNATURE'];

// Your webhook secret (from dashboard)
$webhookSecret = 'your_webhook_secret_here';

// Calculate expected signature
$calculatedSignature = hash_hmac('sha256', $payload, $webhookSecret);

// Verify signature
if (hash_equals($calculatedSignature, $signatureHeader)) {
    // Signature is valid - process webhook
    $data = json_decode($payload, true);
    
    // Handle event
    if ($data['event_type'] === 'virtual_account.payment_received') {
        // Credit customer account
        $customerId = $data['customer']['customer_id'];
        $amount = $data['settlement_amount'];
        
        // Your business logic here
        creditCustomerWallet($customerId, $amount);
    }
    
    // Return 200 OK
    http_response_code(200);
    echo json_encode(['status' => 'success']);
} else {
    // Invalid signature - reject
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
}
?>
```

**Python/Django Example:**
```python
import hmac
import hashlib
import json
from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt

@csrf_exempt
def webhook_handler(request):
    # Get raw POST body
    payload = request.body
    
    # Get signature from header
    signature_header = request.META.get('HTTP_X_POINTWAVE_SIGNATURE')
    
    # Your webhook secret
    webhook_secret = 'your_webhook_secret_here'
    
    # Calculate expected signature
    calculated_signature = hmac.new(
        webhook_secret.encode(),
        payload,
        hashlib.sha256
    ).hexdigest()
    
    # Verify signature
    if hmac.compare_digest(calculated_signature, signature_header):
        # Signature is valid
        data = json.loads(payload)
        
        # Handle event
        if data['event_type'] == 'virtual_account.payment_received':
            customer_id = data['customer']['customer_id']
            amount = data['settlement_amount']
            
            # Your business logic
            credit_customer_wallet(customer_id, amount)
        
        return JsonResponse({'status': 'success'})
    else:
        # Invalid signature
        return JsonResponse(
            {'status': 'error', 'message': 'Invalid signature'},
            status=400
        )
```

**Node.js Example:**
```javascript
const crypto = require('crypto');
const express = require('express');
const app = express();

app.post('/webhook', express.raw({type: 'application/json'}), (req, res) => {
    // Get raw body
    const payload = req.body;
    
    // Get signature from header
    const signatureHeader = req.headers['x-pointwave-signature'];
    
    // Your webhook secret
    const webhookSecret = 'your_webhook_secret_here';
    
    // Calculate expected signature
    const calculatedSignature = crypto
        .createHmac('sha256', webhookSecret)
        .update(payload)
        .digest('hex');
    
    // Verify signature
    if (crypto.timingSafeEqual(
        Buffer.from(calculatedSignature),
        Buffer.from(signatureHeader)
    )) {
        // Signature is valid
        const data = JSON.parse(payload);
        
        // Handle event
        if (data.event_type === 'virtual_account.payment_received') {
            const customerId = data.customer.customer_id;
            const amount = data.settlement_amount;
            
            // Your business logic
            creditCustomerWallet(customerId, amount);
        }
        
        res.json({status: 'success'});
    } else {
        // Invalid signature
        res.status(400).json({
            status: 'error',
            message: 'Invalid signature'
        });
    }
});
```

### Webhook Event Types

| Event Type | Description | When Triggered |
|------------|-------------|----------------|
| `virtual_account.payment_received` | Payment received to virtual account | Customer sends money to virtual account |
| `transfer.successful` | Transfer completed successfully | Bank transfer completed |
| `transfer.failed` | Transfer failed | Bank transfer failed |
| `customer.verified` | Customer identity verified | BVN/NIN verification completed |

---

## 🏦 SUPPORTED BANKS

| Bank Name | Bank Code | Status |
|-----------|-----------|--------|
| Access Bank | 044 | ✅ Active |
| GTBank | 058 | ✅ Active |
| Zenith Bank | 057 | ✅ Active |
| First Bank | 011 | ✅ Active |
| UBA | 033 | ✅ Active |
| Opay | 999992 | ✅ Active |
| Kuda Bank | 090267 | ✅ Active |
| Moniepoint | 50515 | ✅ Active |

**Get Full List:**
```
GET /v1/banks
```

---

## 😠 ERROR CODES

| Code | Message | Solution |
|------|---------|----------|
| 401 | Unauthorized | Check your API credentials |
| 422 | Validation Error | Check request body fields |
| 400 | Bad Request | Check request format |
| 404 | Not Found | Check endpoint URL |
| 500 | Server Error | Contact support |
| 429 | Too Many Requests | Slow down requests |

---

## 🧪 SANDBOX TESTING

**Test Credentials:**
- Business ID: `test_business_id_here`
- API Key: `test_api_key_here`
- Secret Key: `test_secret_key_here`

**Features:**
- ₦2,000,000 starting balance
- Balance resets every 24 hours
- All transactions are simulated
- Same endpoints as production

**Test BVN:** `22222222222`
**Test NIN:** `11111111111`

---

## ✅ WHAT'S DIFFERENT FROM OLD DOCS?

### Old Docs Issues:
❌ Allowed skipping customer creation
❌ Mentioned PalmPay provider
❌ Incomplete code examples
❌ Missing signature verification examples
❌ Confusing flow

### New Docs Benefits:
✅ Enforces customer-first flow
✅ No provider mentions (PointWave only)
✅ Complete PHP, Python, Node.js examples
✅ Professional format like Xixapay
✅ Clear step-by-step integration
✅ Signature verification in all languages
✅ Complete error handling
✅ Best practices included

---

## 📝 NEXT STEPS

1. **Review this preview** - Make sure everything looks good
2. **I'll create all actual files** - Complete HTML documentation
3. **Deploy to server** - Replace old docs
4. **Test with developer** - Ensure no confusion

**Ready to proceed with creating all files?**
