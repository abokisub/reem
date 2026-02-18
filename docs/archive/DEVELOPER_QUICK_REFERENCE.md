# PointPay API - Developer Quick Reference

## 🏦 Powered by PalmPay

PointPay uses PalmPay as the unified provider for:
- ✅ Virtual Accounts (Collections)
- ✅ Identity Verification (BVN/NIN KYC)
- ✅ Bank Transfers (Disbursements)

**One integration. Three powerful features.**

---

## Quick Start

### 1. Get API Credentials
Login to dashboard → Settings → Developer API

You'll receive:
- Business ID (40 chars)
- API Key (40 chars)
- Secret Key (120 chars)
- Webhook Secret

### 2. Required Headers
```http
Authorization: Bearer YOUR_SECRET_KEY
x-api-key: YOUR_API_KEY
x-business-id: YOUR_BUSINESS_ID
Content-Type: application/json
Idempotency-Key: unique-request-id
```

### 3. Base URL
```
Production: https://app.pointwave.ng/api/v1
Sandbox: https://app.pointwave.ng/api/v1 (use test credentials)
```

---

## Core Endpoints

### Create Virtual Account (PalmPay)
```http
POST /v1/virtual-accounts
```

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone_number": "08012345678",
  "bvn": "22490148602",
  "account_type": "static"
}
```

**Response:**
```json
{
  "status": true,
  "data": {
    "account_number": "6644694207",
    "account_name": "John Doe",
    "bank_name": "PalmPay",
    "customer_id": "abc123"
  }
}
```

**Features:**
- ✅ Instant PalmPay account creation
- ✅ BVN verification (Tier 1: ₦300K limit)
- ✅ T+1 settlement (next business day at 2:00 AM)

---

### Initiate Transfer (PalmPay)
```http
POST /v1/transfers
```

```json
{
  "amount": 5000,
  "account_number": "0123456789",
  "bank_code": "058",
  "narration": "Payment for services",
  "reference": "YOUR-REF-123"
}
```

**Response:**
```json
{
  "status": true,
  "data": {
    "transaction_id": "TXN_ABC123",
    "reference": "YOUR-REF-123",
    "amount": 5000,
    "fee": 50,
    "status": "success"
  }
}
```

**Features:**
- ✅ Instant transfer via PalmPay network
- ✅ All Nigerian banks supported
- ✅ ₦50 fee per transaction

---

### Get Transactions
```http
GET /v1/transactions?page=1&limit=20
```

**Response:**
```json
{
  "status": true,
  "data": [
    {
      "id": "TXN_123",
      "type": "deposit",
      "amount": 10000,
      "status": "success",
      "created_at": "2026-02-18T10:00:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 100
  }
}
```

---

## Webhooks (PalmPay Events)

### Setup
1. Go to Dashboard → Settings → Webhook Event
2. Enter your webhook URL
3. Save webhook secret

### Verify Signature
```php
$signature = hash_hmac('sha256', $payload, $webhookSecret);
$isValid = hash_equals($signature, $_SERVER['HTTP_X_POINTPAY_SIGNATURE']);
```

### Common Events

#### Payment Success (Deposit to PalmPay Account)
```json
{
  "event": "payment.success",
  "data": {
    "amount": "10000.00",
    "reference": "REF123",
    "account_number": "6644694207",
    "sender_name": "John Doe",
    "sender_account": "0123456789",
    "sender_bank": "GTBank"
  }
}
```

#### Transfer Success (via PalmPay)
```json
{
  "event": "transfer.success",
  "data": {
    "transaction_id": "TXN_ABC123",
    "reference": "YOUR-REF-123",
    "amount": "5000.00",
    "recipient_account": "0123456789",
    "recipient_bank": "Access Bank"
  }
}
```

#### Settlement Processed (T+1)
```json
{
  "event": "settlement.processed",
  "data": {
    "settlement_id": "SETTLE_123",
    "amount": "10000.00",
    "transactions": 5,
    "settled_at": "2026-02-19T02:00:00Z"
  }
}
```

---

## KYC Tiers (PalmPay)

### Tier 1 (BVN)
- **Limit:** ₦300,000 daily
- **Required:** BVN only
- **Verification:** Instant

### Tier 3 (NIN)
- **Limit:** ₦5,000,000 daily
- **Required:** NIN + additional docs
- **Verification:** 24-48 hours

---

## Settlement Schedule (PalmPay)

### Deposits
- **Schedule:** T+1 (Next business day)
- **Time:** 2:00 AM WAT
- **Weekends:** Skipped (settles Monday)
- **Holidays:** Skipped (next business day)

### Example:
- Deposit received: Monday 3:00 PM
- Settlement: Tuesday 2:00 AM
- Available in wallet: Tuesday 2:00 AM

### Transfers
- **Schedule:** Instant
- **No waiting period**

---

## Fees (PalmPay)

| Service | Fee |
|---------|-----|
| Virtual Account Creation | Free |
| Deposits | Free |
| Bank Transfers | ₦50 |
| KYC Verification | Free |

---

## Error Codes

| Code | Message | Solution |
|------|---------|----------|
| 401 | Unauthorized | Check API credentials |
| 400 | Invalid BVN | Verify BVN is correct |
| 402 | Insufficient Balance | Fund your wallet |
| 404 | Customer Not Found | Create customer first |
| 422 | Validation Error | Check request parameters |
| 500 | Server Error | Contact support |

---

## Testing (Sandbox)

### Sandbox Features
- ✅ 2,000,000 NGN balance
- ✅ Resets every 24 hours
- ✅ Use test credentials
- ✅ All transactions simulated

### Test BVN
```
22490148602 (Valid)
12345678901 (Invalid)
```

### Test Bank Accounts
```
0123456789 (Success)
9999999999 (Failure)
```

---

## Code Examples

### PHP
```php
$ch = curl_init('https://app.pointwave.ng/api/v1/virtual-accounts');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $secretKey,
    'x-api-key: ' . $apiKey,
    'x-business-id: ' . $businessId,
    'Content-Type: application/json',
    'Idempotency-Key: ' . uniqid()
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'phone_number' => '08012345678',
    'bvn' => '22490148602'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
```

### JavaScript
```javascript
const response = await fetch('https://app.pointwave.ng/api/v1/virtual-accounts', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${secretKey}`,
    'x-api-key': apiKey,
    'x-business-id': businessId,
    'Content-Type': 'application/json',
    'Idempotency-Key': crypto.randomUUID()
  },
  body: JSON.stringify({
    first_name: 'John',
    last_name: 'Doe',
    email: 'john@example.com',
    phone_number: '08012345678',
    bvn: '22490148602'
  })
});
const data = await response.json();
```

### Python
```python
import requests
import uuid

response = requests.post(
    'https://app.pointwave.ng/api/v1/virtual-accounts',
    headers={
        'Authorization': f'Bearer {secret_key}',
        'x-api-key': api_key,
        'x-business-id': business_id,
        'Content-Type': 'application/json',
        'Idempotency-Key': str(uuid.uuid4())
    },
    json={
        'first_name': 'John',
        'last_name': 'Doe',
        'email': 'john@example.com',
        'phone_number': '08012345678',
        'bvn': '22490148602'
    }
)
data = response.json()
```

---

## Support

- **Email:** support@pointwave.ng
- **Documentation:** https://app.pointwave.ng/docs
- **Dashboard:** https://app.pointwave.ng

---

## Key Takeaways

1. **One Provider:** PalmPay powers everything
2. **Three Services:** Accounts, KYC, Transfers
3. **Simple Integration:** Same API, same patterns
4. **Fast Settlement:** T+1 for deposits, instant for transfers
5. **Competitive Pricing:** ₦50 per transfer
6. **Reliable:** Built on PalmPay's proven infrastructure

---

**Last Updated:** February 18, 2026
**Version:** 1.0.0
