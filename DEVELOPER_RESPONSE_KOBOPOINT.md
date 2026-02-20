# Response to Kobopoint API Integration Issue

**Date:** February 20, 2026  
**To:** Kobopoint Development Team  
**Re:** API Integration Issue - Correct Base URL

---

## Issue Resolution

Thank you for reaching out regarding the API integration issue. We apologize for the confusion in our documentation.

### ✅ CORRECT API BASE URL

```
https://app.pointwave.ng/api/gateway
```

**NOT** `https://app.pointwave.ng/api/v1` (this was incorrect in our documentation)

---

## Updated Endpoint URLs

Here are the correct endpoint URLs for your integration:

### 1. Wallet Balance
```
GET https://app.pointwave.ng/api/gateway/balance
```

### 2. Banks List
```
GET https://app.pointwave.ng/api/gateway/banks
```

### 3. Verify Bank Account
```
POST https://app.pointwave.ng/api/gateway/banks/verify
```

### 4. Create Virtual Account
```
POST https://app.pointwave.ng/api/gateway/virtual-accounts
```

### 5. Initiate Transfer
```
POST https://app.pointwave.ng/api/gateway/transfers
```

### 6. Get Transactions
```
GET https://app.pointwave.ng/api/gateway/transactions
```

### 7. Webhook Configuration
```
POST https://app.pointwave.ng/api/company/webhook/update
```

---

## Authentication Headers (Correct)

Your authentication headers are correct:

```
Authorization: Bearer {SECRET_KEY}
x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846
x-api-key: {API_KEY}
Content-Type: application/json
Accept: application/json
```

---

## Account Status

Your account with Business ID `3450968aa027e86e3ff5b0169dc17edd7694a846` is **ACTIVE** and ready for API access.

- ✅ Account Activated
- ✅ API Keys Generated
- ✅ Ready for Production Use

---

## Complete Working Examples

### Example 1: Get Wallet Balance

```bash
curl -X GET "https://app.pointwave.ng/api/gateway/balance" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": true,
  "balance": {
    "available": "150000.00",
    "pending": "25000.00",
    "currency": "NGN"
  }
}
```

### Example 2: Get Banks List

```bash
curl -X GET "https://app.pointwave.ng/api/gateway/banks" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "status": true,
  "banks": [
    {
      "code": "100033",
      "name": "PalmPay"
    },
    {
      "code": "000001",
      "name": "Sterling Bank"
    }
  ]
}
```

### Example 3: Create Virtual Account

```bash
curl -X POST "https://app.pointwave.ng/api/gateway/virtual-accounts" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@kobopoint.com",
    "phone_number": "08012345678",
    "account_type": "static",
    "id_type": "bvn",
    "id_number": "22222222222",
    "external_reference": "KOBO-CUST-001",
    "bank_codes": ["100033"]
  }'
```

**Response:**
```json
{
  "status": true,
  "request_id": "f01cd2ef-5de9-4a16-a3b2-ed273851bb4a",
  "customer": {
    "id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@kobopoint.com",
    "phone_number": "08012345678"
  },
  "virtual_account": {
    "account_number": "8012345678",
    "account_name": "John Doe",
    "bank_name": "PalmPay",
    "bank_code": "100033"
  }
}
```

### Example 4: Initiate Transfer

```bash
curl -X POST "https://app.pointwave.ng/api/gateway/transfers" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Idempotency-Key: KOBO-TXN-$(date +%s)" \
  -d '{
    "amount": 5000,
    "bank_code": "000001",
    "account_number": "0123456789",
    "account_name": "Jane Smith",
    "narration": "Payment for services",
    "reference": "KOBO-PAY-001"
  }'
```

**Response:**
```json
{
  "status": true,
  "message": "Transfer initiated successfully",
  "transaction": {
    "id": "TXN_ABC123",
    "reference": "KOBO-PAY-001",
    "amount": 5000,
    "fee": 50,
    "total": 5050,
    "status": "pending",
    "recipient": {
      "account_number": "0123456789",
      "account_name": "Jane Smith",
      "bank_name": "Sterling Bank"
    }
  }
}
```

---

## Laravel/Guzzle Integration Example

```php
<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PointWaveService
{
    private $client;
    private $baseUrl = 'https://app.pointwave.ng/api/gateway';
    private $secretKey;
    private $businessId;
    private $apiKey;

    public function __construct()
    {
        $this->secretKey = config('services.pointwave.secret_key');
        $this->businessId = config('services.pointwave.business_id');
        $this->apiKey = config('services.pointwave.api_key');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'x-business-id' => $this->businessId,
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    /**
     * Get wallet balance
     */
    public function getBalance()
    {
        try {
            $response = $this->client->get('/balance');
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get banks list
     */
    public function getBanks()
    {
        try {
            $response = $this->client->get('/banks');
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify bank account
     */
    public function verifyAccount($accountNumber, $bankCode)
    {
        try {
            $response = $this->client->post('/banks/verify', [
                'json' => [
                    'account_number' => $accountNumber,
                    'bank_code' => $bankCode
                ]
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create virtual account
     */
    public function createVirtualAccount($data)
    {
        try {
            $response = $this->client->post('/virtual-accounts', [
                'json' => $data
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Initiate transfer
     */
    public function initiateTransfer($data)
    {
        try {
            $response = $this->client->post('/transfers', [
                'json' => $data,
                'headers' => [
                    'Idempotency-Key' => 'KOBO-' . uniqid() . '-' . time()
                ]
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
```

**Config file (config/services.php):**
```php
'pointwave' => [
    'secret_key' => env('POINTWAVE_SECRET_KEY'),
    'business_id' => env('POINTWAVE_BUSINESS_ID', '3450968aa027e86e3ff5b0169dc17edd7694a846'),
    'api_key' => env('POINTWAVE_API_KEY'),
],
```

**.env file:**
```
POINTWAVE_SECRET_KEY=your_secret_key_here
POINTWAVE_BUSINESS_ID=3450968aa027e86e3ff5b0169dc17edd7694a846
POINTWAVE_API_KEY=your_api_key_here
```

---

## Webhook Configuration

To receive real-time notifications for deposits and transfers:

```bash
curl -X POST "https://app.pointwave.ng/api/company/webhook/update" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "webhook_url": "https://kobopoint.com/webhooks/pointwave"
  }'
```

### Webhook Events

Your webhook endpoint will receive these events:

1. **payment.received** - When a customer deposits to their virtual account
2. **transfer.success** - When a transfer completes successfully
3. **transfer.failed** - When a transfer fails

### Webhook Signature Verification (PHP)

```php
public function verifyWebhookSignature($payload, $signature)
{
    $secretKey = config('services.pointwave.secret_key');
    $computedSignature = hash_hmac('sha256', $payload, $secretKey);
    
    return hash_equals($computedSignature, $signature);
}

public function handleWebhook(Request $request)
{
    $payload = $request->getContent();
    $signature = $request->header('X-Pointwave-Signature');
    
    if (!$this->verifyWebhookSignature($payload, $signature)) {
        return response()->json(['error' => 'Invalid signature'], 401);
    }
    
    $data = json_decode($payload, true);
    
    switch ($data['event']) {
        case 'payment.received':
            // Handle deposit
            break;
        case 'transfer.success':
            // Handle successful transfer
            break;
        case 'transfer.failed':
            // Handle failed transfer
            break;
    }
    
    return response()->json(['status' => 'success']);
}
```

---

## Updated Documentation

We have updated our documentation at https://app.pointwave.ng/docs with the correct base URL.

---

## Rate Limits

- **60 requests per minute** per API key
- Exceeding this limit returns HTTP 429 (Too Many Requests)

---

## Fees & Limits

| Service | Fee | Limit |
|---------|-----|-------|
| Virtual Account Creation | FREE | Unlimited |
| Deposits (Incoming) | FREE | No limit |
| Transfers (Outgoing) | ₦50 per transfer | Min: ₦100, Max: ₦5,000,000 |
| Balance Inquiry | FREE | Unlimited |

---

## Settlement Schedule (T+1)

- **Monday-Thursday transactions** → Settled next day at 3:00 AM
- **Friday-Sunday transactions** → Settled Monday at 3:00 AM
- **Public holidays** → Settled next business day at 3:00 AM

---

## Support

If you encounter any issues:

- **Email:** support@pointwave.ng
- **Response Time:** Within 24 hours
- **Emergency:** Contact your account manager

---

## Next Steps

1. ✅ Update your base URL to `https://app.pointwave.ng/api/gateway`
2. ✅ Test the endpoints with the examples above
3. ✅ Configure your webhook URL
4. ✅ Implement webhook signature verification
5. ✅ Go live!

---

**We apologize for the documentation error and any inconvenience caused. Your integration should now work perfectly with the correct base URL.**

Best regards,  
PointWave Technical Team
