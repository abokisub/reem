<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication - PointPay API Documentation</title>
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

        .alert.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
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

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge.required {
            background: #f93e3e;
            color: white;
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1>Authentication</h1>
            <p>Secure your API requests with proper authentication</p>
        </div>
    </header>

    <div class="container">
        <nav class="nav">
            <a href="{{ route('docs.index') }}">← Back to Docs</a>
            <a href="{{ route('docs.customers') }}">Customers</a>
            <a href="{{ route('docs.virtual-accounts') }}">Virtual Accounts</a>
            <a href="{{ route('docs.webhooks') }}">Webhooks</a>
        </nav>

        <section class="section">
            <h2>Overview</h2>
            <p>All API requests to PointWave require authentication using three credentials: Business ID, API Key, and Secret Key. These credentials are provided after your account is approved.</p>

            <div class="alert info">
                <strong>💡 Getting Started:</strong> Sign up at <a href="https://app.pointwave.ng/register" style="color: #667eea;">app.pointwave.ng/register</a>, complete KYC verification, and receive your credentials within 24 hours.
            </div>
        </section>

        <section class="section">
            <h2>API Credentials</h2>
            <p>After approval, you'll receive three credentials from your dashboard:</p>

            <table>
                <thead>
                    <tr>
                        <th>Credential</th>
                        <th>Format</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Business ID</strong></td>
                        <td>40-character hex string</td>
                        <td>Identifies your business account</td>
                    </tr>
                    <tr>
                        <td><strong>API Key</strong></td>
                        <td>40-character hex string</td>
                        <td>Public key for API requests</td>
                    </tr>
                    <tr>
                        <td><strong>Secret Key</strong></td>
                        <td>120-character hex string</td>
                        <td>Private key for authentication (keep secure!)</td>
                    </tr>
                </tbody>
            </table>

            <div class="alert warning">
                <strong>⚠️ Security Warning:</strong> Never expose your Secret Key in client-side code, public repositories, or logs. Store it securely on your server only.
            </div>
        </section>

        <section class="section">
            <h2>Environments</h2>
            <p>PointWave provides separate credentials for testing and production:</p>

            <table>
                <thead>
                    <tr>
                        <th>Environment</th>
                        <th>Base URL</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Sandbox</strong></td>
                        <td><code>https://app.pointwave.ng/api/gateway</code></td>
                        <td>Testing with simulated transactions (₦2M balance)</td>
                    </tr>
                    <tr>
                        <td><strong>Production</strong></td>
                        <td><code>https://app.pointwave.ng/api/gateway</code></td>
                        <td>Live transactions with real money</td>
                    </tr>
                </tbody>
            </table>

            <div class="alert info">
                <strong>💡 Tip:</strong> Use sandbox credentials (test mode) during development. Switch to live credentials when ready for production.
            </div>
        </section>

        <section class="section">
            <h2>Required Headers</h2>
            <p>Every API request must include these headers:</p>

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
                        <td><code>Bearer {secret_key}</code></td>
                        <td><span class="badge required">Required</span></td>
                    </tr>
                    <tr>
                        <td><code>x-business-id</code></td>
                        <td><code>{business_id}</code></td>
                        <td><span class="badge required">Required</span></td>
                    </tr>
                    <tr>
                        <td><code>x-api-key</code></td>
                        <td><code>{api_key}</code></td>
                        <td><span class="badge required">Required</span></td>
                    </tr>
                    <tr>
                        <td><code>Content-Type</code></td>
                        <td><code>application/json</code></td>
                        <td><span class="badge required">Required</span></td>
                    </tr>
                    <tr>
                        <td><code>Idempotency-Key</code></td>
                        <td><code>{unique_string}</code></td>
                        <td>Required for POST/PUT</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Idempotency</h2>
            <p>To prevent duplicate transactions, include an <code>Idempotency-Key</code> header for all write
                operations (POST, PUT).</p>

            <div class="code-block"><code>Idempotency-Key: unique-request-id-12345</code></div>

            <p>If you retry a request with the same idempotency key:</p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Within 24 hours: Returns the original response (no duplicate created)</li>
                <li>After 24 hours: Treated as a new request</li>
            </ul>

            <div class="alert info">
                <strong>💡 Best Practice:</strong> Use a UUID or timestamp-based unique identifier for each request.
            </div>
        </section>

        <section class="section">
            <h2>Example Request (PHP)</h2>
            <div class="code-block"><code>&lt;?php

$businessId = 'your_business_id_here';
$apiKey = 'your_api_key_here';
$secretKey = 'your_secret_key_here';

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
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone_number' => '08012345678'
    ])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
print_r($data);
?&gt;</code></div>
        </section>

        <section class="section">
            <h2>Example Request (Python/Django)</h2>
            <div class="code-block"><code>import requests
import uuid

business_id = 'your_business_id_here'
api_key = 'your_api_key_here'
secret_key = 'your_secret_key_here'

headers = {
    'Authorization': f'Bearer {secret_key}',
    'x-api-key': api_key,
    'x-business-id': business_id,
    'Content-Type': 'application/json',
    'Idempotency-Key': str(uuid.uuid4())
}

payload = {
    'first_name': 'John',
    'last_name': 'Doe',
    'email': 'john@example.com',
    'phone_number': '08012345678'
}

response = requests.post(
    'https://app.pointwave.ng/api/gateway/customers',
    headers=headers,
    json=payload
)

print(response.status_code)
print(response.json())</code></div>
        </section>

        <section class="section">
            <h2>Example Request (Node.js)</h2>
            <div class="code-block"><code>const axios = require('axios');
const { v4: uuidv4 } = require('uuid');

const businessId = 'your_business_id_here';
const apiKey = 'your_api_key_here';
const secretKey = 'your_secret_key_here';

const headers = {
    'Authorization': `Bearer ${secretKey}`,
    'x-api-key': apiKey,
    'x-business-id': businessId,
    'Content-Type': 'application/json',
    'Idempotency-Key': uuidv4()
};

const payload = {
    first_name: 'John',
    last_name: 'Doe',
    email: 'john@example.com',
    phone_number: '08012345678'
};

axios.post('https://app.pointwave.ng/api/gateway/customers', payload, { headers })
    .then(response => {
        console.log(response.status);
        console.log(response.data);
    })
    .catch(error => {
        console.error(error.response.data);
    });</code></div>
        </section>

        <section class="section">
            <h2>Authentication Errors</h2>
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
                        <td><code>401</code></td>
                        <td>Invalid credentials</td>
                        <td>Check your Business ID, API Key, and Secret Key</td>
                    </tr>
                    <tr>
                        <td><code>401</code></td>
                        <td>Missing authentication headers</td>
                        <td>Ensure all required headers are present</td>
                    </tr>
                    <tr>
                        <td><code>403</code></td>
                        <td>Account not activated</td>
                        <td>Complete KYC and wait for admin approval</td>
                    </tr>
                    <tr>
                        <td><code>403</code></td>
                        <td>API access locked</td>
                        <td>Contact support to unlock your account</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Security Best Practices</h2>
            <ul style="margin-left: 20px;">
                <li>✅ Store credentials in environment variables, never in code</li>
                <li>✅ Use HTTPS for all API requests</li>
                <li>✅ Rotate your Secret Key periodically</li>
                <li>✅ Implement IP whitelisting if possible</li>
                <li>✅ Monitor API usage for suspicious activity</li>
                <li>❌ Never commit credentials to version control</li>
                <li>❌ Never expose Secret Key in client-side JavaScript</li>
                <li>❌ Never share credentials via email or chat</li>
            </ul>
        </section>
    </div>
</body>

</html>