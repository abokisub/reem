<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PointPay API Documentation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 0; margin-bottom: 40px; }
        header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        header p { font-size: 1.2rem; opacity: 0.9; }
        .nav { display: flex; gap: 20px; margin-bottom: 40px; flex-wrap: wrap; }
        .nav a { padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; transition: background 0.3s; }
        .nav a:hover { background: #764ba2; }
        .section { background: white; padding: 30px; margin-bottom: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section h2 { color: #667eea; margin-bottom: 20px; font-size: 1.8rem; }
        .section h3 { color: #764ba2; margin: 20px 0 10px; font-size: 1.3rem; }
        .code-block { background: #f5f5f5; padding: 20px; border-radius: 5px; overflow-x: auto; margin: 15px 0; border-left: 4px solid #667eea; }
        .code-block code { font-family: 'Courier New', monospace; font-size: 0.9rem; }
        .endpoint { background: #e8f4f8; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .method { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; margin-right: 10px; }
        .method.post { background: #49cc90; color: white; }
        .method.get { background: #61affe; color: white; }
        .method.put { background: #fca130; color: white; }
        .method.delete { background: #f93e3e; color: white; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f5f5f5; font-weight: 600; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 0.85rem; font-weight: 600; }
        .badge.required { background: #f93e3e; color: white; }
        .badge.optional { background: #fca130; color: white; }
        footer { text-align: center; padding: 40px 0; color: #666; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>PointPay API Documentation</h1>
            <p>Enterprise Payment Gateway API - Version 1.0</p>
            <p style="margin-top: 10px; font-size: 1rem;">Base URL: <code style="background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 3px;">https://app.pointwave.ng/api/v1</code></p>
        </div>
    </header>

    <div class="container">
        <nav class="nav">
            <a href="#getting-started">Getting Started</a>
            <a href="{{ route('docs.authentication') }}">Authentication</a>
            <a href="{{ route('docs.customers') }}">Customers</a>
            <a href="{{ route('docs.virtual-accounts') }}">Virtual Accounts</a>
            <a href="{{ route('docs.transfers') }}">Transfers</a>
            <a href="{{ route('docs.webhooks') }}">Webhooks</a>
            <a href="{{ route('docs.sandbox') }}">Sandbox</a>
            <a href="{{ route('docs.errors') }}">Error Codes</a>
        </nav>

        <section class="section" id="getting-started">
            <h2>Getting Started</h2>
            <p>Welcome to the PointPay API documentation. This guide will help you integrate our payment gateway into your application.</p>
            
            <div class="alert info" style="background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #4caf50;">
                <strong>🏦 Powered by PalmPay:</strong> PointPay uses PalmPay as the unified provider for all services - virtual accounts, identity verification (BVN/NIN), and bank transfers. One integration, three powerful features.
            </div>

            <h3>Quick Start</h3>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li>Sign up for a PointPay account at <a href="https://app.pointwave.ng">app.pointwave.ng</a></li>
                <li>Complete your business KYC verification</li>
                <li>Wait for admin approval (usually within 24 hours)</li>
                <li>Get your API credentials from the dashboard</li>
                <li>Start making API calls</li>
            </ol>

            <h3>API Credentials</h3>
            <p>After KYC approval, you'll receive:</p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li><strong>Business ID:</strong> 40-character hex string (identifies your business)</li>
                <li><strong>API Key:</strong> 40-character hex string (public key)</li>
                <li><strong>Secret Key:</strong> 120-character hex string (private key - keep secure!)</li>
                <li><strong>Webhook Secret:</strong> For validating webhook signatures</li>
            </ul>

            <div class="alert info" style="background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #17a2b8;">
                <strong>💡 Test vs Live:</strong> You get separate credentials for testing (sandbox) and production (live). Use test credentials to experiment without real money.
            </div>

            <h3>Environments</h3>
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
                        <td>Production</td>
                        <td><code>https://app.pointwave.ng/api/v1</code></td>
                        <td>Live transactions with real money</td>
                    </tr>
                    <tr>
                        <td>Sandbox</td>
                        <td><code>https://app.pointwave.ng/api/v1</code></td>
                        <td>Testing with test credentials (2M NGN balance)</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Authentication</h2>
            <p>All API requests require authentication using your API credentials.</p>

            <h3>Required Headers</h3>
            <div class="code-block">
                <code>Authorization: Bearer YOUR_SECRET_KEY<br>
x-api-key: YOUR_API_KEY<br>
x-business-id: YOUR_BUSINESS_ID<br>
Content-Type: application/json<br>
Idempotency-Key: unique-request-id (for POST/PUT)</code>
            </div>

            <h3>Example Request</h3>
            <div class="code-block">
                <code>curl -X POST https://app.pointwave.ng/api/v1/virtual-accounts \<br>
  -H "Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1f..." \<br>
  -H "x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba" \<br>
  -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846" \<br>
  -H "Content-Type: application/json" \<br>
  -H "Idempotency-Key: $(uuidgen)" \<br>
  -d '{<br>
    "first_name": "John",<br>
    "last_name": "Doe",<br>
    "email": "john@example.com",<br>
    "phone_number": "08012345678",<br>
    "account_type": "static"<br>
  }'</code>
            </div>

            <p style="margin-top: 20px;"><a href="{{ route('docs.authentication') }}" style="color: #667eea; text-decoration: none; font-weight: 600;">→ View Full Authentication Guide</a></p>
        </section>

        <section class="section">
            <h2>Core Endpoints</h2>
            
            <h3>🏦 PalmPay Integration</h3>
            <p style="margin-bottom: 20px;">All endpoints below are powered by PalmPay's infrastructure, providing reliable and secure payment processing.</p>

            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/v1/customers</code>
                <p style="margin-top: 10px;">Create a new customer with BVN/NIN verification (PalmPay KYC)</p>
            </div>

            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/v1/virtual-accounts</code>
                <p style="margin-top: 10px;">Create a PalmPay virtual account for collections</p>
            </div>

            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/v1/transfers</code>
                <p style="margin-top: 10px;">Initiate bank transfer via PalmPay network</p>
            </div>

            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/v1/transactions</code>
                <p style="margin-top: 10px;">Get transaction history across all PalmPay services</p>
            </div>
        </section>

        <section class="section">
            <h2>Webhooks</h2>
            <p>PointPay sends webhook notifications for important events like successful deposits, failed transfers, etc.</p>
            
            <h3>Webhook Signature Verification</h3>
            <p>All webhooks include an <code>X-PointPay-Signature</code> header for security:</p>
            <div class="code-block">
                <code>$signature = hash_hmac('sha256', $payload, $webhookSecret);<br>
$isValid = hash_equals($signature, $_SERVER['HTTP_X_POINTPAY_SIGNATURE']);</code>
            </div>

            <p style="margin-top: 20px;"><a href="{{ route('docs.webhooks') }}" style="color: #667eea; text-decoration: none; font-weight: 600;">→ View Full Webhook Guide</a></p>
        </section>

        <section class="section">
            <h2>Sandbox Testing</h2>
            <p>Use sandbox mode to test your integration without real money:</p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Each sandbox account gets 2,000,000 NGN balance</li>
                <li>Balance resets every 24 hours</li>
                <li>Use test API credentials (prefix: <code>test_</code>)</li>
                <li>All transactions are simulated</li>
            </ul>

            <p style="margin-top: 20px;"><a href="{{ route('docs.sandbox') }}" style="color: #667eea; text-decoration: none; font-weight: 600;">→ View Sandbox Guide</a></p>
        </section>

        <section class="section">
            <h2>Support</h2>
            <p>Need help? We're here for you:</p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li><strong>Email:</strong> support@pointwave.ng</li>
                <li><strong>Documentation:</strong> <a href="https://app.pointwave.ng/docs">app.pointwave.ng/docs</a></li>
                <li><strong>Status Page:</strong> <a href="https://status.pointwave.ng">status.pointwave.ng</a></li>
            </ul>
        </section>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2026 PointPay. All rights reserved.</p>
            <p style="margin-top: 10px;">Version 1.0.0 | Last Updated: February 17, 2026</p>
        </div>
    </footer>
</body>
</html>
