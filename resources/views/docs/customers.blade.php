<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - PointWave API Documentation</title>
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
        .method.put { background: #fca130; color: white; }
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
            <h1>🔖 Customers</h1>
            <p>Manage your end-users - STEP 1 of integration</p>
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
            <p style="font-size: 1.1rem; margin-bottom: 20px;">Customers represent your end-users in the PointWave system. You MUST create a customer first before creating virtual accounts.</p>

            <div class="alert warning">
                <strong>⚠️ IMPORTANT - Customer-First Flow:</strong> You must create a customer record BEFORE creating virtual accounts. This ensures better data quality, control, and follows traditional banking API patterns.
            </div>

            <h3>Integration Flow</h3>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 15px 0;">
                <ol>
                    <li><strong>Step 1:</strong> Create Customer (this page)</li>
                    <li><strong>Step 2:</strong> Create Virtual Account (requires customer_id from step 1)</li>
                    <li><strong>Step 3:</strong> Setup Webhooks</li>
                    <li><strong>Step 4:</strong> Make Transfers</li>
                </ol>
            </div>
        </section>

        <section class="section">
            <h2>Create Customer</h2>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/v1/customers</code>
            </div>

            <p>Creates a new customer in your PointWave account. This is the first step in the integration flow.</p>

            <h3>Request Headers</h3>
            <table>
                <thead>
                    <tr>
                        <th>Header</th>
                        <th>Value</th>
                        <th>Required</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>Authorization</code></td>
                        <td><code>Bearer {SECRET_KEY}</code></td>
                        <td><span class="badge required">Required</span></td>
                    </tr>
                    <tr>
                        <td><code>x-api-key</code></td>
                        <td><code>{API_KEY}</code></td>
                        <td><span class="badge required">Required</span></td>
                    </tr>
                    <tr>
                        <td><code>x-business-id</code></td>
                        <td><code>{BUSINESS_ID}</code></td>
                        <td><span class="badge required">Required</span></td>
                    </tr>
                    <tr>
                        <td><code>Content-Type</code></td>
                        <td><code>application/json</code></td>
                        <td><span class="badge required">Required</span></td>
                    </tr>
                    <tr>
                        <td><code>Idempotency-Key</code></td>
                        <td><code>{UNIQUE_ID}</code></td>
                        <td><span class="badge required">Required</span></td>
                    </tr>
                </tbody>
            </table>

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
                        <td><code>first_name</code></td>
                        <td>string</td>
                        <td><span class="badge required">Required</span></td>
                        <td>Customer's first name</td>
                    </tr>
                    <tr>
                        <td><code>last_name</code></td>
                        <td>string</td>
                        <td><span class="badge required">Required</span></td>
                        <td>Customer's last name</td>
                    </tr>
                    <tr>
                        <td><code>email</code></td>
                        <td>string</td>
                        <td><span class="badge required">Required</span></td>
                        <td>Customer's email address</td>
                    </tr>
                    <tr>
                        <td><code>phone_number</code></td>
                        <td>string</td>
                        <td><span class="badge required">Required</span></td>
                        <td>Customer's phone (e.g., 08012345678)</td>
                    </tr>
                    <tr>
                        <td><code>id_type</code></td>
                        <td>string</td>
                        <td><span class="badge optional">Optional</span></td>
                        <td><code>bvn</code> or <code>nin</code> for verification</td>
                    </tr>
                    <tr>
                        <td><code>id_number</code></td>
                        <td>string</td>
                        <td><span class="badge optional">Optional</span></td>
                        <td>BVN or NIN number</td>
                    </tr>
                    <tr>
                        <td><code>external_reference</code></td>
                        <td>string</td>
                        <td><span class="badge optional">Optional</span></td>
                        <td>Your unique reference</td>
                    </tr>
                </tbody>
            </table>

            <h3>Example Request (JSON)</h3>
            <div class="code-block"><code>{
  "first_name": "Jamil",
  "last_name": "Abubakar",
  "email": "jamil@example.com",
  "phone_number": "08078889419",
  "id_type": "bvn",
  "id_number": "22490148602",
  "external_reference": "CUST-12345"
}</code></div>

            <h3>Example Request (PHP)</h3>
            <div class="code-block"><code>&lt;?php
$ch = curl_init('https://app.pointwave.ng/api/gateway/customers');

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
        'first_name' => 'Jamil',
        'last_name' => 'Abubakar',
        'email' => 'jamil@example.com',
        'phone_number' => '08078889419',
        'id_type' => 'bvn',
        'id_number' => '22490148602',
        'external_reference' => 'CUST-12345'
    ])
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

// Store customer_id for next step
$customerId = $data['data']['customer_id'];
echo "Customer ID: " . $customerId;
?&gt;</code></div>

            <h3>Example Request (Python/Django)</h3>
            <div class="code-block"><code>import requests
import uuid

headers = {
    'Authorization': f'Bearer {secret_key}',
    'x-api-key': api_key,
    'x-business-id': business_id,
    'Content-Type': 'application/json',
    'Idempotency-Key': str(uuid.uuid4())
}

payload = {
    'first_name': 'Jamil',
    'last_name': 'Abubakar',
    'email': 'jamil@example.com',
    'phone_number': '08078889419',
    'id_type': 'bvn',
    'id_number': '22490148602',
    'external_reference': 'CUST-12345'
}

response = requests.post(
    'https://app.pointwave.ng/api/gateway/customers',
    headers=headers,
    json=payload
)

data = response.json()
customer_id = data['data']['customer_id']
print(f"Customer ID: {customer_id}")</code></div>

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
    first_name: 'Jamil',
    last_name: 'Abubakar',
    email: 'jamil@example.com',
    phone_number: '08078889419',
    id_type: 'bvn',
    id_number: '22490148602',
    external_reference: 'CUST-12345'
};

axios.post('https://app.pointwave.ng/api/gateway/customers', payload, { headers })
    .then(response => {
        const customerId = response.data.data.customer_id;
        console.log(`Customer ID: ${customerId}`);
    })
    .catch(error => {
        console.error(error.response.data);
    });</code></div>

            <h3>✅ Successful Response (201 Created)</h3>
            <div class="code-block"><code>{
  "status": true,
  "request_id": "f01cd2ef-5de9-4a16-a3b2-ed273851bb4a",
  "message": "Customer created successfully",
  "data": {
    "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
    "first_name": "Jamil",
    "last_name": "Abubakar",
    "email": "jamil@example.com",
    "phone_number": "08078889419",
    "verification_status": "verified",
    "created_at": "2026-02-20T10:30:00Z"
  }
}</code></div>

            <h3>❌ Error Response (422 Validation Error)</h3>
            <div class="code-block"><code>{
  "status": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "phone_number": ["The phone number format is invalid."]
  }
}</code></div>

            <div class="alert success">
                <strong>📌 Important:</strong> Store the <code>customer_id</code> returned in the response. You'll need it to create virtual accounts in the next step.
            </div>
        </section>

        <section class="section">
            <h2>Update Customer</h2>
            
            <div class="endpoint">
                <span class="method put">PUT</span>
                <code>/v1/customers/{customer_id}</code>
            </div>

            <p>Update customer information. All fields are optional.</p>

            <h3>Example Request (PHP)</h3>
            <div class="code-block"><code>&lt;?php
$customerId = '1efdfc4845a7327bc9271ff0daafdae551d07524';

$ch = curl_init("https://app.pointwave.ng/api/gateway/customers/{$customerId}");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PUT',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'x-api-key: ' . $apiKey,
        'x-business-id: ' . $businessId,
        'Content-Type: application/json',
        'Idempotency-Key: ' . uniqid('req_', true)
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'first_name' => 'Jamil',
        'last_name' => 'Abubakar Updated',
        'phone_number' => '08078889420'
    ])
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);
?&gt;</code></div>
        </section>

        <section class="section">
            <h2>Get Customer Details</h2>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/v1/customers/{customer_id}</code>
            </div>

            <h3>Example Request (PHP)</h3>
            <div class="code-block"><code>&lt;?php
$customerId = '1efdfc4845a7327bc9271ff0daafdae551d07524';

$ch = curl_init("https://app.pointwave.ng/api/gateway/customers/{$customerId}");

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
?&gt;</code></div>

            <h3>✅ Successful Response (200 OK)</h3>
            <div class="code-block"><code>{
  "status": true,
  "request_id": "abc123...",
  "message": "Customer details retrieved",
  "data": {
    "uuid": "1efdfc4845a7327bc9271ff0daafdae551d07524",
    "first_name": "Jamil",
    "last_name": "Abubakar",
    "email": "jamil@example.com",
    "phone": "08078889419",
    "created_at": "2026-02-17T21:22:00Z"
  }
}</code></div>
        </section>

        <section class="section">
            <h2>Next Steps</h2>
            <p>After creating a customer, proceed to:</p>
            <ol>
                <li><strong><a href="{{ route('docs.virtual-accounts') }}" style="color: #667eea;">Create Virtual Account</a></strong> - Use the customer_id to create a virtual account</li>
                <li><strong><a href="{{ route('docs.webhooks') }}" style="color: #667eea;">Setup Webhooks</a></strong> - Receive payment notifications</li>
                <li><strong><a href="{{ route('docs.transfers') }}" style="color: #667eea;">Make Transfers</a></strong> - Send money to bank accounts</li>
            </ol>
        </section>

        <section class="section">
            <h2>Best Practices</h2>
            <ul>
                <li>✅ Always create customers first before virtual accounts</li>
                <li>✅ Store the customer_id in your database</li>
                <li>✅ Use external_reference to link with your system</li>
                <li>✅ Email and phone must be unique per business</li>
                <li>✅ BVN/NIN verification is optional but recommended for higher limits</li>
                <li>✅ Use Idempotency-Key to prevent duplicate customers</li>
            </ul>
        </section>
    </div>
</body>
</html>
