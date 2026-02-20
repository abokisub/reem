<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sandbox - PointWave API Documentation</title>
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
        .alert { padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid; }
        .alert.success { background: #e8f5e9; border-color: #4caf50; color: #1b5e20; }
        .alert.info { background: #e3f2fd; border-color: #2196f3; color: #0d47a1; }
        .alert.warning { background: #fff3e0; border-color: #ff9800; color: #e65100; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f5f5f5; font-weight: 600; color: #667eea; }
        table tr:hover { background: #f8f9fa; }
        ol, ul { margin-left: 25px; margin-top: 15px; }
        ol li, ul li { margin-bottom: 10px; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>🧪 Sandbox Environment</h1>
            <p>Test your integration safely without real money</p>
        </div>
    </header>

    <div class="container">
        <nav class="nav">
            <a href="{{ route('docs.index') }}">← Back to Docs</a>
            <a href="{{ route('docs.authentication') }}">Authentication</a>
            <a href="{{ route('docs.customers') }}">Customers</a>
            <a href="{{ route('docs.virtual-accounts') }}">Virtual Accounts</a>
        </nav>

        <section class="section">
            <h2>Overview</h2>
            <p style="font-size: 1.1rem; margin-bottom: 20px;">The sandbox environment allows you to test your integration without using real money or affecting production data. All transactions are simulated but behave exactly like production.</p>

            <div class="alert success">
                <strong>✅ Safe Testing:</strong> All transactions in sandbox mode are simulated. No real money is involved, and no actual bank transfers occur.
            </div>

            <h3>Key Benefits</h3>
            <ul>
                <li>✅ Test all API endpoints without risk</li>
                <li>✅ Generous starting balance (₦2,000,000)</li>
                <li>✅ Same API endpoints as production</li>
                <li>✅ Real-time webhook notifications</li>
                <li>✅ Automatic balance reset every 24 hours</li>
                <li>✅ No KYC required for testing</li>
            </ul>
        </section>

        <section class="section">
            <h2>Sandbox Features</h2>
            <table>
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Sandbox</th>
                        <th>Production</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Initial Balance</strong></td>
                        <td>₦2,000,000</td>
                        <td>₦0 (requires funding)</td>
                    </tr>
                    <tr>
                        <td><strong>Balance Reset</strong></td>
                        <td>Every 24 hours</td>
                        <td>Never</td>
                    </tr>
                    <tr>
                        <td><strong>API Endpoint</strong></td>
                        <td>Same as production</td>
                        <td>https://app.pointwave.ng/api/gateway</td>
                    </tr>
                    <tr>
                        <td><strong>Credentials</strong></td>
                        <td>Test credentials</td>
                        <td>Live credentials</td>
                    </tr>
                    <tr>
                        <td><strong>Virtual Accounts</strong></td>
                        <td>Simulated (instant)</td>
                        <td>Real bank accounts</td>
                    </tr>
                    <tr>
                        <td><strong>Transfers</strong></td>
                        <td>Instant success (simulated)</td>
                        <td>Real bank transfers (1-5 min)</td>
                    </tr>
                    <tr>
                        <td><strong>Webhooks</strong></td>
                        <td>Sent normally</td>
                        <td>Sent normally</td>
                    </tr>
                    <tr>
                        <td><strong>KYC Required</strong></td>
                        <td>No</td>
                        <td>Yes</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Getting Started</h2>

            <h3>1. Enable Test Mode</h3>
            <p>In your dashboard, toggle "Test Mode" to ON. This will display your test credentials.</p>

            <h3>2. Get Test Credentials</h3>
            <p>Your test credentials are separate from live credentials and can be found in your dashboard after enabling test mode.</p>

            <div class="alert warning">
                <strong>⚠️ Important:</strong> Test credentials only work in test mode. Live credentials only work in live mode. They are not interchangeable. Never share your credentials publicly.
            </div>

            <h3>3. Make Your First Test Request</h3>
            <div class="code-block"><code>&lt;?php
// Use YOUR test credentials from dashboard
$businessId = 'YOUR_BUSINESS_ID';
$apiKey = 'YOUR_API_KEY';
$secretKey = 'YOUR_SECRET_KEY';

// Create a test customer
$ch = curl_init('https://app.pointwave.ng/api/gateway/customers');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'x-api-key: ' . $apiKey,
        'x-business-id: ' . $businessId,
        'Content-Type: application/json',
        'Idempotency-Key: ' . uniqid('test_', true)
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'test@example.com',
        'phone_number' => '08012345678'
    ])
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

print_r($data);
?&gt;</code></div>
        </section>

        <section class="section">
            <h2>Test Data</h2>

            <h3>Test BVN/NIN Numbers</h3>
            <p>Use these test identity numbers for KYC verification in sandbox:</p>
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Number</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>BVN (Success)</td>
                        <td><code>22222222222</code></td>
                        <td>Verification successful</td>
                    </tr>
                    <tr>
                        <td>BVN (Failed)</td>
                        <td><code>11111111111</code></td>
                        <td>Verification failed</td>
                    </tr>
                    <tr>
                        <td>NIN (Success)</td>
                        <td><code>33333333333</code></td>
                        <td>Verification successful</td>
                    </tr>
                    <tr>
                        <td>NIN (Failed)</td>
                        <td><code>44444444444</code></td>
                        <td>Verification failed</td>
                    </tr>
                </tbody>
            </table>

            <h3>Test Bank Accounts</h3>
            <p>Use these test account numbers for bank verification:</p>
            <table>
                <thead>
                    <tr>
                        <th>Bank Code</th>
                        <th>Account Number</th>
                        <th>Account Name</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>058 (GTBank)</td>
                        <td><code>0123456789</code></td>
                        <td>TEST ACCOUNT</td>
                    </tr>
                    <tr>
                        <td>044 (Access Bank)</td>
                        <td><code>0987654321</code></td>
                        <td>DEMO USER</td>
                    </tr>
                    <tr>
                        <td>057 (Zenith Bank)</td>
                        <td><code>1234567890</code></td>
                        <td>SANDBOX ACCOUNT</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Testing Scenarios</h2>

            <h3>Scenario 1: Create Customer & Virtual Account</h3>
            <ol>
                <li>Create a customer using <code>POST /v1/customers</code></li>
                <li>Store the returned <code>customer_id</code></li>
                <li>Create virtual account using <code>POST /v1/virtual-accounts</code> with the customer_id</li>
                <li>Verify account details in response</li>
            </ol>

            <h3>Scenario 2: Simulate Payment Received</h3>
            <ol>
                <li>Create virtual account (as above)</li>
                <li>In dashboard, use "Simulate Payment" feature</li>
                <li>Enter amount and virtual account number</li>
                <li>Verify webhook notification received</li>
                <li>Check balance updated in dashboard</li>
            </ol>

            <h3>Scenario 3: Test Transfer</h3>
            <ol>
                <li>Verify test bank account using <code>POST /v1/transfers/verify</code></li>
                <li>Initiate transfer using <code>POST /v1/transfers</code></li>
                <li>Transfer completes instantly in sandbox</li>
                <li>Verify webhook notification received</li>
                <li>Check transaction status via API</li>
            </ol>

            <h3>Scenario 4: Test Webhook Handling</h3>
            <ol>
                <li>Set up webhook URL in dashboard</li>
                <li>Use ngrok for local testing: <code>ngrok http 3000</code></li>
                <li>Trigger test webhook from dashboard</li>
                <li>Verify signature verification works</li>
                <li>Test idempotency (duplicate webhooks)</li>
            </ol>
        </section>

        <section class="section">
            <h2>Sandbox Limitations</h2>
            <p>Be aware of these differences between sandbox and production:</p>

            <ul>
                <li>❌ Virtual account numbers are simulated (not real bank accounts)</li>
                <li>❌ Cannot receive real payments from external sources</li>
                <li>❌ Transfers complete instantly (no real bank processing delay)</li>
                <li>❌ Balance resets to ₦2M every 24 hours</li>
                <li>❌ Some edge cases may behave differently</li>
                <li>❌ Rate limits are more relaxed than production</li>
            </ul>

            <div class="alert info">
                <strong>💡 Tip:</strong> Always test edge cases and error scenarios in sandbox before going live.
            </div>
        </section>

        <section class="section">
            <h2>Local Development with Webhooks</h2>

            <h3>Using ngrok</h3>
            <p>For local webhook testing, use ngrok to expose your local server:</p>

            <div class="code-block"><code># Install ngrok
npm install -g ngrok

# Expose local port 3000
ngrok http 3000

# Copy the HTTPS URL (e.g., https://abc123.ngrok.io)
# Set this as your webhook URL in dashboard</code></div>

            <h3>Example Webhook Handler (PHP)</h3>
            <div class="code-block"><code>&lt;?php
// webhook.php - Place in your local server root

$webhookSecret = 'your_test_webhook_secret';
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_POINTWAVE_SIGNATURE'] ?? '';

// Verify signature
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    die('Invalid signature');
}

// Process webhook
$data = json_decode($payload, true);
file_put_contents('webhook_log.txt', print_r($data, true), FILE_APPEND);

http_response_code(200);
echo json_encode(['status' => 'success']);
?&gt;</code></div>
        </section>

        <section class="section">
            <h2>Moving to Production</h2>
            <p>When you're ready to go live:</p>

            <ol>
                <li><strong>Complete Testing:</strong> Test all integration flows in sandbox</li>
                <li><strong>Complete KYC:</strong> Submit business documents for verification</li>
                <li><strong>Wait for Approval:</strong> Admin approval usually takes 24 hours</li>
                <li><strong>Get Live Credentials:</strong> Access from dashboard after approval</li>
                <li><strong>Update Configuration:</strong> Switch from test to live credentials</li>
                <li><strong>Update Webhook URL:</strong> Use production URL (not ngrok)</li>
                <li><strong>Fund Wallet:</strong> Add money to your live wallet</li>
                <li><strong>Start Small:</strong> Test with small amounts first</li>
                <li><strong>Monitor Closely:</strong> Watch transactions and webhooks carefully</li>
            </ol>

            <div class="alert warning">
                <strong>⚠️ Important:</strong> Keep your test environment running for ongoing development and testing even after going live.
            </div>
        </section>

        <section class="section">
            <h2>Best Practices</h2>
            <ul>
                <li>✅ Test all API endpoints in sandbox first</li>
                <li>✅ Test error scenarios (insufficient balance, invalid data, etc.)</li>
                <li>✅ Verify webhook signature verification works</li>
                <li>✅ Test idempotency with duplicate requests</li>
                <li>✅ Use meaningful test data (not "test test test")</li>
                <li>✅ Keep test and live credentials separate</li>
                <li>✅ Document your test scenarios</li>
                <li>✅ Test on staging environment before production</li>
            </ul>
        </section>
    </div>
</body>
</html>
