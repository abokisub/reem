# Webhook Configuration & Aggregator Model Explained

## 1. How Companies Should Set Their Webhook

### For KoboPoint (domain: app.kobopoint.com)

KoboPoint needs to create an endpoint on THEIR server to receive notifications from PointWave.

**Recommended webhook URL:**
```
https://app.kobopoint.com/webhooks/pointwave
```

Or any other path they prefer:
```
https://app.kobopoint.com/api/webhooks/payment
https://app.kobopoint.com/api/v1/pointwave/notifications
```

### Important Points:

1. **This is THEIR URL, not ours**
   - They configure where THEY want to receive notifications
   - PointWave will send POST requests to this URL

2. **The endpoint must accept POST requests**
   - KoboPoint had an issue where their endpoint only accepted GET requests
   - This caused 405 errors: "The POST method is not supported"
   - They need: `Route::post('/webhooks/pointwave', [Controller::class, 'handleWebhook']);`

3. **How to configure in PointWave:**
   - Login to PointWave dashboard
   - Go to Settings → API Configuration
   - Enter webhook URL: `https://app.kobopoint.com/webhooks/pointwave`
   - Save

### What PointWave Will Send

When a customer deposits money to KoboPoint's virtual account:

```json
{
  "event": "payment.success",
  "transaction_id": "TXN_123456",
  "reference": "PWV_IN_ABC123",
  "amount": 5000.00,
  "fee": 50.00,
  "net_amount": 4950.00,
  "customer": {
    "name": "John Doe",
    "account_number": "1234567890"
  },
  "virtual_account": {
    "account_number": "9876543210",
    "account_name": "Customer Name"
  },
  "status": "success",
  "timestamp": "2026-02-22T10:30:00Z"
}
```

### Example Implementation (PHP)

```php
<?php
// File: app/Http/Controllers/WebhookController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handlePointWaveWebhook(Request $request)
    {
        // Get the webhook payload
        $payload = $request->all();
        
        // Log for debugging
        Log::info('PointWave Webhook Received', $payload);
        
        // Process based on event type
        switch ($payload['event']) {
            case 'payment.success':
                $this->handlePaymentSuccess($payload);
                break;
                
            case 'transfer.success':
                $this->handleTransferSuccess($payload);
                break;
                
            case 'transfer.failed':
                $this->handleTransferFailed($payload);
                break;
        }
        
        // Return 200 OK to acknowledge receipt
        return response()->json(['status' => 'received'], 200);
    }
    
    private function handlePaymentSuccess($payload)
    {
        // Credit customer account in your database
        $transactionId = $payload['transaction_id'];
        $amount = $payload['net_amount']; // Use net_amount (after fees)
        $customerAccount = $payload['virtual_account']['account_number'];
        
        // Update your database
        // ...
    }
}
```

**Route:**
```php
// routes/api.php
Route::post('/webhooks/pointwave', [WebhookController::class, 'handlePointWaveWebhook']);
```

---

## 2. Aggregator Model Explained

### How It Works

Yes, the system uses an **aggregator model** where each company uses their director's BVN for ALL customer virtual accounts.

### Example: 20 Companies Register

Let's say 20 companies register and admin activates them:

**Company 1: KoboPoint**
- Director: Abubakar
- Director BVN: 22490148602
- Status: Activated

**Company 2: PayFast**
- Director: Chioma
- Director BVN: 11223344556
- Status: Activated

**Company 3: QuickPay**
- Director: Emeka
- Director BVN: 99887766554
- Status: Activated

... and so on for all 20 companies.

### What Happens When Customers Get Virtual Accounts

#### KoboPoint's Customers (Company 1)
- Customer A requests virtual account → Uses BVN 22490148602 (Abubakar's BVN)
- Customer B requests virtual account → Uses BVN 22490148602 (Abubakar's BVN)
- Customer C requests virtual account → Uses BVN 22490148602 (Abubakar's BVN)
- All 1000 customers → All use BVN 22490148602

#### PayFast's Customers (Company 2)
- Customer X requests virtual account → Uses BVN 11223344556 (Chioma's BVN)
- Customer Y requests virtual account → Uses BVN 11223344556 (Chioma's BVN)
- All their customers → All use BVN 11223344556

#### QuickPay's Customers (Company 3)
- All their customers → All use BVN 99887766554 (Emeka's BVN)

### Key Points

1. **Each company uses THEIR OWN director's BVN**
   - Company 1 uses Director 1's BVN for all their customers
   - Company 2 uses Director 2's BVN for all their customers
   - Company 3 uses Director 3's BVN for all their customers

2. **No customer KYC required**
   - Customers don't need to provide their own BVN
   - Customers don't need to provide their own NIN
   - They just provide: name, email, phone
   - Virtual account is created instantly

3. **Optional customer KYC upgrade**
   - If a customer wants to upgrade their account later
   - They can provide their own BVN or NIN
   - System will use customer's BVN instead of director's BVN
   - This is optional, not required

### Priority Order (How System Chooses KYC)

When creating a virtual account, the system checks in this order:

```
1. Customer's BVN (if provided) ✅ Use this
2. Customer's NIN (if provided) ✅ Use this
3. Company Director's BVN ✅ Use this (AGGREGATOR MODEL)
4. Company Director's NIN ✅ Use this
5. Company RC Number ✅ Use this (fallback)
```

### Why This Works

**PalmPay Requirements:**
- PalmPay requires BVN or NIN to create virtual accounts
- Instead of collecting BVN from every customer (slow, friction)
- We use the company director's BVN (fast, no friction)
- PalmPay accepts this because the director is the business owner

**Benefits:**
- ✅ Fast onboarding (no customer KYC needed)
- ✅ Better conversion (customers don't abandon signup)
- ✅ Compliant (director's BVN is verified during company KYC)
- ✅ Scalable (one BVN can create unlimited virtual accounts)

### Example Flow

**Scenario: KoboPoint has 5000 customers**

1. **Company Setup (One Time)**
   - KoboPoint registers on PointWave
   - Submits director's BVN: 22490148602
   - Admin verifies and activates company
   - Master wallet created using director's BVN

2. **Customer Onboarding (Instant)**
   - Customer 1 signs up on KoboPoint app
   - Provides: Name, Email, Phone (NO BVN needed)
   - KoboPoint calls PointWave API: `POST /api/v1/virtual-accounts`
   - PointWave creates virtual account using director's BVN 22490148602
   - Customer gets account number instantly: 1234567890
   - Customer 2, 3, 4... 5000 → Same process, all use director's BVN

3. **Deposits Work Normally**
   - Customer deposits ₦10,000 to their virtual account
   - PalmPay processes payment (sees director's BVN in backend)
   - PointWave receives webhook from PalmPay
   - PointWave credits KoboPoint's wallet
   - PointWave sends webhook to KoboPoint
   - KoboPoint credits customer's balance

### Is This Legal/Compliant?

**Yes!** This is a standard aggregator model used by:
- Paystack (uses merchant BVN for customer virtual accounts)
- Flutterwave (same model)
- Monnify (same model)
- All payment aggregators in Nigeria

The director's BVN represents the business entity. All customer funds flow through the business, so using the director's BVN is compliant with CBN regulations.

---

## 3. Company Daily Limits

### Current Status: UNLIMITED

Companies do NOT have daily limits. The `user_limit` field is set to **999999999** (unlimited).

**Why?**
- Companies are businesses, not individual users
- They need to process unlimited transactions for their customers
- Limits would break their business operations

**What IS Limited:**
- Individual customer transaction limits (if company sets them)
- Company wallet balance (can't spend more than they have)
- PalmPay API rate limits (handled by circuit breaker)

### Settings

When a company registers, the system sets:
```php
'user_limit' => 999999999  // Unlimited
```

This means:
- ✅ No daily transaction limit
- ✅ No monthly transaction limit
- ✅ Only limited by wallet balance

---

## Summary

### Webhook Configuration
- Companies configure THEIR OWN webhook URL
- Example: `https://app.kobopoint.com/webhooks/pointwave`
- Endpoint must accept POST requests
- PointWave sends payment notifications to this URL

### Aggregator Model
- Each company uses their director's BVN for ALL customer virtual accounts
- 20 companies = 20 different director BVNs
- Each company's customers use that company's director BVN
- No customer KYC required (instant onboarding)
- Customers can optionally upgrade with their own BVN later

### Company Limits
- Companies have UNLIMITED transaction limits
- Only limited by wallet balance
- This is intentional for business operations

---

**Date:** February 22, 2026  
**Status:** ✅ All systems working correctly
