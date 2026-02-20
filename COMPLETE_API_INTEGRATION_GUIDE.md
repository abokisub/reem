# Complete API Integration Guide
## PointWave Payment API - Developer Documentation

**Base URL:** `https://app.pointwave.ng/api`

**Support:** support@pointwave.ng

---

## Table of Contents
1. [Getting Started](#getting-started)
2. [Authentication](#authentication)
3. [Create Customer](#create-customer)
4. [Create Virtual Account](#create-virtual-account)
5. [Bank Transfer](#bank-transfer)
6. [Webhooks](#webhooks)
7. [Error Handling](#error-handling)
8. [Complete Code Examples](#complete-code-examples)

---

## Getting Started

### Step 1: Get Your API Credentials

1. Login to your dashboard at https://app.pointwave.ng
2. Go to **Settings** → **API Keys**
3. Copy your:
   - **Secret Key** (starts with `sk_live_...`)
   - **Business ID** (40-character hex string)

### Step 2: Whitelist Your Server IP

Contact support@pointwave.ng to whitelist your server IP address. This is required for API access.

---

## Authentication

All API requests must include your Secret Key in the `Authorization` header.

### Header Format:
```
Authorization: Token YOUR_SECRET_KEY
```

### Example:
```bash
curl -X POST https://app.pointwave.ng/api/customers/create \
  -H "Authorization: Token sk_live_abc123xyz456..." \
  -H "Content-Type: application/json"
```

---

## Create Customer

**IMPORTANT:** You MUST create a customer BEFORE creating a virtual account.

### Endpoint:
```
POST /api/customers/create
```

### Headers:
```
Authorization: Token YOUR_SECRET_KEY
Content-Type: application/json
```

### Request Body:
```json
{
  "email": "customer@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "08012345678",
  "bvn": "22222222222"
}
```

### Field Requirements:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `email` | string | Yes | Valid email address |
| `first_name` | string | Yes | Customer's first name |
| `last_name` | string | Yes | Customer's last name |
| `phone` | string | Yes | Nigerian phone number (11 digits) |
| `bvn` | string | Yes | Bank Verification Number (11 digits) |

### Success Response (200):
```json
{
  "status": "success",
  "message": "Customer created successfully",
  "data": {
    "customer_id": "cust_abc123xyz456",
    "email": "customer@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "08012345678",
    "created_at": "2026-02-20T10:30:00Z"
  }
}
```

### Error Response (400):
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "bvn": ["The bvn must be 11 digits."]
  }
}
```

### PHP Example:
```php
<?php

$secretKey = 'sk_live_your_secret_key_here';
$baseUrl = 'https://app.pointwave.ng/api';

$data = [
    'email' => 'customer@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'phone' => '08012345678',
    'bvn' => '22222222222'
];

$ch = curl_init($baseUrl . '/customers/create');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Token ' . $secretKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && $result['status'] === 'success') {
    $customerId = $result['data']['customer_id'];
    echo "Customer created: " . $customerId;
} else {
    echo "Error: " . $result['message'];
}
```

---

## Create Virtual Account

After creating a customer, create a virtual account for them to receive payments.

### Endpoint:
```
POST /api/virtual-accounts/create
```

### Headers:
```
Authorization: Token YOUR_SECRET_KEY
Content-Type: application/json
```

### Request Body:
```json
{
  "customer_id": "cust_abc123xyz456",
  "account_name": "John Doe"
}
```

### Field Requirements:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `customer_id` | string | Yes | Customer ID from previous step |
| `account_name` | string | Yes | Name to display on the account |

### Success Response (200):
```json
{
  "status": "success",
  "message": "Virtual account created successfully",
  "data": {
    "account_number": "9876543210",
    "account_name": "John Doe",
    "bank_name": "PalmPay",
    "bank_code": "999991",
    "customer_id": "cust_abc123xyz456",
    "created_at": "2026-02-20T10:35:00Z"
  }
}
```

### Error Response (400):
```json
{
  "status": "error",
  "message": "Customer not found"
}
```

### PHP Example:
```php
<?php

$secretKey = 'sk_live_your_secret_key_here';
$baseUrl = 'https://app.pointwave.ng/api';
$customerId = 'cust_abc123xyz456'; // From previous step

$data = [
    'customer_id' => $customerId,
    'account_name' => 'John Doe'
];

$ch = curl_init($baseUrl . '/virtual-accounts/create');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Token ' . $secretKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && $result['status'] === 'success') {
    $accountNumber = $result['data']['account_number'];
    $bankName = $result['data']['bank_name'];
    echo "Virtual Account: " . $accountNumber . " (" . $bankName . ")";
} else {
    echo "Error: " . $result['message'];
}
```

---

## Bank Transfer

Send money to any Nigerian bank account.

### Endpoint:
```
POST /api/transfers/initiate
```

### Headers:
```
Authorization: Token YOUR_SECRET_KEY
Content-Type: application/json
```

### Request Body:
```json
{
  "request-id": "TXN_20260220_123456",
  "amount": 5000,
  "account_number": "0123456789",
  "account_name": "Jane Smith",
  "bank_code": "058",
  "narration": "Payment for services"
}
```

### Field Requirements:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `request-id` | string | Yes | Unique transaction reference (your system) |
| `amount` | number | Yes | Amount in Naira (minimum: ₦100) |
| `account_number` | string | Yes | 10-digit account number |
| `account_name` | string | Yes | Account holder's name |
| `bank_code` | string | Yes | 3-digit bank code (see bank list) |
| `narration` | string | Yes | Transfer description |

### Success Response (200):
```json
{
  "status": "success",
  "message": "Transfer successful",
  "data": {
    "reference": "REF123456789",
    "request_id": "TXN_20260220_123456",
    "amount": 5000,
    "fee": 50,
    "total_deducted": 5050,
    "recipient": {
      "account_number": "0123456789",
      "account_name": "Jane Smith",
      "bank_name": "GTBank"
    },
    "status": "successful",
    "created_at": "2026-02-20T11:00:00Z"
  }
}
```

### Error Response (400):
```json
{
  "status": "fail",
  "message": "Insufficient Funds. Your current wallet balance is ₦3,000.00. Required: ₦5,050.00"
}
```

### PHP Example:
```php
<?php

$secretKey = 'sk_live_your_secret_key_here';
$baseUrl = 'https://app.pointwave.ng/api';

// Generate unique request ID
$requestId = 'TXN_' . date('Ymd_His') . '_' . uniqid();

$data = [
    'request-id' => $requestId,
    'amount' => 5000,
    'account_number' => '0123456789',
    'account_name' => 'Jane Smith',
    'bank_code' => '058', // GTBank
    'narration' => 'Payment for services'
];

$ch = curl_init($baseUrl . '/transfers/initiate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Token ' . $secretKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && $result['status'] === 'success') {
    $reference = $result['data']['reference'];
    echo "Transfer successful: " . $reference;
} else {
    echo "Error: " . $result['message'];
}
```

---

## Webhooks

Receive real-time notifications when transactions occur.

### Setup Webhook URL

1. Login to dashboard
2. Go to **Settings** → **Webhooks**
3. Enter your webhook URL: `https://yourdomain.com/webhook`
4. Save

### Webhook Payload

When a customer deposits money into their virtual account, we send a POST request to your webhook URL:

```json
{
  "event": "charge.success",
  "data": {
    "reference": "REF987654321",
    "amount": 10000,
    "fee": 100,
    "net_amount": 9900,
    "customer": {
      "customer_id": "cust_abc123xyz456",
      "email": "customer@example.com",
      "name": "John Doe"
    },
    "virtual_account": {
      "account_number": "9876543210",
      "bank_name": "PalmPay"
    },
    "status": "successful",
    "paid_at": "2026-02-20T12:00:00Z"
  }
}
```

### Webhook Security

Verify webhook signatures to ensure requests are from PointWave:

```php
<?php

// Your webhook secret (from dashboard)
$webhookSecret = 'whsec_your_webhook_secret_here';

// Get raw POST data
$payload = file_get_contents('php://input');

// Get signature from header
$signature = $_SERVER['HTTP_X_POINTWAVE_SIGNATURE'] ?? '';

// Verify signature
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    die('Invalid signature');
}

// Process webhook
$data = json_decode($payload, true);

if ($data['event'] === 'charge.success') {
    $reference = $data['data']['reference'];
    $amount = $data['data']['amount'];
    $customerId = $data['data']['customer']['customer_id'];
    
    // Update your database
    // Credit customer account
    // Send notification
    
    echo "Webhook processed";
}

http_response_code(200);
```

---

## Error Handling

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 400 | Bad Request (validation error) |
| 401 | Unauthorized (invalid API key) |
| 403 | Forbidden (IP not whitelisted) |
| 404 | Not Found |
| 500 | Server Error |

### Common Errors

#### 1. Invalid API Key
```json
{
  "status": "error",
  "message": "Invalid Authorization Token"
}
```
**Solution:** Check your Secret Key in dashboard

#### 2. IP Not Whitelisted
```json
{
  "status": "error",
  "message": "Access Denied"
}
```
**Solution:** Contact support to whitelist your server IP

#### 3. Insufficient Funds
```json
{
  "status": "fail",
  "message": "Insufficient Funds. Your current wallet balance is ₦3,000.00. Required: ₦5,050.00"
}
```
**Solution:** Fund your wallet from dashboard

#### 4. Duplicate Request ID
```json
{
  "status": "error",
  "message": "Duplicate request-id. This transaction has already been processed."
}
```
**Solution:** Use a unique request-id for each transaction

---

## Complete Code Examples

### PHP Complete Integration

```php
<?php

class PointWaveAPI {
    private $secretKey;
    private $baseUrl = 'https://app.pointwave.ng/api';
    
    public function __construct($secretKey) {
        $this->secretKey = $secretKey;
    }
    
    private function makeRequest($endpoint, $data = []) {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token ' . $this->secretKey,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'code' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }
    
    public function createCustomer($email, $firstName, $lastName, $phone, $bvn) {
        return $this->makeRequest('/customers/create', [
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'bvn' => $bvn
        ]);
    }
    
    public function createVirtualAccount($customerId, $accountName) {
        return $this->makeRequest('/virtual-accounts/create', [
            'customer_id' => $customerId,
            'account_name' => $accountName
        ]);
    }
    
    public function initiateTransfer($amount, $accountNumber, $accountName, $bankCode, $narration) {
        $requestId = 'TXN_' . date('Ymd_His') . '_' . uniqid();
        
        return $this->makeRequest('/transfers/initiate', [
            'request-id' => $requestId,
            'amount' => $amount,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'bank_code' => $bankCode,
            'narration' => $narration
        ]);
    }
}

// Usage Example
$api = new PointWaveAPI('sk_live_your_secret_key_here');

// Step 1: Create Customer
$customer = $api->createCustomer(
    'customer@example.com',
    'John',
    'Doe',
    '08012345678',
    '22222222222'
);

if ($customer['code'] === 200 && $customer['data']['status'] === 'success') {
    $customerId = $customer['data']['data']['customer_id'];
    echo "Customer created: " . $customerId . "\n";
    
    // Step 2: Create Virtual Account
    $account = $api->createVirtualAccount($customerId, 'John Doe');
    
    if ($account['code'] === 200 && $account['data']['status'] === 'success') {
        $accountNumber = $account['data']['data']['account_number'];
        echo "Virtual Account: " . $accountNumber . "\n";
    }
}

// Step 3: Send Transfer
$transfer = $api->initiateTransfer(
    5000,
    '0123456789',
    'Jane Smith',
    '058',
    'Payment for services'
);

if ($transfer['code'] === 200 && $transfer['data']['status'] === 'success') {
    echo "Transfer successful: " . $transfer['data']['data']['reference'] . "\n";
} else {
    echo "Transfer failed: " . $transfer['data']['message'] . "\n";
}
```

### Python Complete Integration

```python
import requests
import json
from datetime import datetime
import hashlib
import hmac

class PointWaveAPI:
    def __init__(self, secret_key):
        self.secret_key = secret_key
        self.base_url = 'https://app.pointwave.ng/api'
        self.headers = {
            'Authorization': f'Token {secret_key}',
            'Content-Type': 'application/json'
        }
    
    def create_customer(self, email, first_name, last_name, phone, bvn):
        data = {
            'email': email,
            'first_name': first_name,
            'last_name': last_name,
            'phone': phone,
            'bvn': bvn
        }
        response = requests.post(
            f'{self.base_url}/customers/create',
            headers=self.headers,
            json=data
        )
        return response.json()
    
    def create_virtual_account(self, customer_id, account_name):
        data = {
            'customer_id': customer_id,
            'account_name': account_name
        }
        response = requests.post(
            f'{self.base_url}/virtual-accounts/create',
            headers=self.headers,
            json=data
        )
        return response.json()
    
    def initiate_transfer(self, amount, account_number, account_name, bank_code, narration):
        request_id = f"TXN_{datetime.now().strftime('%Y%m%d_%H%M%S')}_{hash(datetime.now())}"
        data = {
            'request-id': request_id,
            'amount': amount,
            'account_number': account_number,
            'account_name': account_name,
            'bank_code': bank_code,
            'narration': narration
        }
        response = requests.post(
            f'{self.base_url}/transfers/initiate',
            headers=self.headers,
            json=data
        )
        return response.json()

# Usage Example
api = PointWaveAPI('sk_live_your_secret_key_here')

# Create Customer
customer = api.create_customer(
    'customer@example.com',
    'John',
    'Doe',
    '08012345678',
    '22222222222'
)

if customer['status'] == 'success':
    customer_id = customer['data']['customer_id']
    print(f"Customer created: {customer_id}")
    
    # Create Virtual Account
    account = api.create_virtual_account(customer_id, 'John Doe')
    
    if account['status'] == 'success':
        account_number = account['data']['account_number']
        print(f"Virtual Account: {account_number}")

# Send Transfer
transfer = api.initiate_transfer(
    5000,
    '0123456789',
    'Jane Smith',
    '058',
    'Payment for services'
)

if transfer['status'] == 'success':
    print(f"Transfer successful: {transfer['data']['reference']}")
else:
    print(f"Transfer failed: {transfer['message']}")
```

### Node.js Complete Integration

```javascript
const axios = require('axios');
const crypto = require('crypto');

class PointWaveAPI {
    constructor(secretKey) {
        this.secretKey = secretKey;
        this.baseUrl = 'https://app.pointwave.ng/api';
        this.headers = {
            'Authorization': `Token ${secretKey}`,
            'Content-Type': 'application/json'
        };
    }
    
    async createCustomer(email, firstName, lastName, phone, bvn) {
        try {
            const response = await axios.post(
                `${this.baseUrl}/customers/create`,
                {
                    email,
                    first_name: firstName,
                    last_name: lastName,
                    phone,
                    bvn
                },
                { headers: this.headers }
            );
            return response.data;
        } catch (error) {
            return error.response.data;
        }
    }
    
    async createVirtualAccount(customerId, accountName) {
        try {
            const response = await axios.post(
                `${this.baseUrl}/virtual-accounts/create`,
                {
                    customer_id: customerId,
                    account_name: accountName
                },
                { headers: this.headers }
            );
            return response.data;
        } catch (error) {
            return error.response.data;
        }
    }
    
    async initiateTransfer(amount, accountNumber, accountName, bankCode, narration) {
        const requestId = `TXN_${Date.now()}_${crypto.randomBytes(4).toString('hex')}`;
        
        try {
            const response = await axios.post(
                `${this.baseUrl}/transfers/initiate`,
                {
                    'request-id': requestId,
                    amount,
                    account_number: accountNumber,
                    account_name: accountName,
                    bank_code: bankCode,
                    narration
                },
                { headers: this.headers }
            );
            return response.data;
        } catch (error) {
            return error.response.data;
        }
    }
}

// Usage Example
(async () => {
    const api = new PointWaveAPI('sk_live_your_secret_key_here');
    
    // Create Customer
    const customer = await api.createCustomer(
        'customer@example.com',
        'John',
        'Doe',
        '08012345678',
        '22222222222'
    );
    
    if (customer.status === 'success') {
        const customerId = customer.data.customer_id;
        console.log(`Customer created: ${customerId}`);
        
        // Create Virtual Account
        const account = await api.createVirtualAccount(customerId, 'John Doe');
        
        if (account.status === 'success') {
            const accountNumber = account.data.account_number;
            console.log(`Virtual Account: ${accountNumber}`);
        }
    }
    
    // Send Transfer
    const transfer = await api.initiateTransfer(
        5000,
        '0123456789',
        'Jane Smith',
        '058',
        'Payment for services'
    );
    
    if (transfer.status === 'success') {
        console.log(`Transfer successful: ${transfer.data.reference}`);
    } else {
        console.log(`Transfer failed: ${transfer.message}`);
    }
})();
```

---

## Nigerian Bank Codes

| Bank Name | Code |
|-----------|------|
| Access Bank | 044 |
| GTBank | 058 |
| First Bank | 011 |
| UBA | 033 |
| Zenith Bank | 057 |
| Fidelity Bank | 070 |
| FCMB | 214 |
| Union Bank | 032 |
| Sterling Bank | 232 |
| Stanbic IBTC | 221 |
| Wema Bank | 035 |
| Polaris Bank | 076 |
| Ecobank | 050 |
| Keystone Bank | 082 |
| Unity Bank | 215 |
| PalmPay | 999991 |
| OPay | 999992 |
| Kuda Bank | 090267 |
| Moniepoint | 090405 |

---

## Support

**Email:** support@pointwave.ng  
**Website:** https://app.pointwave.ng  
**Documentation:** https://app.pointwave.ng/docs

---

## Checklist for Developers

- [ ] Get API credentials from dashboard
- [ ] Whitelist server IP address
- [ ] Test customer creation
- [ ] Test virtual account creation
- [ ] Test bank transfer
- [ ] Setup webhook URL
- [ ] Verify webhook signatures
- [ ] Handle errors properly
- [ ] Test in production

---

**Last Updated:** February 20, 2026  
**API Version:** 1.0
