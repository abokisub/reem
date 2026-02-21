# 🚀 PointWave API - Complete Developer Integration Guide

**Base URL:** `https://app.pointwave.ng/api/v1`  
**Support:** support@pointwave.ng  
**Dashboard:** https://app.pointwave.ng

---

## ⚡ Quick Start (2 Steps)

### Step 1: Get Your API Credentials

1. Login to https://app.pointwave.ng
2. Go to **Settings** → **API Keys**
3. Copy these 3 values:
   - **Secret Key** (starts with `sk_live_...`)
   - **API Key** (starts with `pk_live_...`)
   - **Business ID** (40-character string)

### Step 2: Test Your Connection

```bash
curl -X POST "https://app.pointwave.ng/api/v1/customers" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "first_name": "Test",
    "last_name": "User",
    "phone": "08012345678",
    "bvn": "22222222222"
  }'
```

If you get `200 OK`, you're ready!

---

## 🔐 Authentication

All API requests require these 3 headers:

```
Authorization: Bearer YOUR_SECRET_KEY
x-api-key: YOUR_API_KEY
x-business-id: YOUR_BUSINESS_ID
Content-Type: application/json
```

---

## 📋 API Endpoints

### 1️⃣ Create Customer

**Endpoint:** `POST /api/v1/customers`

**Request:**
```json
{
  "email": "john@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "08012345678",
  "bvn": "22222222222"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Customer created successfully",
  "data": {
    "customer_id": "cust_abc123xyz456",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "08012345678",
    "created_at": "2026-02-20T10:30:00Z"
  }
}
```

---

### 2️⃣ Delete Customer

**Endpoint:** `DELETE /api/v1/customers/{customer_id}`

**Request:**
```bash
curl -X DELETE "https://app.pointwave.ng/api/v1/customers/cust_abc123xyz456" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Customer deleted successfully",
  "data": {
    "customer_id": "cust_abc123xyz456",
    "deleted_at": "2026-02-21T00:30:00Z"
  }
}
```

**Error Response (400):**
```json
{
  "status": "error",
  "message": "Cannot delete customer with active virtual accounts. Please deactivate all virtual accounts first."
}
```

---

### 3️⃣ Get Customer Details

**Endpoint:** `GET /api/v1/customers/{customer_id}`

**Request:**
```bash
curl -X GET "https://app.pointwave.ng/api/v1/customers/cust_abc123xyz456" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Customer details retrieved",
  "data": {
    "customer_id": "cust_abc123xyz456",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "08012345678",
    "kyc_status": "approved",
    "created_at": "2026-02-20T10:30:00Z"
  }
}
```

---

### 4️⃣ Update Customer

**Endpoint:** `PUT /api/v1/customers/{customer_id}`

**Request:**
```json
{
  "first_name": "John",
  "last_name": "Smith",
  "phone": "08087654321",
  "address": "123 New Street, Lagos"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Customer updated successfully",
  "data": {
    "customer_id": "cust_abc123xyz456",
    "kyc_status": "approved"
  }
}
```

---

### 5️⃣ Create Virtual Account

**Endpoint:** `POST /api/v1/virtual-accounts`

**Request:**
```json
{
  "customer_id": "cust_abc123xyz456",
  "account_name": "John Doe"
}
```

**Success Response (200):**
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

---

### 6️⃣ Update Virtual Account Status

**Endpoint:** `PUT /api/v1/virtual-accounts/{virtual_account_id}`

**Request:**
```json
{
  "status": "deactivated",
  "reason": "Customer requested account closure"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Virtual account status updated",
  "data": {
    "virtual_account_id": "va_xyz789abc123",
    "status": "deactivated"
  }
}
```

**Note:** Only static accounts can be deactivated. Dynamic accounts cannot be updated.

---

### 7️⃣ List Virtual Accounts

**Endpoint:** `GET /api/v1/virtual-accounts`

**Query Parameters:**
- `status` (optional): Filter by status (active, deactivated)
- `customer_id` (optional): Filter by customer ID
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 20, max: 100)

**Request:**
```bash
curl -X GET "https://app.pointwave.ng/api/v1/virtual-accounts?status=active&page=1&per_page=20" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Virtual accounts retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "virtual_account_id": "va_xyz789abc123",
        "account_number": "9876543210",
        "account_name": "John Doe",
        "bank_name": "PalmPay",
        "bank_code": "999991",
        "customer_id": "cust_abc123xyz456",
        "account_type": "static",
        "status": "active",
        "created_at": "2026-02-20T10:35:00Z"
      }
    ],
    "total": 50,
    "per_page": 20,
    "last_page": 3
  }
}
```

---

### 8️⃣ Get Virtual Account

**Endpoint:** `GET /api/v1/virtual-accounts/{virtual_account_id}`

**Request:**
```bash
curl -X GET "https://app.pointwave.ng/api/v1/virtual-accounts/va_xyz789abc123" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Virtual account retrieved successfully",
  "data": {
    "virtual_account_id": "va_xyz789abc123",
    "account_number": "9876543210",
    "account_name": "John Doe",
    "bank_name": "PalmPay",
    "bank_code": "999991",
    "customer": {
      "customer_id": "cust_abc123xyz456",
      "email": "john@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "phone": "08012345678"
    },
    "account_type": "static",
    "status": "active",
    "created_at": "2026-02-20T10:35:00Z"
  }
}
```

---

### 9️⃣ Delete Virtual Account

**Endpoint:** `DELETE /api/v1/virtual-accounts/{virtual_account_id}`

**Request:**
```bash
curl -X DELETE "https://app.pointwave.ng/api/v1/virtual-accounts/va_xyz789abc123" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Virtual account deleted successfully",
  "data": {
    "virtual_account_id": "va_xyz789abc123",
    "account_number": "9876543210",
    "status": "deactivated",
    "deleted_at": "2026-02-21T00:35:00Z"
  }
}
```

**Error Response (400):**
```json
{
  "status": "error",
  "message": "Dynamic virtual accounts cannot be deleted"
}
```

**Note:** Only static virtual accounts can be deleted. Dynamic accounts cannot be deleted.

---

### 🔟 Get Banks List

**Endpoint:** `GET /api/v1/banks`

**Request:**
```bash
curl "https://app.pointwave.ng/api/v1/banks" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Banks retrieved successfully",
  "data": {
    "banks": [
      {
        "id": 1,
        "name": "Access Bank",
        "code": "044",
        "slug": "access-bank",
        "active": true
      },
      {
        "id": 2,
        "name": "GTBank",
        "code": "058",
        "slug": "gtbank",
        "active": true
      }
    ],
    "total": 24
  }
}
```

**Note:** Cache this list in your application to reduce API calls. The bank list rarely changes.

---

### 1️⃣1️⃣ Get Wallet Balance

**Endpoint:** `GET /api/v1/balance`

**Request:**
```bash
curl "https://app.pointwave.ng/api/v1/balance" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Balance retrieved successfully",
  "data": {
    "balance": 50000.00,
    "currency": "NGN",
    "formatted_balance": "₦50,000.00"
  }
}
```

**Use Case:** Check wallet balance before initiating transfers to ensure sufficient funds.

---

### 1️⃣2️⃣ Bank Transfer

**Endpoint:** `POST /api/v1/transfers`

**Request:**
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

**Success Response (200):**
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

---

### 1️⃣3️⃣ Get Transactions

**Endpoint:** `GET /api/v1/transactions`

**Query Parameters:**
- `page` (optional): Page number for pagination (default: 1)
- `per_page` (optional): Items per page (default: 20, max: 100)

**Request:**
```bash
curl -X GET "https://app.pointwave.ng/api/v1/transactions?page=1&per_page=20" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID"
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Transactions retrieved",
  "data": {
    "current_page": 1,
    "data": [
      {
        "transaction_id": "TXN123456",
        "reference": "REF987654321",
        "amount": 10000,
        "fee": 100,
        "type": "credit",
        "category": "virtual_account_deposit",
        "status": "success",
        "created_at": "2026-02-20T12:00:00Z"
      }
    ],
    "total": 150,
    "per_page": 20,
    "last_page": 8
  }
}
```

---

## 🔐 KYC Endpoints

### 1️⃣4️⃣ Get KYC Status

**Endpoint:** `GET /api/v1/kyc/status`

**Request:**
```bash
curl -X GET "https://app.pointwave.ng/api/v1/kyc/status" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID"
```

**Success Response (200):**
```json
{
  "status": "success",
  "data": {
    "business_info": {
      "status": "approved",
      "submitted_at": "2026-02-15T10:00:00Z",
      "approved_at": "2026-02-16T14:30:00Z"
    },
    "directors_info": {
      "status": "pending",
      "submitted_at": "2026-02-18T09:00:00Z"
    },
    "documents": {
      "status": "not_submitted"
    },
    "overall_status": "in_progress"
  }
}
```

---

### 1️⃣5️⃣ Submit KYC Section

**Endpoint:** `POST /api/v1/kyc/submit/{section}`

**Sections:** `business_info`, `directors_info`, `documents`

**Request (Business Info):**
```json
{
  "business_name": "Tech Solutions Ltd",
  "business_type": "limited_liability",
  "rc_number": "RC123456",
  "tax_id": "TIN987654321",
  "business_address": "123 Business Street, Lagos",
  "business_phone": "08012345678",
  "business_email": "info@techsolutions.com"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Business information submitted successfully",
  "data": {
    "section": "business_info",
    "status": "pending",
    "submitted_at": "2026-02-20T10:00:00Z"
  }
}
```

---

### 1️⃣6️⃣ Verify BVN

**Endpoint:** `POST /api/v1/kyc/verify-bvn`

**Request:**
```json
{
  "bvn": "22222222222",
  "first_name": "John",
  "last_name": "Doe",
  "date_of_birth": "1990-05-15"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "BVN verified successfully",
  "data": {
    "verified": true,
    "bvn": "22222222222",
    "name_match": true,
    "dob_match": true
  }
}
```

---

### 1️⃣7️⃣ Verify NIN

**Endpoint:** `POST /api/v1/kyc/verify-nin`

**Request:**
```json
{
  "nin": "12345678901",
  "first_name": "John",
  "last_name": "Doe",
  "date_of_birth": "1990-05-15"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "NIN verified successfully",
  "data": {
    "verified": true,
    "nin": "12345678901",
    "name_match": true,
    "dob_match": true
  }
}
```

---

### 1️⃣8️⃣ Verify Bank Account

**Endpoint:** `POST /api/v1/kyc/verify-bank-account`

**Request:**
```json
{
  "account_number": "0123456789",
  "bank_code": "058"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Bank account verified",
  "data": {
    "account_number": "0123456789",
    "account_name": "JOHN DOE",
    "bank_name": "GTBank",
    "bank_code": "058"
  }
}
```

---

## 💻 Complete Code Examples

### PHP Implementation

```php
<?php

class PointWaveAPI {
    private $secretKey;
    private $apiKey;
    private $businessId;
    private $baseUrl = 'https://app.pointwave.ng/api/v1';
    
    public function __construct($secretKey, $apiKey, $businessId) {
        $this->secretKey = $secretKey;
        $this->apiKey = $apiKey;
        $this->businessId = $businessId;
    }
    
    private function makeRequest($endpoint, $data = [], $method = 'POST') {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->secretKey,
            'x-api-key: ' . $this->apiKey,
            'x-business-id: ' . $this->businessId,
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
        return $this->makeRequest('/customers', [
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'bvn' => $bvn
        ]);
    }
    
    public function getCustomer($customerId) {
        return $this->makeRequest('/customers/' . $customerId, [], 'GET');
    }
    
    public function updateCustomer($customerId, $data) {
        return $this->makeRequest('/customers/' . $customerId, $data, 'PUT');
    }
    
    public function createVirtualAccount($customerId, $accountName) {
        return $this->makeRequest('/virtual-accounts', [
            'customer_id' => $customerId,
            'account_name' => $accountName
        ]);
    }
    
    public function updateVirtualAccount($vaId, $status, $reason = null) {
        return $this->makeRequest('/virtual-accounts/' . $vaId, [
            'status' => $status,
            'reason' => $reason
        ], 'PUT');
    }
    
    public function getTransactions($page = 1, $perPage = 20) {
        return $this->makeRequest('/transactions?page=' . $page . '&per_page=' . $perPage, [], 'GET');
    }
    
    public function initiateTransfer($amount, $accountNumber, $accountName, $bankCode, $narration) {
        $requestId = 'TXN_' . date('Ymd_His') . '_' . uniqid();
        
        return $this->makeRequest('/transfers', [
            'request-id' => $requestId,
            'amount' => $amount,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'bank_code' => $bankCode,
            'narration' => $narration
        ]);
    }
}

// USAGE EXAMPLE
$api = new PointWaveAPI(
    'sk_live_your_secret_key',
    'pk_live_your_api_key',
    'your_business_id_40_chars'
);

// Create Customer
$customer = $api->createCustomer(
    'john@example.com',
    'John',
    'Doe',
    '08012345678',
    '22222222222'
);

if ($customer['code'] === 200 && $customer['data']['status'] === 'success') {
    $customerId = $customer['data']['data']['customer_id'];
    echo "✅ Customer created: " . $customerId . "\n";
    
    // Get Customer Details
    $customerDetails = $api->getCustomer($customerId);
    if ($customerDetails['code'] === 200) {
        echo "✅ Customer retrieved: " . $customerDetails['data']['data']['email'] . "\n";
    }
    
    // Update Customer
    $updated = $api->updateCustomer($customerId, [
        'phone' => '08087654321',
        'address' => '123 New Street, Lagos'
    ]);
    if ($updated['code'] === 200) {
        echo "✅ Customer updated\n";
    }
    
    // Create Virtual Account
    $account = $api->createVirtualAccount($customerId, 'John Doe');
    
    if ($account['code'] === 200 && $account['data']['status'] === 'success') {
        $accountNumber = $account['data']['data']['account_number'];
        $vaId = $account['data']['data']['virtual_account_id'];
        echo "✅ Virtual Account: " . $accountNumber . " (PalmPay)\n";
        
        // Update VA Status (deactivate)
        $vaUpdate = $api->updateVirtualAccount($vaId, 'deactivated', 'Testing');
        if ($vaUpdate['code'] === 200) {
            echo "✅ Virtual Account deactivated\n";
        }
    }
    
    // Get Transactions
    $transactions = $api->getTransactions(1, 10);
    if ($transactions['code'] === 200) {
        echo "✅ Retrieved " . count($transactions['data']['data']['data']) . " transactions\n";
    }
}

// Send Transfer
$transfer = $api->initiateTransfer(
    5000,
    '0123456789',
    'Jane Smith',
    '058',
    'Payment for services'
);

if ($transfer['code'] === 200 && $transfer['data']['status'] === 'success') {
    echo "✅ Transfer successful: " . $transfer['data']['data']['reference'] . "\n";
} else {
    echo "❌ Transfer failed: " . $transfer['data']['message'] . "\n";
}
```

---

### Python Implementation

```python
import requests
import json
from datetime import datetime

class PointWaveAPI:
    def __init__(self, secret_key, api_key, business_id):
        self.secret_key = secret_key
        self.api_key = api_key
        self.business_id = business_id
        self.base_url = 'https://app.pointwave.ng/api/v1'
        self.headers = {
            'Authorization': f'Bearer {secret_key}',
            'x-api-key': api_key,
            'x-business-id': business_id,
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
            f'{self.base_url}/customers',
            headers=self.headers,
            json=data
        )
        return response.json()
    
    def get_customer(self, customer_id):
        response = requests.get(
            f'{self.base_url}/customers/{customer_id}',
            headers=self.headers
        )
        return response.json()
    
    def update_customer(self, customer_id, data):
        response = requests.put(
            f'{self.base_url}/customers/{customer_id}',
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
            f'{self.base_url}/virtual-accounts',
            headers=self.headers,
            json=data
        )
        return response.json()
    
    def update_virtual_account(self, va_id, status, reason=None):
        data = {'status': status}
        if reason:
            data['reason'] = reason
        response = requests.put(
            f'{self.base_url}/virtual-accounts/{va_id}',
            headers=self.headers,
            json=data
        )
        return response.json()
    
    def get_transactions(self, page=1, per_page=20):
        response = requests.get(
            f'{self.base_url}/transactions?page={page}&per_page={per_page}',
            headers=self.headers
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
            f'{self.base_url}/transfers',
            headers=self.headers,
            json=data
        )
        return response.json()

# USAGE EXAMPLE
api = PointWaveAPI(
    'sk_live_your_secret_key',
    'pk_live_your_api_key',
    'your_business_id_40_chars'
)

# Create Customer
customer = api.create_customer(
    'john@example.com',
    'John',
    'Doe',
    '08012345678',
    '22222222222'
)

if customer['status'] == 'success':
    customer_id = customer['data']['customer_id']
    print(f"✅ Customer created: {customer_id}")
    
    # Create Virtual Account
    account = api.create_virtual_account(customer_id, 'John Doe')
    
    if account['status'] == 'success':
        account_number = account['data']['account_number']
        print(f"✅ Virtual Account: {account_number} (PalmPay)")

# Send Transfer
transfer = api.initiate_transfer(
    5000,
    '0123456789',
    'Jane Smith',
    '058',
    'Payment for services'
)

if transfer['status'] == 'success':
    print(f"✅ Transfer successful: {transfer['data']['reference']}")
else:
    print(f"❌ Transfer failed: {transfer['message']}")
```

---

### Node.js Implementation

```javascript
const axios = require('axios');
const crypto = require('crypto');

class PointWaveAPI {
    constructor(secretKey, apiKey, businessId) {
        this.secretKey = secretKey;
        this.apiKey = apiKey;
        this.businessId = businessId;
        this.baseUrl = 'https://app.pointwave.ng/api/v1';
        this.headers = {
            'Authorization': `Bearer ${secretKey}`,
            'x-api-key': apiKey,
            'x-business-id': businessId,
            'Content-Type': 'application/json'
        };
    }
    
    async createCustomer(email, firstName, lastName, phone, bvn) {
        try {
            const response = await axios.post(
                `${this.baseUrl}/customers`,
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
    
    async getCustomer(customerId) {
        try {
            const response = await axios.get(
                `${this.baseUrl}/customers/${customerId}`,
                { headers: this.headers }
            );
            return response.data;
        } catch (error) {
            return error.response.data;
        }
    }
    
    async updateCustomer(customerId, data) {
        try {
            const response = await axios.put(
                `${this.baseUrl}/customers/${customerId}`,
                data,
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
                `${this.baseUrl}/virtual-accounts`,
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
    
    async updateVirtualAccount(vaId, status, reason = null) {
        try {
            const response = await axios.put(
                `${this.baseUrl}/virtual-accounts/${vaId}`,
                { status, reason },
                { headers: this.headers }
            );
            return response.data;
        } catch (error) {
            return error.response.data;
        }
    }
    
    async getTransactions(page = 1, perPage = 20) {
        try {
            const response = await axios.get(
                `${this.baseUrl}/transactions?page=${page}&per_page=${perPage}`,
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
                `${this.baseUrl}/transfers`,
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

// USAGE EXAMPLE
(async () => {
    const api = new PointWaveAPI(
        'sk_live_your_secret_key',
        'pk_live_your_api_key',
        'your_business_id_40_chars'
    );
    
    // Create Customer
    const customer = await api.createCustomer(
        'john@example.com',
        'John',
        'Doe',
        '08012345678',
        '22222222222'
    );
    
    if (customer.status === 'success') {
        const customerId = customer.data.customer_id;
        console.log(`✅ Customer created: ${customerId}`);
        
        // Create Virtual Account
        const account = await api.createVirtualAccount(customerId, 'John Doe');
        
        if (account.status === 'success') {
            const accountNumber = account.data.account_number;
            console.log(`✅ Virtual Account: ${accountNumber} (PalmPay)`);
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
        console.log(`✅ Transfer successful: ${transfer.data.reference}`);
    } else {
        console.log(`❌ Transfer failed: ${transfer.message}`);
    }
})();
```

---

## 🏦 Nigerian Bank Codes

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

## ⚠️ Error Handling

### Common Errors & Solutions

#### 1. Invalid API Key
```json
{
  "status": "error",
  "message": "Invalid API credentials"
}
```
**Solution:** Check all 3 credentials in dashboard Settings → API Keys

#### 2. Missing Headers
```json
{
  "status": "error",
  "message": "Missing x-business-id or x-api-key header"
}
```
**Solution:** Ensure you're sending all 3 required headers

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

#### 5. Customer Not Found
```json
{
  "status": "error",
  "message": "Customer not found"
}
```
**Solution:** Create customer first before creating virtual account

---

## 🔔 Webhooks

### Setup Webhook

1. Login to dashboard
2. Go to **Settings** → **Webhooks**
3. Enter your URL: `https://yourdomain.com/webhook`
4. Save

### Webhook Payload

When customer deposits money:

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
      "email": "john@example.com",
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

### Verify Webhook Signature

```php
<?php
$webhookSecret = 'whsec_your_secret_from_dashboard';
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_POINTWAVE_SIGNATURE'] ?? '';

$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    die('Invalid signature');
}

$data = json_decode($payload, true);
// Process webhook...
http_response_code(200);
```

---

## ✅ Integration Checklist

- [ ] Get API credentials from dashboard (Secret Key, API Key, Business ID)
- [ ] Test customer creation endpoint (POST /customers)
- [ ] Test get customer endpoint (GET /customers/{id})
- [ ] Test update customer endpoint (PUT /customers/{id})
- [ ] Test delete customer endpoint (DELETE /customers/{id})
- [ ] Test list virtual accounts endpoint (GET /virtual-accounts)
- [ ] Test virtual account creation endpoint (POST /virtual-accounts)
- [ ] Test get virtual account endpoint (GET /virtual-accounts/{id})
- [ ] Test virtual account update endpoint (PUT /virtual-accounts/{id})
- [ ] Test delete virtual account endpoint (DELETE /virtual-accounts/{id})
- [ ] Test get transactions endpoint (GET /transactions)
- [ ] Test bank transfer endpoint (POST /transfers)
- [ ] Setup webhook URL in dashboard
- [ ] Verify webhook signatures
- [ ] Handle all error responses
- [ ] Test with real transactions
- [ ] Monitor API logs regularly
- [ ] Keep credentials secure (never expose in frontend)

---

## 🎯 Best Practices

1. **Always create customer before virtual account**
2. **Use unique request-id for each transfer** (prevents duplicate transactions)
3. **Verify webhook signatures** (prevents fake webhooks)
4. **Handle errors gracefully** (show user-friendly messages)
5. **Log all API requests** (for debugging and audit)
6. **Never expose credentials** (keep them server-side only)
7. **Test in production with small amounts first**
8. **Monitor your wallet balance** (fund before it runs out)
9. **Store customer_id in your database** (for future reference)
10. **Use HTTPS for webhook URL** (required for security)

---

## 📞 Support

**Email:** support@pointwave.ng  
**Dashboard:** https://app.pointwave.ng  
**Documentation:** https://app.pointwave.ng/docs

**Response Time:** Within 24 hours

---

## 📝 Quick Reference

### Authentication Headers
```
Authorization: Bearer YOUR_SECRET_KEY
x-api-key: YOUR_API_KEY
x-business-id: YOUR_BUSINESS_ID
Content-Type: application/json
```

### Endpoints
```
POST   /api/v1/customers              # Create customer
DELETE /api/v1/customers/{id}         # Delete customer
GET    /api/v1/customers/{id}         # Get customer details
PUT    /api/v1/customers/{id}         # Update customer
GET    /api/v1/virtual-accounts       # List virtual accounts
POST   /api/v1/virtual-accounts       # Create virtual account
GET    /api/v1/virtual-accounts/{id}  # Get virtual account
PUT    /api/v1/virtual-accounts/{id}  # Update virtual account status
DELETE /api/v1/virtual-accounts/{id}  # Delete virtual account
GET    /api/v1/transactions           # Get transaction history
POST   /api/v1/transfers              # Initiate bank transfer
GET    /api/v1/kyc/status             # Get KYC status
POST   /api/v1/kyc/submit/{section}   # Submit KYC section
POST   /api/v1/kyc/verify-bvn         # Verify BVN
POST   /api/v1/kyc/verify-nin         # Verify NIN
POST   /api/v1/kyc/verify-bank-account # Verify bank account
```

---

**Last Updated:** February 20, 2026  
**API Version:** 1.0

---

## 🚀 Ready to Integrate?

Copy the code examples above and start integrating in minutes!

Need help? Email support@pointwave.ng

