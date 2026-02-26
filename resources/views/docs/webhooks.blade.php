<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhooks - PointPay API Documentation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }

        header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .nav {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .nav a {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav a:hover {
            background: #764ba2;
        }

        .section {
            background: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .section h3 {
            color: #764ba2;
            margin: 20px 0 10px;
            font-size: 1.3rem;
        }

        .code-block {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 15px 0;
            border-left: 4px solid #667eea;
        }

        .code-block code {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            white-space: pre;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .alert.warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .alert.info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background: #f5f5f5;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1>🔏 Webhooks</h1>
            <p>Receive real-time notifications for payment events</p>
        </div>
    </header>

    <div class="container">
        <nav class="nav">
            <a href="{{ route('docs.index') }}">← Back to Docs</a>
            <a href="{{ route('docs.authentication') }}">Authentication</a>
            <a href="{{ route('docs.virtual-accounts') }}">Virtual Accounts</a>
            <a href="{{ route('docs.transfers') }}">Transfers</a>
        </nav>

        <section class="section">
            <h2>Overview</h2>
            <p>Webhooks allow you to receive real-time notifications when events occur in your PointWave account. Instead of polling our API, we'll send HTTP POST requests to your server when important events happen.</p>

            <h3>Common Use Cases</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>✅ Customer receives payment to virtual account</li>
                <li>✅ Transfer completed successfully</li>
                <li>✅ Transfer failed</li>
                <li>✅ Account balance updated</li>
                <li>✅ KYC status changed (BVN/NIN verification)</li>
                <li>✅ Settlement processed</li>
            </ul>
        </section>

        <section class="section">
            <h2>Setting Up Webhooks</h2>

            <h3>1. Configure Your Webhook URL</h3>
            <p>Set your webhook URL in the dashboard or via API:</p>
            <div class="code-block"><code>POST /company/webhook/update
Content-Type: application/json
Authorization: Bearer YOUR_SECRET_KEY

{
  "webhook_url": "https://yourdomain.com/webhooks/pointpay"
}</code></div>

            <h3>2. Verify Your Endpoint</h3>
            <p>Your webhook endpoint must:</p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Accept POST requests</li>
                <li>Return HTTP 200 status code</li>
                <li>Respond within 10 seconds</li>
                <li>Be publicly accessible (HTTPS recommended)</li>
            </ul>

            <div class="alert warning">
                <strong>⚠️ Important:</strong> Always use HTTPS for webhook URLs in production to ensure data security.
            </div>
        </section>

        <section class="section">
            <h2>Webhook Events</h2>
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Description</th>
                        <th>When It Fires</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>payment.received</code></td>
                        <td>Customer received payment</td>
                        <td>When money is deposited to virtual account</td>
                    </tr>
                    <tr>
                        <td><code>transfer.success</code></td>
                        <td>Transfer completed</td>
                        <td>When outgoing transfer succeeds</td>
                    </tr>
                    <tr>
                        <td><code>transfer.failed</code></td>
                        <td>Transfer failed</td>
                        <td>When outgoing transfer fails</td>
                    </tr>
                    <tr>
                        <td><code>balance.updated</code></td>
                        <td>Balance changed</td>
                        <td>When account balance changes</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Webhook Payload</h2>
            <p>All webhooks include these standard fields:</p>

            <h3>Payment Received Example</h3>
            <div class="code-block"><code>{
  "event": "payment.received",
  "timestamp": "2026-02-17T21:30:00Z",
  "data": {
    "transaction_id": "TXN_ABC123",
    "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
    "virtual_account": {
      "account_number": "6690945661",
      "bank_name": "PointWave MFB"
    },
    "amount": 5000.00,
    "currency": "NGN",
    "sender_name": "John Doe",
    "sender_account": "0123456789",
    "sender_bank": "GTBank",
    "reference": "REF-12345",
    "narration": "Payment for services",
    "status": "successful",
    "created_at": "2026-02-17T21:30:00Z"
  }
}</code></div>

            <h3>Transfer Success Example</h3>
            <div class="code-block"><code>{
  "event": "transfer.success",
  "timestamp": "2026-02-17T21:35:00Z",
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
    "completed_at": "2026-02-17T21:35:00Z"
  }
}</code></div>
        </section>

        <section class="section">
            <h2>Signature Verification</h2>
            <p>Every webhook includes a signature in the <code>X-PointWave-Signature</code> header for security. Always verify this
                signature to ensure the webhook came from PointWave.</p>

            <h3>Verification Process</h3>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li>Get the raw request body (JSON string)</li>
                <li>Get your Secret Key from the dashboard</li>
                <li>Compute HMAC SHA-256 hash of the body using your Secret Key</li>
                <li>Compare with the signature in the header</li>
            </ol>

            <div class="alert info">
                <strong>Note:</strong> PointWave uses your Secret Key (not a separate webhook secret) to sign webhooks. This is the same key you use for API authentication.
            </div>

            <h3>PHP Example</h3>
            <div class="code-block"><code>&lt;?php

// Get your Secret Key from dashboard
$secretKey = 'your_secret_key_here';

// Get raw request body
$payload = file_get_contents('php://input');

// Get signature from header
$receivedSignature = $_SERVER['HTTP_X_POINTWAVE_SIGNATURE'] ?? '';

// Compute expected signature
$expectedSignature = hash_hmac('sha256', $payload, $secretKey);

// Verify signature
if (!hash_equals($expectedSignature, $receivedSignature)) {
    http_response_code(401);
    die('Invalid signature');
}

// Signature is valid, process webhook
$data = json_decode($payload, true);

// Handle the event
switch ($data['event']) {
    case 'payment.received':
        handlePaymentReceived($data['data']);
        break;
    case 'transfer.success':
        handleTransferSuccess($data['data']);
        break;
    case 'transfer.failed':
        handleTransferFailed($data['data']);
        break;
}

// Return 200 OK
http_response_code(200);
echo json_encode(['status' => 'success']);

function handlePaymentReceived($data) {
    // Update customer balance
    // Send notification to customer
    // Log transaction
}

function handleTransferSuccess($data) {
    // Update transaction status
    // Notify user
}

function handleTransferFailed($data) {
    // Refund customer
    // Notify user
}</code></div>

            <h3>Python/Django Example</h3>
            <div class="code-block"><code>import hmac
import hashlib
import json
from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt

@csrf_exempt
def webhook_handler(request):
    # Get raw POST body
    payload = request.body
    
    # Get signature from header
    signature_header = request.META.get('HTTP_X_POINTWAVE_SIGNATURE')
    
    # Your Secret Key from dashboard
    secret_key = 'your_secret_key_here'
    
    # Calculate expected signature
    calculated_signature = hmac.new(
        secret_key.encode(),
        payload,
        hashlib.sha256
    ).hexdigest()
    
    # Verify signature
    if hmac.compare_digest(calculated_signature, signature_header):
        # Signature is valid
        data = json.loads(payload)
        
        # Handle event
        if data['event'] == 'payment.received':
            customer_id = data['data']['customer_id']
            amount = data['data']['amount']
            
            # Your business logic
            credit_customer_wallet(customer_id, amount)
        
        return JsonResponse({'status': 'success'})
    else:
        # Invalid signature
        return JsonResponse(
            {'status': 'error', 'message': 'Invalid signature'},
            status=400
        )</code></div>

            <h3>Node.js Example</h3>
            <div class="code-block"><code>const crypto = require('crypto');
const express = require('express');
const app = express();

app.post('/webhook', express.raw({type: 'application/json'}), (req, res) => {
    // Get raw body
    const payload = req.body;
    
    // Get signature from header
    const signatureHeader = req.headers['x-pointwave-signature'];
    
    // Your Secret Key from dashboard
    const secretKey = 'your_secret_key_here';
    
    // Calculate expected signature
    const calculatedSignature = crypto
        .createHmac('sha256', secretKey)
        .update(payload)
        .digest('hex');
    
    // Verify signature
    if (crypto.timingSafeEqual(
        Buffer.from(calculatedSignature),
        Buffer.from(signatureHeader)
    )) {
        // Signature is valid
        const data = JSON.parse(payload);
        
        // Handle event
        if (data.event === 'payment.received') {
            const customerId = data.data.customer_id;
            const amount = data.data.amount;
            
            // Your business logic
            creditCustomerWallet(customerId, amount);
        }
        
        res.json({status: 'success'});
    } else {
        // Invalid signature
        res.status(400).json({
            status: 'error',
            message: 'Invalid signature'
        });
    }
});

app.listen(3000);</code></div>
        </section>

        <section class="section">
            <h2>Retry Logic</h2>
            <p>If your webhook endpoint fails to respond with HTTP 200, PointWave will retry:</p>

            <table>
                <thead>
                    <tr>
                        <th>Attempt</th>
                        <th>Delay</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1st retry</td>
                        <td>1 minute</td>
                    </tr>
                    <tr>
                        <td>2nd retry</td>
                        <td>5 minutes</td>
                    </tr>
                    <tr>
                        <td>3rd retry</td>
                        <td>15 minutes</td>
                    </tr>
                    <tr>
                        <td>4th retry</td>
                        <td>1 hour</td>
                    </tr>
                    <tr>
                        <td>5th retry</td>
                        <td>6 hours</td>
                    </tr>
                </tbody>
            </table>

            <p style="margin-top: 15px;">After 5 failed attempts, the webhook is marked as failed and you'll need to
                manually retrieve the data via API.</p>
        </section>

        <section class="section">
            <h2>Testing Webhooks</h2>

            <h3>Local Development</h3>
            <p>For local testing, use tools like ngrok to expose your local server:</p>
            <div class="code-block"><code># Install ngrok
npm install -g ngrok

# Expose local port
ngrok http 3000

# Use the ngrok URL as your webhook URL
https://abc123.ngrok.io/webhooks/pointpay</code></div>

            <h3>Manual Testing</h3>
            <p>You can manually trigger test webhooks from the dashboard to verify your integration.</p>
        </section>

        <section class="section">
            <h2>Best Practices</h2>
            <ul style="margin-left: 20px;">
                <li>✅ Always verify webhook signatures</li>
                <li>✅ Return HTTP 200 quickly (process async if needed)</li>
                <li>✅ Implement idempotency (handle duplicate webhooks)</li>
                <li>✅ Log all webhook events for debugging</li>
                <li>✅ Use HTTPS for webhook URLs</li>
                <li>✅ Handle retries gracefully</li>
                <li>❌ Never expose webhook secret in client-side code</li>
                <li>❌ Don't perform long-running tasks in webhook handler</li>
            </ul>
        </section>

        <section class="section">
            <h2>Troubleshooting</h2>

            <h3>Webhook Not Received</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Check webhook URL is correct and publicly accessible</li>
                <li>Verify your server is returning HTTP 200</li>
                <li>Check firewall settings</li>
                <li>Review webhook logs in dashboard</li>
            </ul>

            <h3>Signature Verification Failed</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Ensure you're using the raw request body (not parsed JSON)</li>
                <li>Verify webhook secret is correct</li>
                <li>Check for encoding issues</li>
            </ul>

            <h3>Duplicate Webhooks</h3>
            <p>Implement idempotency using the <code>transaction_id</code> field:</p>
            <div class="code-block"><code>// Check if transaction already processed
if (isTransactionProcessed($data['transaction_id'])) {
    return; // Already handled
}

// Process transaction
processTransaction($data);

// Mark as processed
markTransactionProcessed($data['transaction_id']);</code></div>
        </section>
    </div>
</body>

</html>