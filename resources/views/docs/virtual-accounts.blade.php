<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Accounts - PointPay API Documentation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; line-height: 1.6; color: #333; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 0; margin-bottom: 40px; }
        header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .nav { display: flex; gap: 20px; margin-bottom: 40px; flex-wrap: wrap; }
        .nav a { padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; transition: background 0.3s; }
        .nav a:hover { background: #764ba2; }
        .section { background: white; padding: 30px; margin-bottom: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section h2 { color: #667eea; margin-bottom: 20px; font-size: 1.8rem; }
        .section h3 { color: #764ba2; margin: 20px 0 10px; font-size: 1.3rem; }
        .code-block { background: #f5f5f5; padding: 20px; border-radius: 5px; overflow-x: auto; margin: 15px 0; border-left: 4px solid #667eea; }
        .code-block code { font-family: 'Courier New', monospace; font-size: 0.9rem; white-space: pre; }
        .endpoint { background: #e8f4f8; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .method { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; margin-right: 10px; }
        .method.post { background: #49cc90; color: white; }
        .method.get { background: #61affe; color: white; }
        .alert { padding: 15px; border-radius: 5px; margin: 15px 0; }
        .alert.info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        .alert.success { background: #d4edda; border-left: 4px solid #28a745; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f5f5f5; font-weight: 600; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 0.85rem; font-weight: 600; }
        .badge.required { background: #f93e3e; color: white; }
        .badge.optional { background: #fca130; color: white; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>🏘️ Virtual Accounts</h1>
            <p>Create virtual bank accounts for your customers - STEP 2 of integration</p>
        </div>
    </header>

    <div class="container">
        <nav class="nav">
            <a href="{{ route('docs.index') }}">← Back to Docs</a>
            <a href="{{ route('docs.authentication') }}">Authentication</a>
            <a href="{{ route('docs.transfers') }}">Transfers</a>
            <a href="{{ route('docs.webhooks') }}">Webhooks</a>
        </nav>

        <section class="section">
            <h2>Overview</h2>
            <p style="font-size: 1.1rem; margin-bottom: 20px;">Virtual accounts allow your customers to receive payments via bank transfer. Each customer gets a unique account number that automatically credits their balance when they receive payments.</p>

            <div class="alert warning">
                <strong>⚠️ PREREQUISITE:</strong> You must create a customer first using <code>/v1/customers</code> endpoint. You'll need the <code>customer_id</code> to create a virtual account.
            </div>

            <h3>Key Features</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>✅ Instant account creation</li>
                <li>✅ Real-time payment notifications via webhooks</li>
                <li>✅ Configurable settlement schedule</li>
                <li>✅ BVN/NIN verification for KYC compliance</li>
                <li>✅ Static and dynamic accounts</li>
                <li>✅ Multiple bank support</li>
            </ul>
        </section>

        <section class="section">
            <h2>Create Virtual Account</h2>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/v1/virtual-accounts</code>
            </div>

            <p>Creates a virtual account for an existing customer. The customer can receive payments to this account.</p>

            <div class="alert warning">
                <strong>⚠️ REQUIRED:</strong> You must have a <code>customer_id</code> from creating a customer first. See <a href="{{ route('docs.customers') }}" style="color: #e65100;">Customers Documentation</a>.
            </div>

            <h3>Request Parameters</h3>
            <table>
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>customer_id</code></td>
                        <td>string</td>
                        <td><span class="badge required">Required</span></td>
                        <td>Customer ID from step 1</td>
                    </tr>
                    <tr>
                        <td><code>account_type</code></td>
                        <td>string</td>
                        <td><span class="badge required">Required</span></td>
                        <td>Either <code>static</code> or <code>dynamic</code></td>
                    </tr>
                    <tr>
                        <td><code>amount</code></td>
                        <td>number</td>
                        <td>Required if dynamic</td>
                        <td>Expected amount for dynamic accounts</td>
                    </tr>
                    <tr>
                        <td><code>bank_codes</code></td>
                        <td>array</td>
                        <td><span class="badge optional">Optional</span></td>
                        <td>Array of bank codes (default: ["999129"])</td>
                    </tr>
                </tbody>
            </table>

            <h3>Account Types</h3>
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Use Case</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>static</code></td>
                        <td>Permanent account, accepts any amount</td>
                        <td>Wallets, recurring payments, general use</td>
                    </tr>
                    <tr>
                        <td><code>dynamic</code></td>
                        <td>Temporary account for specific amount</td>
                        <td>One-time payments, invoices, checkout</td>
                    </tr>
                </tbody>
            </table>

            <h3>Example Request (Static Account)</h3>
            <div class="code-block"><code>{
  "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
  "account_type": "static",
  "bank_codes": ["999129"]
}</code></div>

            <h3>Example Request (Dynamic Account)</h3>
            <div class="code-block"><code>{
  "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
  "account_type": "dynamic",
  "amount": 50000,
  "bank_codes": ["999129"]
}</code></div>

            <h3>Example Request (PHP)</h3>
            <div class="code-block"><code>&lt;?php
// Use customer_id from previous step
$customerId = '1efdfc4845a7327bc9271ff0daafdae551d07524';

$ch = curl_init('https://app.pointwave.ng/api/gateway/virtual-accounts');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'x-api-key: ' . $apiKey,
        'x-business-id: ' . $businessId,
        'Content-Type: application/json',
        'Idempotency-Key: ' . uniqid('req_', true)
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'customer_id' => $customerId,
        'account_type' => 'static',
        'bank_codes' => ['999129']
    ])
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

// Store account details
$accountNumber = $data['data']['virtual_accounts'][0]['account_number'];
$accountName = $data['data']['virtual_accounts'][0]['account_name'];

echo "Account Number: " . $accountNumber . "\n";
echo "Account Name: " . $accountName;
?&gt;</code></div>

            <h3>Example Request (Python/Django)</h3>
            <div class="code-block"><code>import requests
import uuid

customer_id = '1efdfc4845a7327bc9271ff0daafdae551d07524'

headers = {
    'Authorization': f'Bearer {secret_key}',
    'x-api-key': api_key,
    'x-business-id': business_id,
    'Content-Type': 'application/json',
    'Idempotency-Key': str(uuid.uuid4())
}

payload = {
    'customer_id': customer_id,
    'account_type': 'static',
    'bank_codes': ['999129']
}

response = requests.post(
    'https://app.pointwave.ng/api/gateway/virtual-accounts',
    headers=headers,
    json=payload
)

data = response.json()
account_number = data['data']['virtual_accounts'][0]['account_number']
print(f"Account Number: {account_number}")</code></div>

            <h3>Example Request (Node.js)</h3>
            <div class="code-block"><code>const axios = require('axios');
const { v4: uuidv4 } = require('uuid');

const customerId = '1efdfc4845a7327bc9271ff0daafdae551d07524';

const headers = {
    'Authorization': `Bearer ${secretKey}`,
    'x-api-key': apiKey,
    'x-business-id': businessId,
    'Content-Type': 'application/json',
    'Idempotency-Key': uuidv4()
};

const payload = {
    customer_id: customerId,
    account_type: 'static',
    bank_codes: ['999129']
};

axios.post('https://app.pointwave.ng/api/gateway/virtual-accounts', payload, { headers })
    .then(response => {
        const accountNumber = response.data.data.virtual_accounts[0].account_number;
        console.log(`Account Number: ${accountNumber}`);
    })
    .catch(error => {
        console.error(error.response.data);
    });</code></div>

            <h3>✅ Successful Response (201 Created)</h3>
            <div class="code-block"><code>{
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
        "bank_code": "999129",
        "bank_name": "PointWave MFB",
        "account_number": "6690945661",
        "account_name": "YourBusiness-Jamil Abubakar",
        "account_type": "static",
        "virtual_account_id": "PWV_VA_71B1A38C2F",
        "status": "active"
      }
    ]
  }
}</code></div>
        </section>

        <section class="section">
            <h2>Supported Banks</h2>
            <table>
                <thead>
                    <tr>
                        <th>Bank Name</th>
                        <th>Bank Code</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PointWave MFB</td>
                        <td><code>999129</code></td>
                        <td>✅ Active (Default)</td>
                    </tr>
                    <tr>
                        <td>Alternative Bank</td>
                        <td><code>090743</code></td>
                        <td>✅ Active</td>
                    </tr>
                </tbody>
            </table>

            <p style="margin-top: 15px;">To create accounts on multiple banks, pass an array of bank codes:</p>
            <div class="code-block"><code>{
  "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
  "account_type": "static",
  "bank_codes": ["999129", "090743"]
}</code></div>
        </section>

        <section class="section">
            <h2>Receiving Payments</h2>
            <p>When a customer receives a payment to their virtual account:</p>

            <ol style="margin-left: 20px; margin-top: 10px;">
                <li>Payment is received by the bank</li>
                <li>Bank sends webhook notification to PointWave</li>
                <li>PointWave processes the payment and credits customer balance</li>
                <li>PointWave sends webhook notification to your system</li>
                <li>Customer balance is updated in real-time</li>
            </ol>

            <div class="alert info">
                <strong>💡 Tip:</strong> Set up webhooks to receive real-time notifications when payments are received. See <a href="{{ route('docs.webhooks') }}">Webhook Documentation</a>.
            </div>
        </section>

        <section class="section">
            <h2>Error Responses</h2>
            <table>
                <thead>
                    <tr>
                        <th>Status Code</th>
                        <th>Error</th>
                        <th>Solution</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>422</code></td>
                        <td>Validation error</td>
                        <td>Check required fields and data formats</td>
                    </tr>
                    <tr>
                        <td><code>500</code></td>
                        <td>Provider error</td>
                        <td>Bank API issue - retry or contact support</td>
                    </tr>
                    <tr>
                        <td><code>401</code></td>
                        <td>Authentication failed</td>
                        <td>Check your API credentials</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Best Practices</h2>
            <ul style="margin-left: 20px;">
                <li>✅ Always create customers first before virtual accounts</li>
                <li>✅ Store the <code>customer_id</code> and <code>virtual_account_id</code> in your database</li>
                <li>✅ Use static accounts for wallets and recurring payments</li>
                <li>✅ Use dynamic accounts for one-time payments and invoices</li>
                <li>✅ Implement webhook handlers to receive payment notifications</li>
                <li>✅ Use Idempotency-Key to prevent duplicate accounts</li>
            </ul>
        </section>
    </div>
</body>
</html>
