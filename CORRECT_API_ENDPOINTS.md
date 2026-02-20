# ✅ CORRECT API Endpoints for PointWave

## Base URL
```
https://app.pointwave.ng/api/v1
```

## Authentication
All requests need this header:
```
Authorization: Token YOUR_SECRET_KEY
Content-Type: application/json
```

## Endpoints

### 1. Create Customer
```
POST https://app.pointwave.ng/api/v1/customers
```

**Body:**
```json
{
  "email": "john@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "08012345678",
  "bvn": "22222222222"
}
```

---

### 2. Create Virtual Account
```
POST https://app.pointwave.ng/api/v1/virtual-accounts
```

**Body:**
```json
{
  "customer_id": "cust_abc123",
  "account_name": "John Doe"
}
```

---

### 3. Bank Transfer
```
POST https://app.pointwave.ng/api/v1/transfers
```

**Body:**
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

---

### 4. Get Transactions
```
GET https://app.pointwave.ng/api/v1/transactions
```

---

## Quick Test

```bash
curl -X POST https://app.pointwave.ng/api/v1/customers \
  -H "Authorization: Token YOUR_SECRET_KEY" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","first_name":"Test","last_name":"User","phone":"08012345678","bvn":"22222222222"}'
```

---

## Summary

✅ Base URL: `https://app.pointwave.ng/api/v1`  
✅ All endpoints are under `/api/v1/` prefix  
✅ No IP whitelisting required  
✅ Just need Secret Key from dashboard

