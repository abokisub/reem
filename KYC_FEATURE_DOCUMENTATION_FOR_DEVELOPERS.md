# PointWave KYC Verification API - Complete Documentation

**Date:** February 21, 2026  
**Status:** Production Ready ✅  
**Base URL:** `https://app.pointwave.ng/api/v1`

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Available KYC Services](#available-kyc-services)
4. [Pricing](#pricing)
5. [API Endpoints](#api-endpoints)
6. [Code Examples](#code-examples)
7. [Error Handling](#error-handling)
8. [Testing](#testing)
9. [Support](#support)

---

## Overview

PointWave provides comprehensive KYC (Know Your Customer) verification services powered by EaseID. Our API allows you to verify customer identities using BVN, NIN, bank accounts, facial recognition, and more.

### Key Features

✅ **11 KYC Verification Services** - BVN, NIN, Bank Account, Face Recognition, Liveness Detection, Blacklist Check, Credit Score, Loan Features, and EaseID Balance  
✅ **Pay-As-You-Go Pricing** - Only pay for successful verifications  
✅ **Real-Time Verification** - Instant results from government databases  
✅ **Secure & Compliant** - Bank-grade security and NDPR compliant  
✅ **Caching** - Duplicate verifications are cached (no double charges)  
✅ **Detailed Responses** - Get full customer details including name, phone, DOB, address  
✅ **Webhook Support** - Get notified of verification results  
✅ **Sandbox Mode** - Test without charges

---

## Authentication

All API requests require 4 headers:

```http
Authorization: Bearer YOUR_SECRET_KEY
x-api-key: YOUR_API_KEY
x-business-id: YOUR_BUSINESS_ID
Idempotency-Key: UNIQUE_REQUEST_ID
Content-Type: application/json
```

### Getting Your Credentials

1. Sign up at https://app.pointwave.ng
2. Complete your business KYC verification
3. Navigate to Settings → API Keys
4. Copy your API Key, Secret Key, and Business ID

### Important Notes

- Keep your Secret Key secure (never expose in frontend code)
- Use a unique Idempotency-Key for each request to prevent duplicates
- No IP whitelisting required - API accepts connections from anywhere

---

## Available KYC Services

| Service | Description | Use Case |
|---------|-------------|----------|
| **Enhanced BVN** | Full BVN details with photo | Customer onboarding, identity verification |
| **Enhanced NIN** | Full NIN details with photo | Government ID verification |
| **Basic BVN** | Name matching only | Quick verification |
| **Basic NIN** | Name matching only | Quick verification |
| **Bank Account** | Verify account ownership | Transfer confirmation |
| **Face Recognition** | Compare two face images | Biometric verification |
| **Liveness Detection** | Anti-spoofing check | Prevent fake photos |
| **Blacklist Check** | Credit blacklist status | Risk assessment |
| **Credit Score** | Customer creditworthiness | Loan decisions |
| **Loan Features** | Loan history and behavior | Lending decisions |
| **EaseID Balance** | Check EaseID account balance | Monitoring |

---

## Pricing

All prices are in Nigerian Naira (₦) and charged per successful verification:


| Service | Price | Typical Response Time |
|---------|-------|----------------------|
| Enhanced BVN Verification | ₦100 | 2-5 seconds |
| Enhanced NIN Verification | ₦100 | 2-5 seconds |
| Basic BVN Verification | ₦50 | 1-3 seconds |
| Basic NIN Verification | ₦50 | 1-3 seconds |
| Bank Account Verification | ₦50 | 1-3 seconds |
| Face Recognition | ₦50 | 2-4 seconds |
| Liveness Detection | ₦100 | 3-6 seconds |
| Blacklist Check | ₦50 | 2-4 seconds |
| Credit Score Query | ₦100 | 3-5 seconds |
| Loan Features Query | ₦50 | 2-4 seconds |
| EaseID Balance Check | Free | 1-2 seconds |

### Billing

- Charges are deducted from your PointWave wallet balance
- Only successful verifications are charged
- Failed verifications are NOT charged
- Cached results (duplicate verifications) are NOT charged
- Top up your wallet via bank transfer or card payment

---

## API Endpoints

### 1. Enhanced BVN Verification

Verify BVN and get full customer details including photo.

**Endpoint:** `POST /api/v1/kyc/verify-bvn`

**Request:**
```json
{
  "bvn": "22154883751"
}
```

**Response (Success):**
```json
{
  "status": true,
  "message": "BVN verified successfully",
  "data": {
    "bvn": "22154883751",
    "first_name": "ABUBAKAR",
    "middle_name": "JAMAILU",
    "last_name": "BASHIR",
    "date_of_birth": "1990-01-15",
    "phone": "08012345678",
    "email": "customer@example.com",
    "gender": "Male",
    "address": "123 Main Street, Lagos",
    "state_of_origin": "Lagos",
    "lga_of_origin": "Ikeja",
    "nationality": "Nigerian",
    "marital_status": "Single",
    "registration_date": "2015-06-20",
    "photo": "base64_encoded_image_string"
  },
  "charged": true,
  "charge_amount": 100,
  "transaction_reference": "KYC_ENHANCED_BVN_1708531200_1234"
}
```

**Response (Cached - No Charge):**
```json
{
  "status": true,
  "message": "BVN verified successfully (Cached)",
  "data": { ... },
  "charged": false
}
```

**Response (Insufficient Balance):**
```json
{
  "status": false,
  "message": "Insufficient balance. Required: ₦100.00, Available: ₦50.00"
}
```

---

### 2. Enhanced NIN Verification

Verify NIN and get full customer details including photo.

**Endpoint:** `POST /api/v1/kyc/verify-nin`

**Request:**
```json
{
  "nin": "12345678901"
}
```

**Response:**
```json
{
  "status": true,
  "message": "NIN verified successfully",
  "data": {
    "nin": "12345678901",
    "first_name": "ABUBAKAR",
    "middle_name": "JAMAILU",
    "last_name": "BASHIR",
    "date_of_birth": "1990-01-15",
    "phone": "08012345678",
    "gender": "Male",
    "address": "123 Main Street, Lagos",
    "state_of_origin": "Lagos",
    "lga_of_origin": "Ikeja",
    "nationality": "Nigerian",
    "photo": "base64_encoded_image_string"
  },
  "charged": true,
  "charge_amount": 100,
  "transaction_reference": "KYC_ENHANCED_NIN_1708531200_5678"
}
```

---

### 3. Bank Account Verification

Verify bank account ownership and get account name.

**Endpoint:** `POST /api/v1/kyc/verify-bank-account`

**Request:**
```json
{
  "account_number": "7040540018",
  "bank_code": "100004"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Bank account verified successfully",
  "data": {
    "account_name": "ABUBAKAR JAMAILU BASHIR",
    "account_number": "7040540018",
    "bank_code": "100004",
    "bank_name": "OPay"
  },
  "charged": true,
  "charge_amount": 50,
  "transaction_reference": "KYC_BANK_ACCOUNT_VERIFICATION_1708531200_9012"
}
```

**Use Case:** Display account name to user before transfer to confirm recipient.

---

### 4. Face Recognition

Compare two face images to verify if they belong to the same person.

**Endpoint:** `POST /api/v1/kyc/face-compare`

**Request:**
```json
{
  "source_image": "base64_encoded_image_1",
  "target_image": "base64_encoded_image_2"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Face comparison completed",
  "data": {
    "similarity_score": 85.5,
    "match": true,
    "confidence": "high"
  },
  "charged": true,
  "charge_amount": 50,
  "transaction_reference": "KYC_FACE_RECOGNITION_1708531200_3456"
}
```

**Note:** Similarity score > 60 indicates same person.

---

### 5. Liveness Detection

Initialize liveness detection to prevent spoofing attacks.

**Endpoint:** `POST /api/v1/kyc/liveness/initialize`

**Request:**
```json
{
  "biz_id": "your_business_reference",
  "redirect_url": "https://yourapp.com/callback",
  "user_id": "customer_123"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Liveness initialized",
  "data": {
    "transaction_id": "LIVE_1708531200_7890",
    "verification_url": "https://easeid.ai/liveness/verify/...",
    "expires_at": "2026-02-21T12:00:00Z"
  },
  "charged": true,
  "charge_amount": 100
}
```

**Query Result:**

**Endpoint:** `POST /api/v1/kyc/liveness/query`

**Request:**
```json
{
  "transaction_id": "LIVE_1708531200_7890"
}
```

**Response:**
```json
{
  "status": true,
  "data": {
    "liveness_passed": true,
    "confidence_score": 95.2,
    "face_image": "base64_encoded_image"
  }
}
```

---

### 6. Blacklist Check

Check if customer is on credit blacklist.

**Endpoint:** `POST /api/v1/kyc/blacklist-check`

**Request:**
```json
{
  "phone_number": "08012345678",
  "bvn": "22154883751",
  "nin": "12345678901"
}
```

**Note:** Provide at least one identifier.

**Response:**
```json
{
  "status": true,
  "message": "Blacklist check completed",
  "data": {
    "is_blacklisted": false,
    "blacklist_reason": null,
    "risk_level": "low"
  },
  "charged": true,
  "charge_amount": 50
}
```

---

### 7. Credit Score Query

Get customer credit score for lending decisions.

**Endpoint:** `POST /api/v1/kyc/credit-score`

**Request:**
```json
{
  "mobile_no": "08012345678",
  "id_number": "22154883751"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Credit score retrieved",
  "data": {
    "credit_score": 720,
    "score_range": "650-850",
    "rating": "Good",
    "factors": {
      "payment_history": "Excellent",
      "credit_utilization": "Good",
      "credit_age": "Fair"
    }
  },
  "charged": true,
  "charge_amount": 100
}
```

---

### 8. Loan Features Query

Get customer loan history and behavior.

**Endpoint:** `POST /api/v1/kyc/loan-features`

**Request:**
```json
{
  "value": "08012345678",
  "type": 1,
  "access_type": "01"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Loan features retrieved",
  "data": {
    "total_loans": 5,
    "active_loans": 1,
    "defaulted_loans": 0,
    "total_borrowed": 500000,
    "total_repaid": 450000,
    "repayment_rate": 90.0
  },
  "charged": true,
  "charge_amount": 50
}
```

---

### 10. Check EaseID Balance

Check your EaseID account balance (for monitoring purposes).

**Endpoint:** `GET /api/v1/kyc/easeid-balance`

**Response:**
```json
{
  "status": true,
  "message": "EaseID balance retrieved successfully",
  "data": {
    "balance": 50000.00,
    "currency": "NGN",
    "formatted_balance": "₦50,000.00",
    "last_updated": "2026-02-21T10:30:00Z"
  }
}
```

**Note:** This shows your EaseID provider balance, not your PointWave wallet balance.

---

### 11. Get Banks List

Get list of all supported banks for account verification.

**Endpoint:** `GET /api/v1/banks`

**Response:**
```json
{
  "status": true,
  "message": "Banks retrieved successfully",
  "data": {
    "banks": [
      {
        "id": 1,
        "name": "Access Bank",
        "code": "000014",
        "slug": "access-bank",
        "active": true
      },
      {
        "id": 2,
        "name": "GTBank",
        "code": "000013",
        "slug": "gtbank",
        "active": true
      }
    ],
    "total": 50
  }
}
```

---

### 12. Get Wallet Balance

Check your PointWave wallet balance.

**Endpoint:** `GET /api/v1/balance`

**Response:**
```json
{
  "status": true,
  "message": "Balance retrieved successfully",
  "data": {
    "balance": 5000.00,
    "currency": "NGN",
    "formatted_balance": "₦5,000.00"
  }
}
```

---

## Code Examples

### PHP (cURL)

```php
<?php

$apiKey = 'YOUR_API_KEY';
$secretKey = 'YOUR_SECRET_KEY';
$businessId = 'YOUR_BUSINESS_ID';

$ch = curl_init('https://app.pointwave.ng/api/v1/kyc/verify-bvn');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'x-api-key: ' . $apiKey,
        'x-business-id: ' . $businessId,
        'Idempotency-Key: ' . uniqid('kyc_', true),
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'bvn' => '22154883751'
    ])
]);

$response = curl_exec($ch);
$result = json_decode($response, true);

if ($result['status']) {
    echo "BVN Verified!\n";
    echo "Name: " . $result['data']['first_name'] . " " . $result['data']['last_name'] . "\n";
    echo "Charged: ₦" . $result['charge_amount'] . "\n";
} else {
    echo "Error: " . $result['message'] . "\n";
}

curl_close($ch);
```

### JavaScript (Node.js)

```javascript
const axios = require('axios');

const apiKey = 'YOUR_API_KEY';
const secretKey = 'YOUR_SECRET_KEY';
const businessId = 'YOUR_BUSINESS_ID';

async function verifyBVN(bvn) {
  try {
    const response = await axios.post(
      'https://app.pointwave.ng/api/v1/kyc/verify-bvn',
      { bvn },
      {
        headers: {
          'Authorization': `Bearer ${secretKey}`,
          'x-api-key': apiKey,
          'x-business-id': businessId,
          'Idempotency-Key': `kyc_${Date.now()}_${Math.random()}`,
          'Content-Type': 'application/json'
        }
      }
    );

    if (response.data.status) {
      console.log('BVN Verified!');
      console.log('Name:', response.data.data.first_name, response.data.data.last_name);
      console.log('Charged: ₦', response.data.charge_amount);
    }
  } catch (error) {
    console.error('Error:', error.response?.data?.message || error.message);
  }
}

verifyBVN('22154883751');
```

### Python

```python
import requests
import uuid

api_key = 'YOUR_API_KEY'
secret_key = 'YOUR_SECRET_KEY'
business_id = 'YOUR_BUSINESS_ID'

headers = {
    'Authorization': f'Bearer {secret_key}',
    'x-api-key': api_key,
    'x-business-id': business_id,
    'Idempotency-Key': f'kyc_{uuid.uuid4()}',
    'Content-Type': 'application/json'
}

data = {
    'bvn': '22154883751'
}

response = requests.post(
    'https://app.pointwave.ng/api/v1/kyc/verify-bvn',
    json=data,
    headers=headers
)

result = response.json()

if result['status']:
    print('BVN Verified!')
    print(f"Name: {result['data']['first_name']} {result['data']['last_name']}")
    print(f"Charged: ₦{result['charge_amount']}")
else:
    print(f"Error: {result['message']}")
```

---

## Error Handling

### Common Error Responses

**Invalid BVN/NIN:**
```json
{
  "status": false,
  "message": "Invalid BVN format. BVN must be 11 digits"
}
```

**Insufficient Balance:**
```json
{
  "status": false,
  "message": "Insufficient balance. Required: ₦100.00, Available: ₦50.00"
}
```

**Authentication Error:**
```json
{
  "status": false,
  "message": "Invalid API credentials"
}
```

**Rate Limit:**
```json
{
  "status": false,
  "message": "Rate limit exceeded. Please try again in 60 seconds"
}
```

**Service Unavailable:**
```json
{
  "status": false,
  "message": "KYC service temporarily unavailable. Please try again"
}
```

### HTTP Status Codes

- `200` - Success
- `400` - Bad Request (validation error)
- `401` - Unauthorized (invalid credentials)
- `403` - Forbidden (insufficient balance)
- `404` - Not Found
- `422` - Unprocessable Entity (validation failed)
- `429` - Too Many Requests (rate limit)
- `500` - Internal Server Error

---

## Testing

### Sandbox Mode

Test all endpoints without charges using sandbox mode.

**Enable Sandbox:**
Add `x-sandbox: true` header to any request.

```bash
curl -X POST https://app.pointwave.ng/api/v1/kyc/verify-bvn \
  -H 'Authorization: Bearer YOUR_SECRET_KEY' \
  -H 'x-api-key: YOUR_API_KEY' \
  -H 'x-business-id: YOUR_BUSINESS_ID' \
  -H 'x-sandbox: true' \
  -H 'Content-Type: application/json' \
  -d '{"bvn":"22154883751"}'
```

**Sandbox Response:**
```json
{
  "status": true,
  "message": "BVN verified successfully (SANDBOX)",
  "data": {
    "bvn": "22154883751",
    "first_name": "TEST",
    "last_name": "USER",
    ...
  },
  "charged": false,
  "sandbox": true
}
```

### Test BVN Numbers

Use these test BVNs in sandbox mode:
- `22154883751` - Valid BVN (returns success)
- `12345678901` - Invalid BVN (returns error)

---

## Best Practices

### 1. Cache Results

Store verification results in your database to avoid duplicate charges:

```php
// Check cache first
$cached = DB::table('kyc_verifications')
    ->where('bvn', $bvn)
    ->where('created_at', '>', now()->subDays(30))
    ->first();

if ($cached) {
    return $cached->data; // Use cached result
}

// Call API only if not cached
$result = verifyBVN($bvn);

// Store in cache
DB::table('kyc_verifications')->insert([
    'bvn' => $bvn,
    'data' => json_encode($result),
    'created_at' => now()
]);
```

### 2. Handle Errors Gracefully

```php
try {
    $result = verifyBVN($bvn);
    
    if (!$result['status']) {
        // Log error
        Log::error('BVN verification failed', [
            'bvn' => substr($bvn, 0, 3) . '****',
            'error' => $result['message']
        ]);
        
        // Show user-friendly message
        return response()->json([
            'error' => 'Unable to verify BVN. Please try again or contact support.'
        ], 400);
    }
    
    return response()->json($result);
    
} catch (Exception $e) {
    Log::error('BVN verification exception', ['error' => $e->getMessage()]);
    return response()->json([
        'error' => 'Service temporarily unavailable'
    ], 500);
}
```

### 3. Use Idempotency Keys

Always use unique idempotency keys to prevent duplicate charges:

```php
$idempotencyKey = 'kyc_' . $userId . '_' . time() . '_' . uniqid();
```

### 4. Monitor Wallet Balance

Check balance before making KYC calls:

```php
$balance = getWalletBalance();

if ($balance < 100) {
    // Alert admin to top up
    sendLowBalanceAlert($balance);
    
    // Show user message
    return 'KYC verification temporarily unavailable';
}
```

### 5. Secure Your Credentials

- Store credentials in environment variables
- Never commit credentials to version control
- Rotate keys regularly
- Use different keys for staging and production

---

## Webhooks (Optional)

Get notified when KYC verifications complete.

### Setup Webhook URL

1. Go to Settings → Webhooks
2. Add your webhook URL: `https://yourapp.com/webhooks/kyc`
3. Select events: `kyc.verification.completed`

### Webhook Payload

```json
{
  "event": "kyc.verification.completed",
  "timestamp": "2026-02-21T10:30:00Z",
  "data": {
    "verification_id": "KYC_ENHANCED_BVN_1708531200_1234",
    "type": "enhanced_bvn",
    "status": "success",
    "bvn": "22154883751",
    "customer_data": {
      "first_name": "ABUBAKAR",
      "last_name": "BASHIR",
      ...
    },
    "charged": true,
    "charge_amount": 100
  }
}
```

### Verify Webhook Signature

```php
$signature = $_SERVER['HTTP_X_POINTWAVE_SIGNATURE'];
$payload = file_get_contents('php://input');

$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

if (!hash_equals($signature, $expectedSignature)) {
    http_response_code(401);
    exit('Invalid signature');
}

// Process webhook
$data = json_decode($payload, true);
```

---

## Rate Limits

- **100 requests per minute** per API key
- **1,000 requests per hour** per API key
- **10,000 requests per day** per API key

Exceeding limits returns `429 Too Many Requests`.

---

## Support

### Documentation
- Full API Docs: https://app.pointwave.ng/docs
- Developer Portal: https://app.pointwave.ng/developers

### Contact
- Email: support@pointwave.ng
- WhatsApp: +234 XXX XXX XXXX
- Live Chat: https://app.pointwave.ng (bottom right)

### Response Times
- Email: Within 24 hours
- WhatsApp: Within 2 hours (business hours)
- Live Chat: Instant (business hours)

### Business Hours
Monday - Friday: 9:00 AM - 6:00 PM WAT  
Saturday: 10:00 AM - 2:00 PM WAT  
Sunday: Closed

---

## Changelog

### Version 1.0 (February 21, 2026)
- ✅ Initial release
- ✅ 11 KYC verification services
- ✅ Enhanced BVN and NIN verification
- ✅ Bank account verification
- ✅ Face recognition and liveness detection
- ✅ Credit score and blacklist check
- ✅ Loan features query
- ✅ EaseID balance monitoring
- ✅ Sandbox mode for testing
- ✅ Webhook support
- ✅ Comprehensive error handling

---

## Legal & Compliance

### Data Protection
- All data is encrypted in transit (TLS 1.3)
- Data is encrypted at rest (AES-256)
- NDPR (Nigeria Data Protection Regulation) compliant
- No data is stored longer than necessary
- Customer data is never shared with third parties

### Terms of Service
- By using this API, you agree to our Terms of Service
- You are responsible for obtaining customer consent
- You must comply with all applicable laws and regulations
- Misuse of the API may result in account suspension

### Privacy Policy
- Read our full privacy policy: https://app.pointwave.ng/privacy
- Contact our DPO: dpo@pointwave.ng

---

**Ready to get started?**

1. Sign up at https://app.pointwave.ng
2. Complete your business KYC
3. Get your API credentials
4. Fund your wallet
5. Start verifying!

**Questions?** Contact us at support@pointwave.ng

---

© 2026 PointWave. All rights reserved.
