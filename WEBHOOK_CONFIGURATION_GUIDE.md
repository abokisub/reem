# Webhook Configuration Guide

## Understanding Webhooks

There are TWO types of webhooks in the PointWave system:

### 1. Incoming Webhooks (Provider → PointWave)
These are webhooks that PointWave receives from payment providers like PalmPay.

**PalmPay Webhook URLs** (configured in PalmPay dashboard):
- `https://app.pointwave.ng/api/webhooks/palmpay`
- `https://app.pointwave.ng/api/v1/webhook/palmpay`

**Purpose**: PalmPay sends notifications to PointWave when:
- Customer deposits money to virtual account
- Transfer completes
- Transfer fails

### 2. Outgoing Webhooks (PointWave → Company)
These are webhooks that PointWave sends to YOUR company when events occur.

**Company Webhook URL** (configured in company settings):
- This is YOUR URL (not a PointWave URL)
- Example: `https://app.kobopoint.com/webhooks/pointwave`
- Example: `https://api.yourcompany.com/webhooks/payment`

**Purpose**: PointWave sends notifications to your company when:
- Customer deposits money to your virtual account
- Transfer completes successfully
- Transfer fails
- Refund is processed

## For Companies (Like KoboPoint)

### What URL Should You Configure?

**WRONG** ❌:
```
https://app.pointwave.ng/api/pointwave/webhook
```
This URL doesn't exist and is incorrect.

**CORRECT** ✅:
```
https://YOUR-DOMAIN.com/webhooks/pointwave
https://api.YOUR-DOMAIN.com/webhooks/payment-gateway
https://YOUR-DOMAIN.com/api/webhooks/deposits
```

You configure YOUR OWN webhook URL where you want to receive notifications from PointWave.

### How to Configure Your Webhook URL

#### Option 1: Via Dashboard

1. Log in to PointWave dashboard
2. Go to **Settings** → **API Configuration**
3. Find **Webhook URL** field
4. Enter your webhook URL: `https://your-domain.com/webhooks/pointwave`
5. Click **Save**

#### Option 2: Via API

```bash
curl -X POST https://app.pointwave.ng/api/secure/company/webhook/update \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "webhook_url": "https://your-domain.com/webhooks/pointwave"
  }'
```

### Webhook Events You'll Receive

PointWave will send POST requests to your webhook URL with these events:

#### 1. Payment Success (Virtual Account Credit)
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
    "account_number": "1234567890",
    "bank_name": "Access Bank"
  },
  "virtual_account": {
    "account_number": "9876543210",
    "account_name": "Your Business Name",
    "bank_name": "PalmPay"
  },
  "status": "success",
  "timestamp": "2026-02-22T10:30:00Z"
}
```

#### 2. Transfer Success
```json
{
  "event": "transfer.success",
  "transaction_id": "TXN_789012",
  "reference": "PWV_OUT_XYZ789",
  "amount": 10000.00,
  "fee": 30.00,
  "recipient": {
    "account_number": "0123456789",
    "account_name": "Jane Smith",
    "bank_name": "GTBank",
    "bank_code": "058"
  },
  "status": "success",
  "timestamp": "2026-02-22T11:00:00Z"
}
```

#### 3. Transfer Failed
```json
{
  "event": "transfer.failed",
  "transaction_id": "TXN_345678",
  "reference": "PWV_OUT_DEF456",
  "amount": 5000.00,
  "error": "Insufficient funds in recipient account",
  "status": "failed",
  "timestamp": "2026-02-22T11:15:00Z"
}
```

### Implementing Your Webhook Endpoint

Here's example code for receiving webhooks:

#### PHP Example
```php
<?php
// File: webhooks/pointwave.php

// Get the webhook payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Verify the webhook (optional but recommended)
$signature = $_SERVER['HTTP_X_POINTWAVE_SIGNATURE'] ?? '';
// Verify signature here

// Log the webhook
file_put_contents('webhook.log', date('Y-m-d H:i:s') . ' - ' . $payload . "\n", FILE_APPEND);

// Process the event
switch ($data['event']) {
    case 'payment.success':
        // Customer deposited money
        $transactionId = $data['transaction_id'];
        $amount = $data['amount'];
        // Update your database, credit customer, etc.
        break;
        
    case 'transfer.success':
        // Transfer completed
        $reference = $data['reference'];
        // Update transaction status in your database
        break;
        
    case 'transfer.failed':
        // Transfer failed
        $error = $data['error'];
        // Handle failure, notify user, etc.
        break;
}

// Return 200 OK to acknowledge receipt
http_response_code(200);
echo json_encode(['status' => 'received']);
?>
```

#### Node.js Example
```javascript
const express = require('express');
const app = express();

app.use(express.json());

app.post('/webhooks/pointwave', (req, res) => {
    const event = req.body;
    
    console.log('Received webhook:', event);
    
    switch (event.event) {
        case 'payment.success':
            // Handle payment
            console.log(`Payment received: ${event.amount}`);
            break;
            
        case 'transfer.success':
            // Handle transfer success
            console.log(`Transfer completed: ${event.reference}`);
            break;
            
        case 'transfer.failed':
            // Handle transfer failure
            console.log(`Transfer failed: ${event.error}`);
            break;
    }
    
    // Acknowledge receipt
    res.status(200).json({ status: 'received' });
});

app.listen(3000, () => {
    console.log('Webhook server running on port 3000');
});
```

### Testing Your Webhook

1. **Use a webhook testing service**:
   - https://webhook.site
   - https://requestbin.com
   - Configure this URL temporarily to see what PointWave sends

2. **Test locally with ngrok**:
   ```bash
   ngrok http 3000
   # Use the ngrok URL as your webhook URL
   ```

3. **Check webhook logs** in PointWave dashboard:
   - Go to **Logs** → **Webhook Logs**
   - See all webhook attempts, responses, and errors

### Webhook Security

1. **Verify webhook signatures** (if provided)
2. **Use HTTPS** for your webhook URL
3. **Validate the payload** before processing
4. **Return 200 OK quickly** (process async if needed)
5. **Implement idempotency** (handle duplicate webhooks)

### Troubleshooting

#### Webhook not received
- Check your webhook URL is correct
- Ensure your server is accessible from the internet
- Check firewall settings
- Verify HTTPS certificate is valid
- Check webhook logs in PointWave dashboard

#### Webhook received but not processing
- Check your server logs
- Verify JSON parsing is working
- Ensure your endpoint returns 200 OK
- Check for timeout issues

## Summary

**For Companies**:
- Configure YOUR OWN webhook URL (not a PointWave URL)
- Example: `https://your-domain.com/webhooks/pointwave`
- Implement an endpoint to receive POST requests
- Process events: payment.success, transfer.success, transfer.failed

**For PointWave (Internal)**:
- PalmPay sends webhooks to: `https://app.pointwave.ng/api/webhooks/palmpay`
- PointWave forwards events to company webhook URLs

## Example for KoboPoint

KoboPoint should configure:
```
Webhook URL: https://app.kobopoint.com/webhooks/pointwave
```

NOT:
```
❌ https://app.pointwave.ng/api/pointwave/webhook (doesn't exist)
```
