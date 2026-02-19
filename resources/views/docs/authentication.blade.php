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
            <h2>Getting Your API Credentials</h2>
            <p>After completing KYC verification, you'll receive your API credentials from the dashboard:</p>

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
                        <td><code>40 characters (hex)</code></td>
                        <td>Identifies your business</td>
                    </tr>
                    <tr>
                        <td><strong>API Key</strong></td>
                        <td><code>40 characters (hex)</code></td>
                        <td>Public key for requests</td>
                    </tr>
                    <tr>
                        <td><strong>Secret Key</strong></td>
                        <td><code>120 characters (hex)</code></td>
                        <td>Private key for authentication</td>
                    </tr>
                </tbody>
            </table>

            <div class="alert warning">
                <strong>⚠️ Security Warning:</strong> Never expose your Secret Key in client-side code, public
                repositories, or logs. Keep it secure on your server.
            </div>
        </section>

        <section class="section">
            <h2>Test vs Live Credentials</h2>
            <p>You have separate credentials for testing and production:</p>

            <h3>Test Mode (Sandbox)</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Use when <code>is_test</code> flag is enabled in your account</li>
                <li>Starts with 2,000,000 NGN balance</li>
                <li>All transactions are simulated</li>
                <li>No real money involved</li>
            </ul>

            <h3>Live Mode (Production)</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Real transactions with actual money</li>
                <li>Requires completed KYC verification</li>
                <li>Account must be activated by admin</li>
            </ul>
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
            <h2>Example: cURL</h2>
            <div class="code-block"><code>curl -X POST https://app.pointwave.ng/api/v1/virtual-accounts \
  -H "Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1f..." \
  -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -H "x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone_number": "08012345678",
    "account_type": "static"
  }'</code></div>
        </section>

        <section class="section">
            <h2>Example: PHP</h2>
            <div class="code-block"><code>&lt;?php

$businessId = '3450968aa027e86e3ff5b0169dc17edd7694a846';
$apiKey = '7db8dbb3991382487a1fc388a05d96a7139d92ba';
$secretKey = 'd8a3151a8993c157c1a4ee5ecda8983107004b1f...';

$ch = curl_init('https://app.pointwave.ng/api/v1/virtual-accounts');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $secretKey",
        "x-business-id: $businessId",
        "x-api-key: $apiKey",
        "Content-Type: application/json",
        "Idempotency-Key: " . uniqid('req_', true)
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone_number' => '08012345678',
        'account_type' => 'static'
    ])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
print_r($data);</code></div>
        </section>

        <section class="section">
            <h2>Example: JavaScript (Node.js)</h2>
            <div class="code-block"><code>const axios = require('axios');
const { v4: uuidv4 } = require('uuid');

const businessId = '3450968aa027e86e3ff5b0169dc17edd7694a846';
const apiKey = '7db8dbb3991382487a1fc388a05d96a7139d92ba';
const secretKey = 'd8a3151a8993c157c1a4ee5ecda8983107004b1f...';

async function createVirtualAccount() {
  try {
    const response = await axios.post(
      'https://app.pointwave.ng/api/v1/virtual-accounts',
      {
        first_name: 'John',
        last_name: 'Doe',
        email: 'john@example.com',
        phone_number: '08012345678',
        account_type: 'static'
      },
      {
        headers: {
          'Authorization': `Bearer ${secretKey}`,
          'x-business-id': businessId,
          'x-api-key': apiKey,
          'Content-Type': 'application/json',
          'Idempotency-Key': uuidv4()
        }
      }
    );
    
    console.log(response.data);
  } catch (error) {
    console.error(error.response.data);
  }
}

createVirtualAccount();</code></div>
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