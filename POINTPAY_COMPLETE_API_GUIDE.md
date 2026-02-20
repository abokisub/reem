# PointPay API - Complete Integration Guide

**Base URL:** `https://app.pointwave.ng/api/v1`  
**Provider:** PalmPay (All services)  
**Documentation:** https://app.pointwave.ng/docs

---

## 🔐 Authentication

All requests require these headers:

```
Authorization: Bearer YOUR_SECRET_KEY
x-business-id: YOUR_BUSINESS_ID
x-api-key: YOUR_API_KEY
Content-Type: application/json
Idempotency-Key: unique-request-id (for POST/PUT)
```

---

## 📋 Complete API Endpoints

### 1. CREATE VIRTUAL ACCOUNT (Most Important)

**Endpoint:** `POST /v1/virtual-accounts`

**Purpose:** Create a PalmPay virtual account for a customer to receive payments

**Request:**
```json
{
  "first_name": "Jamil",
  "last_name": "Abubakar",
  "email": "jamil@example.com",
  "phone_number": "08078889419",
  "account_type": "static",
  "id_type": "bvn",
  "id_number": "22222222222",
  "external_reference": "customer-12345",
  "bank_codes": ["100033"]
}
```

**Response (201 Created):**
```json
{
  "status": true,
  "request_id": "f01cd2ef-5de9-4a16-a3b2-ed273851bb4a",
  "message": "Virtual accounts created successfully",
  "data": {
    "customer": {
      "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
      "name": "Jamil Abubakar",
      "email": "jamil@example.com"
    },
    "virtual_accounts": [
      {
        "bank_code": "100033",
        "bank_name": "PalmPay",
        "account_number": "6690945661",
        "account_name": "YourBusiness-Jamil Abubakar(PointWave)",
        "account_type": "static",
        "virtual_account_id": "PWV_VA_71B1A38C2F"
      }
    ]
  }
}
```

**Parameters:**
- `first_name` (required): Customer's first name
- `last_name` (required): Customer's last name
- `email` (required): Customer's email
- `phone_number` (required): Nigerian phone number (e.g., 08012345678)
- `account_type` (required): "static" or "dynamic"
- `id_type` (optional): "bvn" or "nin" for KYC
- `id_number` (optional): BVN or NIN number
- `external_reference` (optional): Your unique reference
- `bank_codes` (optional): Array of bank codes, default ["100033"]

---

### 2. INITIATE BANK TRANSFER

**Endpoint:** `POST /v1/transfers`

**Purpose:** Send money to any Nigerian bank account via PalmPay

**Request:**
```json
{
  "amount": 5000,
  "account_number": "0123456789",
  "bank_code": "058",
  "narration": "Payment for services",
  "reference": "YOUR-UNIQUE-REF-123"
}
```

**Response (201 Created):**
```json
{
  "status": true,
  "request_id": "abc123...",
  "message": "Transfer initiated successfully",
  "data": {
    "transaction_id": "TXN_ABC123",
    "reference": "YOUR-UNIQUE-REF-123",
    "amount": 5000,
    "fee": 50,
    "net_amount": 4950,
    "recipient": {
      "account_number": "0123456789",
      "account_name": "JOHN DOE",
      "bank_name": "GTBank"
    },
    "status": "pending",
    "created_at": "2026-02-20T10:30:00Z"
  }
}
```

**Parameters:**
- `amount` (required): Amount in Naira (minimum: 100)
- `account_number` (required): 10-digit account number
- `bank_code` (required): 3 or 6-digit bank code (see banks list)
- `narration` (required): Transfer description (max 100 chars)
- `reference` (optional): Your unique reference

**Fee:** ₦50 per transfer

---

### 3. GET TRANSACTIONS

**Endpoint:** `GET /v1/transactions`

**Purpose:** Get all transactions (deposits, transfers, etc.)

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `limit` (optional): Items per page (default: 50, max: 100)
- `type` (optional): Filter by type (deposit, transfer, withdrawal)
- `status` (optional): Filter by status (successful, pending, failed)
- `start_date` (optional): Start date (YYYY-MM-DD)
- `end_date` (optional): End date (YYYY-MM-DD)

**Request:**
```
GET /v1/transactions?page=1&limit=50&type=deposit&status=successful
```

**Response (200 OK):**
```json
{
  "status": true,
  "request_id": "xyz789...",
  "message": "Transactions retrieved successfully",
  "data": {
    "transactions": [
      {
        "transaction_id": "TXN_ABC123",
        "type": "deposit",
        "amount": 10000.00,
        "fee": 0.00,
        "net_amount": 10000.00,
        "status": "successful",
        "reference": "PALMPAY-REF-12345",
        "narration": "Payment received",
        "customer": {
          "customer_id": "1efdfc...",
          "name": "John Doe",
          "email": "john@example.com"
        },
        "created_at": "2026-02-20T10:00:00Z",
        "completed_at": "2026-02-20T10:00:05Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total": 250,
      "total_pages": 5,
      "has_more": true
    }
  }
}
```

---

### 4. GET SINGLE TRANSACTION

**Endpoint:** `GET /v1/transactions/{transaction_id}`

**Purpose:** Get details of a specific transaction

**Request:**
```
GET /v1/transactions/TXN_ABC123
```

**Response (200 OK):**
```json
{
  "status": true,
  "request_id": "def456...",
  "message": "Transaction details retrieved",
  "data": {
    "transaction_id": "TXN_ABC123",
    "type": "transfer",
    "amount": 5000.00,
    "fee": 50.00,
    "net_amount": 4950.00,
    "status": "successful",
    "reference": "YOUR-REF-123",
    "narration": "Payment for services",
    "recipient": {
      "account_number": "0123456789",
      "account_name": "JANE SMITH",
      "bank_name": "Access Bank"
    },
    "provider_reference": "PALMPAY-TXN-789",
    "created_at": "2026-02-20T10:30:00Z",
    "completed_at": "2026-02-20T10:30:15Z"
  }
}
```

---

### 5. GET CUSTOMER DETAILS

**Endpoint:** `GET /v1/customers/{customer_id}`

**Purpose:** Get customer information

**Request:**
```
GET /v1/customers/1efdfc4845a7327bc9271ff0daafdae551d07524
```

**Response (200 OK):**
```json
{
  "status": true,
  "request_id": "ghi789...",
  "message": "Customer details retrieved",
  "data": {
    "uuid": "1efdfc4845a7327bc9271ff0daafdae551d07524",
    "first_name": "Jamil",
    "last_name": "Abubakar",
    "email": "jamil@example.com",
    "phone": "08078889419",
    "virtual_accounts": [
      {
        "account_number": "6690945661",
        "bank_name": "PalmPay",
        "account_type": "static",
        "status": "active"
      }
    ],
    "created_at": "2026-02-17T21:22:00Z"
  }
}
```

---

### 6. UPDATE CUSTOMER

**Endpoint:** `POST /v1/customers/{customer_id}`

**Purpose:** Update customer information

**Request:**
```json
{
  "first_name": "Jamil",
  "last_name": "Abubakar Bashir",
  "phone_number": "08078889420"
}
```

**Response (200 OK):**
```json
{
  "status": true,
  "request_id": "jkl012...",
  "message": "Customer updated successfully",
  "data": {
    "uuid": "1efdfc4845a7327bc9271ff0daafdae551d07524",
    "first_name": "Jamil",
    "last_name": "Abubakar Bashir",
    "email": "jamil@example.com",
    "phone": "08078889420"
  }
}
```

---

### 7. VERIFY BANK ACCOUNT

**Endpoint:** `POST /v1/verify-account`

**Purpose:** Verify bank account name before transfer

**Request:**
```json
{
  "account_number": "0123456789",
  "bank_code": "058"
}
```

**Response (200 OK):**
```json
{
  "status": true,
  "request_id": "mno345...",
  "message": "Account verified successfully",
  "data": {
    "account_number": "0123456789",
    "account_name": "JOHN DOE",
    "bank_name": "GTBank",
    "bank_code": "058"
  }
}
```

---

### 8. GET SUPPORTED BANKS

**Endpoint:** `GET /v1/banks`

**Purpose:** Get list of all supported Nigerian banks

**Request:**
```
GET /v1/banks
```

**Response (200 OK):**
```json
{
  "status": true,
  "request_id": "pqr678...",
  "message": "Banks retrieved successfully",
  "data": {
    "banks": [
      {
        "bank_code": "058",
        "bank_name": "GTBank",
        "status": "active"
      },
      {
        "bank_code": "044",
        "bank_name": "Access Bank",
        "status": "active"
      }
    ],
    "total": 50
  }
}
```

---

### 9. GET WALLET BALANCE

**Endpoint:** `GET /v1/wallet/balance`

**Purpose:** Get your current wallet balance

**Request:**
```
GET /v1/wallet/balance
```

**Response (200 OK):**
```json
{
  "status": true,
  "request_id": "stu901...",
  "message": "Balance retrieved successfully",
  "data": {
    "available_balance": 150000.00,
    "pending_balance": 25000.00,
    "total_balance": 175000.00,
    "currency": "NGN",
    "last_updated": "2026-02-20T10:45:00Z"
  }
}
```

---

### 10. CONFIGURE WEBHOOK URL

**Endpoint:** `POST /company/webhook/update`

**Purpose:** Set your webhook URL to receive payment notifications

**Request:**
```json
{
  "webhook_url": "https://yourdomain.com/webhooks/pointpay"
}
```

**Response (200 OK):**
```json
{
  "status": true,
  "request_id": "vwx234...",
  "message": "Webhook URL updated successfully",
  "data": {
    "webhook_url": "https://yourdomain.com/webhooks/pointpay",
    "webhook_secret": "whsec_abc123...",
    "updated_at": "2026-02-20T10:50:00Z"
  }
}
```

---

## 🔔 WEBHOOK EVENTS

When events occur, PointPay sends POST requests to your webhook URL.

### Payment Received Webhook

```json
{
  "event": "payment.received",
  "timestamp": "2026-02-20T11:00:00Z",
  "data": {
    "transaction_id": "TXN_ABC123",
    "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
    "virtual_account": {
      "account_number": "6690945661",
      "bank_name": "PalmPay"
    },
    "amount": 5000.00,
    "currency": "NGN",
    "sender_name": "John Doe",
    "sender_account": "0123456789",
    "sender_bank": "GTBank",
    "reference": "PALMPAY-REF-12345",
    "narration": "Payment for services",
    "status": "successful",
    "created_at": "2026-02-20T11:00:00Z"
  }
}
```

### Transfer Success Webhook

```json
{
  "event": "transfer.success",
  "timestamp": "2026-02-20T11:05:00Z",
  "data": {
    "transaction_id": "TXN_XYZ789",
    "amount": 10000.00,
    "currency": "NGN",
    "recipient": {
      "account_number": "0123456789",
      "account_name": "Jane Smith",
      "bank_name": "Access Bank"
    },
    "reference": "YOUR-REF-456",
    "narration": "Withdrawal",
    "fee": 50.00,
    "status": "successful",
    "completed_at": "2026-02-20T11:05:00Z"
  }
}
```

### Transfer Failed Webhook

```json
{
  "event": "transfer.failed",
  "timestamp": "2026-02-20T11:10:00Z",
  "data": {
    "transaction_id": "TXN_FAIL123",
    "amount": 5000.00,
    "recipient": {
      "account_number": "0123456789",
      "bank_name": "GTBank"
    },
    "reference": "YOUR-REF-789",
    "status": "failed",
    "error_message": "Insufficient funds",
    "failed_at": "2026-02-20T11:10:00Z"
  }
}
```

### Webhook Signature Verification (PHP)

```php
<?php
$webhookSecret = 'your_webhook_secret_here';
$payload = file_get_contents('php://input');
$receivedSignature = $_SERVER['HTTP_X_POINTPAY_SIGNATURE'] ?? '';

$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

if (!hash_equals($expectedSignature, $receivedSignature)) {
    http_response_code(401);
    die('Invalid signature');
}

$data = json_decode($payload, true);
// Process webhook...
```

---

## 💰 FEES & LIMITS

| Service | Fee | Limit |
|---------|-----|-------|
| Virtual Account Creation | Free | Unlimited |
| Receiving Payments | 0.5% (max ₦500) | No limit |
| Bank Transfer | ₦50 per transfer | Min: ₦100 |
| BVN Verification (Tier 1) | Free | ₦300,000/day |
| NIN Verification (Tier 3) | Free | ₦5,000,000/day |

---

## ⏰ SETTLEMENT SCHEDULE

**T+1 Settlement:** Funds settle next business day at 3:00 AM

| Transaction Day | Settlement Day | Settlement Time |
|----------------|----------------|-----------------|
| Monday | Tuesday | 3:00 AM |
| Tuesday | Wednesday | 3:00 AM |
| Wednesday | Thursday | 3:00 AM |
| Thursday | Friday | 3:00 AM |
| Friday | Monday | 3:00 AM |
| Saturday | Monday | 3:00 AM |
| Sunday | Monday | 3:00 AM |
| Public Holiday | Next Business Day | 3:00 AM |

---

## 🧪 SANDBOX TESTING

**Test Credentials:**
- Use your sandbox API keys from dashboard
- Each sandbox account gets ₦2,000,000 balance
- Balance resets every 24 hours

**Test BVN:** 22222222222  
**Test NIN:** 12345678901  
**Test Phone:** 08012345678

---

## ⚠️ ERROR CODES

| Code | Error | Solution |
|------|-------|----------|
| 401 | Unauthorized | Check API credentials |
| 403 | Forbidden | Account not activated or KYC incomplete |
| 422 | Validation Error | Check request parameters |
| 429 | Too Many Requests | Rate limit exceeded, retry after 60s |
| 500 | Server Error | Contact support |

---

## 📞 SUPPORT

- **Email:** support@pointwave.ng
- **Documentation:** https://app.pointwave.ng/docs
- **Dashboard:** https://app.pointwave.ng

---

## ✅ QUICK START CHECKLIST

1. ✅ Sign up at https://app.pointwave.ng
2. ✅ Complete KYC verification
3. ✅ Wait for admin approval (24 hours)
4. ✅ Get API credentials from dashboard
5. ✅ Test in sandbox mode
6. ✅ Create virtual account for customer
7. ✅ Set up webhook URL
8. ✅ Go live!

