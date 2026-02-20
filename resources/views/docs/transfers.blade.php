<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfers - PointWave API Documentation</title>
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
        .code-block { background: #2d2d2d; color: #f8f8f2; padding: 20px; border-radius: 5px; overflow-x: auto; margin: 15px 0; border-left: 4px solid #667eea; font-family: 'Courier New', monospace; font-size: 0.9rem; }
        .code-block code { white-space: pre; }
        .endpoint { background: #f0f4ff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #667eea; }
        .method { display: inline-block; padding: 5px 12px; border-radius: 3px; font-weight: bold; margin-right: 10px; font-size: 0.9rem; }
        .method.post { background: #49cc90; color: white; }
        .method.get { background: #61affe; color: white; }
        .alert { padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid; }
        .alert.info { background: #e3f2fd; border-color: #2196f3; color: #0d47a1; }
        .alert.warning { background: #fff3e0; border-color: #ff9800; color: #e65100; }
        .alert.success { background: #e8f5e9; border-color: #4caf50; color: #1b5e20; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f5f5f5; font-weight: 600; color: #667eea; }
        table tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 0.85rem; font-weight: 600; }
        .badge.required { background: #f44336; color: white; }
        .badge.optional { background: #ff9800; color: white; }
        ol, ul { margin-left: 25px; margin-top: 15px; }
        ol li, ul li { margin-bottom: 10px; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>💷 Transfers</h1>
            <p>Send money to any Nigerian bank account</p>
        </div>
    </header>

    <div class="container">
        <nav class="nav">
            <a href="{{ route('docs.index') }}">← Back to Docs</a>
            <a href="{{ route('docs.authentication') }}">Authentication</a>
            <a href="{{ route('docs.virtual-accounts') }}">Virtual Accounts</a>
            <a href="{{ route('docs.webhooks') }}">Webhooks</a>
        </nav>

        <section class="section">
            <h2>Overview</h2>
            <p style="font-size: 1.1rem; margin-bottom: 20px;">Transfer funds from your PointWave wallet to any Nigerian bank account. Transfers are processed instantly with automatic status updates via webhooks.</p>

            <h3>Key Features</h3>
            <ul>
                <li>✅ Instant transfers to all Nigerian banks</li>
                <li>✅ Bank account verification before transfer</li>
                <li>✅ Real-time status updates via webhooks</li>
                <li>✅ Automatic fee calculation</li>
                <li>✅ Idempotency support to prevent duplicates</li>
            </ul>
        </section>

        <section class="section">
            <h2>Verify Bank Account (Recommended)</h2>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/v1/transfers/verify</code>
            </div>

            <p>Verify bank account details before initiating a transfer. This prevents failed transfers due to incorrect account information.</p>

            <h3>Request Body</h3>
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>bank_code</code></td>
                        <td>string</td>
                        <td><span class="badge required">Required</span></td>
                        <td>Bank code (e.g., "058" for GTBank)</td>
                    </tr>
                    <tr>
                        <td><code>account_number</code></td>
                        <td>string</td>
                        <td><span class="badge required">Required</span></td>
                        <td>10-digit account number</td>
                    </tr>
                </tbody>
            </table>

            <h3>Example Request (JSON)</h3>
            <div class="code-block"><code>{
  "bank_code": "058",
  "account_number": "0123456789"
}</code></div>

            <h3>Example Request (PHP)</h3>
            <div class="code-block"><code>&lt;?php
$ch = curl_init('https://app.pointwave.ng/api/gateway/transfers/verify');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'x-api-key: ' . $apiKey,
        'x-business-id: ' . $businessId,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'bank_code' => '058',
        'account_number' => '0123456789'
    ])
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

if ($data['status']) {
    echo "Account Name: " . $data['data']['account_name'];
    echo "Bank Name: " . $data['data']['bank_name'];
}
?&gt;</code></div>

            <h3>Example Request (Python/Django)</h3>
            <div class="code-block"><code>import requests

headers = {
    'Authorization': f'Bearer {secret_key}',
    'x-api-key': api_key,
    'x-business-id': business_id,
    'Content-Type': 'application/json'
}

payload = {
    'bank_code': '058',
    'account_number': '0123456789'
}

response = requests.post(
    'https://app.pointwave.ng/api/gateway/transfers/verify',
    headers=headers,
    json=payload
)

data = response.json()
if data['status']:
    print(f"Account Name: {data['data']['account_name']}")
    print(f"Bank Name: {data['data']['bank_name']}")</code></div>

            <h3>Example Request (Node.js)</h3>
            <div class="code-block"><code>const axios = require('axios');

const headers = {
    'Authorization': `Bearer ${secretKey}`,
    'x-api-key': apiKey,
    'x-business-id': businessId,
    'Content-Type': 'application/json'
};

const payload = {
    bank_code: '058',
    account_number: '0123456789'
};

axios.post('https://app.pointwave.ng/api/gateway/transfers/verify', payload, { headers })
    .then(response => {
        if (response.data.status) {
            console.log(`Account Name: ${response.data.data.account_name}`);
            console.log(`Bank Name: ${response.data.data.bank_name}`);
        }
    })
    .catch(error => {
        console.error(error.response.data);
    });</code></div>

            <h3>✅ Successful Response</h3>
            <div class="code-block"><code>{
  "status": true,
  "message": "Account verified successfully",
  "data": {
    "account_name": "JOHN DOE",
    "account_number": "0123456789",
    "bank_name": "Guaranty Trust Bank",
    "bank_code": "058"
  }
}</code></div>
        </section>

        <section class="section">
            <h2>Initiate Transfer</h2>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/v1/transfers</code>
            </div>

            <p>Send money from your PointWave wallet to any Nigerian bank account.</p>

            <h3>Request Body</h3>
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>amount</code></td>
                        <td>number</td>
                        <td><span class="badge required">Required</span></td>
                        <td>Amount in NGN (minimum: 100)</td>
                    </tr>
                    <tr>
                        <td><code>bank_code</code></td>
                        <td>string</td>
                        <td><span class="badge required">Required</span></td>
                        <td>Destination bank code</td>
                    </tr>
                    <tr>
                        <td><code>account_number</code></td>
                        <td>string</td>
                        <td><span class="badge required">Required</span></td>
                        <td>10-digit account number</td>
                    </tr>
                    <tr>
                        <td><code>account_name</code></td>
                        <td>string</td>
                        <td><span class="badge required">Required</span></td>
                        <td>Account holder name</td>
                    </tr>
                    <tr>
                        <td><code>narration</code></td>
                        <td>string</td>
                        <td><span class="badge optional">Optional</span></td>
                        <td>Transfer description</td>
                    </tr>
                    <tr>
                        <td><code>reference</code></td>
                        <td>string</td>
                        <td><span class="badge optional">Optional</span></td>
                        <td>Your unique reference</td>
                    </tr>
                </tbody>
            </table>

            <h3>Example Request (JSON)</h3>
            <div class="code-block"><code>{
  "amount": 25000,
  "bank_code": "058",
  "account_number": "0123456789",
  "account_name": "JOHN DOE",
  "narration": "Wallet withdrawal",
  "reference": "TXN-12345"
}</code></div>

            <h3>Example Request (PHP)</h3>
            <div class="code-block"><code>&lt;?php
$ch = curl_init('https://app.pointwave.ng/api/gateway/transfers');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'x-api-key: ' . $apiKey,
        'x-business-id: ' . $businessId,
        'Content-Type: application/json',
        'Idempotency-Key: ' . uniqid('txn_', true)
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'amount' => 25000,
        'bank_code' => '058',
        'account_number' => '0123456789',
        'account_name' => 'JOHN DOE',
        'narration' => 'Wallet withdrawal',
        'reference' => 'TXN-' . time()
    ])
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

if ($data['status']) {
    echo "Transfer Reference: " . $data['data']['reference'];
    echo "Status: " . $data['data']['status'];
}
?&gt;</code></div>

            <h3>Example Request (Python/Django)</h3>
            <div class="code-block"><code>import requests
import uuid
import time

headers = {
    'Authorization': f'Bearer {secret_key}',
    'x-api-key': api_key,
    'x-business-id': business_id,
    'Content-Type': 'application/json',
    'Idempotency-Key': str(uuid.uuid4())
}

payload = {
    'amount': 25000,
    'bank_code': '058',
    'account_number': '0123456789',
    'account_name': 'JOHN DOE',
    'narration': 'Wallet withdrawal',
    'reference': f'TXN-{int(time.time())}'
}

response = requests.post(
    'https://app.pointwave.ng/api/gateway/transfers',
    headers=headers,
    json=payload
)

data = response.json()
if data['status']:
    print(f"Transfer Reference: {data['data']['reference']}")
    print(f"Status: {data['data']['status']}")</code></div>

            <h3>Example Request (Node.js)</h3>
            <div class="code-block"><code>const axios = require('axios');
const { v4: uuidv4 } = require('uuid');

const headers = {
    'Authorization': `Bearer ${secretKey}`,
    'x-api-key': apiKey,
    'x-business-id': businessId,
    'Content-Type': 'application/json',
    'Idempotency-Key': uuidv4()
};

const payload = {
    amount: 25000,
    bank_code: '058',
    account_number: '0123456789',
    account_name: 'JOHN DOE',
    narration: 'Wallet withdrawal',
    reference: `TXN-${Date.now()}`
};

axios.post('https://app.pointwave.ng/api/gateway/transfers', payload, { headers })
    .then(response => {
        if (response.data.status) {
            console.log(`Transfer Reference: ${response.data.data.reference}`);
            console.log(`Status: ${response.data.data.status}`);
        }
    })
    .catch(error => {
        console.error(error.response.data);
    });</code></div>

            <h3>✅ Successful Response (201 Created)</h3>
            <div class="code-block"><code>{
  "status": true,
  "message": "Transfer initiated successfully",
  "data": {
    "transaction_id": "txn_699898366854b34349",
    "reference": "TF_6998983665137",
    "amount": 25000,
    "fee": 15,
    "total_amount": 25015,
    "status": "processing",
    "recipient_account": "0123456789",
    "recipient_name": "JOHN DOE",
    "recipient_bank": "Guaranty Trust Bank",
    "created_at": "2026-02-20T18:21:58Z"
  }
}</code></div>

            <div class="alert info">
                <strong>📌 Transfer Flow:</strong>
                <ol>
                    <li>Verify account details (recommended)</li>
                    <li>Deduct amount + fee from wallet</li>
                    <li>Initiate transfer</li>
                    <li>Receive webhook notification when complete</li>
                    <li>Transfer typically completes in 1-5 minutes</li>
                </ol>
            </div>
        </section>

        <section class="section">
            <h2>Get Supported Banks</h2>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/v1/banks</code>
            </div>

            <p>Retrieve the list of all supported Nigerian banks with their codes.</p>

            <h3>Example Request (PHP)</h3>
            <div class="code-block"><code>&lt;?php
$ch = curl_init('https://app.pointwave.ng/api/gateway/banks');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'x-api-key: ' . $apiKey,
        'x-business-id: ' . $businessId
    ]
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

foreach ($data['data'] as $bank) {
    echo $bank['bank_name'] . " - " . $bank['bank_code'] . "\n";
}
?&gt;</code></div>

            <h3>✅ Successful Response</h3>
            <div class="code-block"><code>{
  "status": true,
  "data": [
    {
      "bank_name": "Access Bank",
      "bank_code": "044"
    },
    {
      "bank_name": "GTBank",
      "bank_code": "058"
    },
    {
      "bank_name": "Zenith Bank",
      "bank_code": "057"
    }
  ]
}</code></div>
        </section>

        <section class="section">
            <h2>Check Transfer Status</h2>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/v1/transactions/{transaction_id}</code>
            </div>

            <p>Check the status of a transfer using the transaction ID.</p>

            <h3>Example Request (PHP)</h3>
            <div class="code-block"><code>&lt;?php
$transactionId = 'txn_699898366854b34349';

$ch = curl_init("https://app.pointwave.ng/api/gateway/transactions/{$transactionId}");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'x-api-key: ' . $apiKey,
        'x-business-id: ' . $businessId
    ]
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

echo "Status: " . $data['data']['status'];
?&gt;</code></div>
        </section>

        <section class="section">
            <h2>Common Banks</h2>
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
                        <td>Access Bank</td>
                        <td><code>044</code></td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>GTBank</td>
                        <td><code>058</code></td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>Zenith Bank</td>
                        <td><code>057</code></td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>First Bank</td>
                        <td><code>011</code></td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>UBA</td>
                        <td><code>033</code></td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>Opay</td>
                        <td><code>999992</code></td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>Kuda Bank</td>
                        <td><code>090267</code></td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>Moniepoint</td>
                        <td><code>50515</code></td>
                        <td>✅ Active</td>
                    </tr>
                </tbody>
            </table>
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
                        <td><code>400</code></td>
                        <td>Insufficient balance</td>
                        <td>Top up your wallet</td>
                    </tr>
                    <tr>
                        <td><code>422</code></td>
                        <td>Invalid account number</td>
                        <td>Verify account details first</td>
                    </tr>
                    <tr>
                        <td><code>422</code></td>
                        <td>Invalid bank code</td>
                        <td>Use correct bank code from /v1/banks</td>
                    </tr>
                    <tr>
                        <td><code>500</code></td>
                        <td>Transfer failed</td>
                        <td>Retry or contact support</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Best Practices</h2>
            <ul>
                <li>✅ Always verify account details before transfer</li>
                <li>✅ Use Idempotency-Key to prevent duplicate transfers</li>
                <li>✅ Store transaction_id for status tracking</li>
                <li>✅ Implement webhook handlers for status updates</li>
                <li>✅ Check wallet balance before initiating transfer</li>
                <li>✅ Use meaningful narration for customer reference</li>
                <li>✅ Handle failed transfers gracefully (refund if needed)</li>
            </ul>
        </section>
    </div>
</body>
</html>
