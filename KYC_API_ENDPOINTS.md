# PointWave KYC API Endpoints

## Base URL
```
https://app.pointwave.ng/api
```

## Authentication
All KYC endpoints require authentication using your API credentials:

```
Headers:
  Authorization: Bearer {secret_key}
  X-API-Key: {api_key}
  X-Business-ID: {business_id}
  Content-Type: application/json
```

---

## V1 KYC Endpoints (Recommended)

### 1. Verify BVN
**Endpoint:** `POST /v1/kyc/verify-bvn`

**Description:** Verify a Bank Verification Number (BVN) and get customer details

**Request:**
```json
{
  "bvn": "22490148602"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "BVN verified successfully",
  "data": {
    "bvn": "22490148602",
    "first_name": "John",
    "last_name": "Doe",
    "middle_name": "Smith",
    "date_of_birth": "1990-01-15",
    "phone_number": "08012345678",
    "gender": "Male",
    "nationality": "Nigerian",
    "state_of_origin": "Lagos",
    "lga_of_origin": "Ikeja",
    "residential_address": "123 Main Street, Lagos",
    "enrollment_bank": "Access Bank",
    "enrollment_branch": "Ikeja Branch",
    "watch_listed": false
  },
  "charge": 50.00
}
```

**Charges:** ₦50 per verification (deducted from your wallet)

---

### 2. Verify NIN
**Endpoint:** `POST /v1/kyc/verify-nin`

**Description:** Verify a National Identification Number (NIN) and get customer details

**Request:**
```json
{
  "nin": "12345678901"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "NIN verified successfully",
  "data": {
    "nin": "12345678901",
    "first_name": "Jane",
    "last_name": "Doe",
    "middle_name": "Mary",
    "date_of_birth": "1992-05-20",
    "phone_number": "08098765432",
    "gender": "Female",
    "nationality": "Nigerian",
    "state_of_origin": "Abuja",
    "lga_of_origin": "Gwagwalada",
    "residential_address": "456 Federal Road, Abuja",
    "photo": "base64_encoded_photo_string"
  },
  "charge": 50.00
}
```

**Charges:** ₦50 per verification (deducted from your wallet)

---

### 3. Verify Bank Account
**Endpoint:** `POST /v1/kyc/verify-bank-account`

**Description:** Verify a bank account number and get account holder name

**Request:**
```json
{
  "account_number": "0123456789",
  "bank_code": "058"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Account verified successfully",
  "data": {
    "account_number": "0123456789",
    "account_name": "JOHN DOE SMITH",
    "bank_code": "058",
    "bank_name": "GTBank"
  },
  "charge": 10.00
}
```

**Charges:** ₦10 per verification (deducted from your wallet)

---

### 4. Get KYC Status
**Endpoint:** `GET /v1/kyc/status`

**Description:** Get your company's KYC submission status

**Response:**
```json
{
  "status": "success",
  "data": {
    "kyc_status": "verified",
    "sections": {
      "business_info": {
        "status": "approved",
        "submitted_at": "2026-02-15T10:30:00Z",
        "reviewed_at": "2026-02-16T14:20:00Z"
      },
      "bvn_info": {
        "status": "approved",
        "submitted_at": "2026-02-15T10:35:00Z",
        "reviewed_at": "2026-02-16T14:25:00Z"
      },
      "documents": {
        "status": "approved",
        "submitted_at": "2026-02-15T10:40:00Z",
        "reviewed_at": "2026-02-16T14:30:00Z"
      },
      "bank_details": {
        "status": "approved",
        "submitted_at": "2026-02-15T10:45:00Z",
        "reviewed_at": "2026-02-16T14:35:00Z"
      },
      "directors": {
        "status": "approved",
        "submitted_at": "2026-02-15T10:50:00Z",
        "reviewed_at": "2026-02-16T14:40:00Z"
      }
    }
  }
}
```

---

### 5. Submit KYC Section
**Endpoint:** `POST /v1/kyc/submit/{section}`

**Description:** Submit a specific KYC section for review

**Sections:** `business_info`, `bvn_info`, `documents`, `bank_details`, `directors`

**Example - Submit Business Info:**
```json
POST /v1/kyc/submit/business_info

{
  "business_name": "KoboPoint Technologies",
  "business_type": "limited_liability",
  "business_category": "fintech",
  "rc_number": "RC-9058987",
  "business_address": "123 Tech Street, Lagos",
  "business_phone": "08012345678",
  "business_email": "info@kobopoint.com",
  "website": "https://kobopoint.com"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Business information submitted successfully",
  "data": {
    "section": "business_info",
    "status": "pending",
    "submitted_at": "2026-02-22T15:30:00Z"
  }
}
```

---

## Legacy Endpoints (Still Supported)

### Verify BVN (Legacy)
**Endpoint:** `POST /user/verify-bvn`

**Requires:** `auth.token` middleware (Bearer token)

**Request:**
```json
{
  "bvn": "22490148602"
}
```

---

### Verify NIN (Legacy)
**Endpoint:** `POST /user/verify-nin`

**Requires:** `auth.token` middleware (Bearer token)

**Request:**
```json
{
  "nin": "12345678901"
}
```

---

### Verify Bank Account (Legacy)
**Endpoint:** `POST /user/verify-bank`

**Requires:** `auth.token` middleware (Bearer token)

**Request:**
```json
{
  "account_number": "0123456789",
  "bank_code": "058"
}
```

---

## Error Responses

### Insufficient Balance
```json
{
  "status": "error",
  "message": "Insufficient balance. KYC verification costs ₦50.00. Your balance: ₦25.00"
}
```

### Invalid BVN/NIN
```json
{
  "status": "error",
  "message": "Invalid BVN format. BVN must be 11 digits"
}
```

### Verification Failed
```json
{
  "status": "error",
  "message": "BVN verification failed. Please check the BVN and try again",
  "error_code": "VERIFICATION_FAILED"
}
```

### Authentication Error
```json
{
  "status": "error",
  "message": "Unauthorized. Invalid API credentials"
}
```

---

## Pricing

| Service | Cost | Description |
|---------|------|-------------|
| BVN Verification | ₦50 | Verify Bank Verification Number |
| NIN Verification | ₦50 | Verify National Identification Number |
| Bank Account Verification | ₦10 | Verify bank account name |
| Face Comparison | ₦100 | Compare two face images |
| Liveness Check | ₦150 | Verify person is live (not photo) |
| Credit Score | ₦200 | Get credit score report |
| Blacklist Check | ₦50 | Check if phone/BVN/NIN is blacklisted |

**Note:** All charges are automatically deducted from your PointWave wallet balance.

---

## Best Practices

1. **Cache Results:** Store verification results to avoid repeated charges
2. **Handle Errors:** Implement proper error handling for failed verifications
3. **Check Balance:** Ensure sufficient balance before verification
4. **Rate Limiting:** Don't exceed 10 requests per second
5. **Secure Storage:** Never store BVN/NIN in plain text
6. **User Consent:** Always get user consent before verifying their data

---

## Example Implementation (PHP)

```php
<?php

function verifyBVN($bvn) {
    $apiKey = 'your_api_key';
    $secretKey = 'your_secret_key';
    $businessId = 'your_business_id';
    
    $ch = curl_init('https://app.pointwave.ng/api/v1/kyc/verify-bvn');
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['bvn' => $bvn]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secretKey,
            'X-API-Key: ' . $apiKey,
            'X-Business-ID: ' . $businessId,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data;
    } else {
        throw new Exception('BVN verification failed: ' . $response);
    }
}

// Usage
try {
    $result = verifyBVN('22490148602');
    echo "Name: " . $result['data']['first_name'] . " " . $result['data']['last_name'];
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

---

## Support

For questions or issues with KYC endpoints:
- Email: support@pointwave.ng
- Documentation: https://app.pointwave.ng/secure/documentation
- Dashboard: https://app.pointwave.ng

---

**Last Updated:** February 22, 2026
