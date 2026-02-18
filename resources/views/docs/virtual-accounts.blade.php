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
            <h1>Virtual Accounts</h1>
            <p>Create and manage PalmPay virtual accounts for your customers</p>
            <p style="margin-top: 10px; font-size: 0.95rem; opacity: 0.9;">
                <strong>🏦 Provider:</strong> PalmPay | <strong>⏰ Settlement:</strong> T+1 (Next business day at 2:00 AM)
            </p>
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
            <p>Virtual accounts allow your customers to receive payments via bank transfer. Each customer gets a unique PalmPay account number that automatically credits their balance when they receive payments.</p>

            <div class="alert info">
                <strong>🏦 PalmPay Integration:</strong> All virtual accounts are created on PalmPay's infrastructure, providing reliable payment processing with T+1 settlement (funds settle next business day at 2:00 AM, excluding weekends and holidays).
            </div>

            <h3>Key Features</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>✅ Instant PalmPay account creation</li>
                <li>✅ Real-time payment notifications via webhooks</li>
                <li>✅ T+1 settlement schedule (configurable)</li>
                <li>✅ BVN/NIN verification for KYC compliance</li>
                <li>✅ Static and dynamic accounts</li>
                <li>✅ Automatic customer deduplication</li>
                <li>✅ Tier 1 (₦300K limit) and Tier 3 (₦5M limit) support</li>
            </ul>
        </section>

        <section class="section">
            <h2>Create Virtual Account</h2>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/v1/virtual-accounts</code>
            </div>

            <p>Creates a virtual account for a customer. If the customer doesn't exist, they will be created automatically.</p>

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
                        <td>Customer's phone number (e.g., 08012345678)</td>
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
                        <td><code>id_type</code></td>
                        <td>string</td>
                        <td><span class="badge optional">Optional</span></td>
                        <td><code>bvn</code> or <code>nin</code></td>
                    </tr>
                    <tr>
                        <td><code>id_number</code></td>
                        <td>string</td>
                        <td><span class="badge optional">Optional</span></td>
                        <td>Customer's BVN or NIN</td>
                    </tr>
                    <tr>
                        <td><code>external_reference</code></td>
                        <td>string</td>
                        <td><span class="badge optional">Optional</span></td>
                        <td>Your unique reference for this account</td>
                    </tr>
                    <tr>
                        <td><code>bank_codes</code></td>
                        <td>array</td>
                        <td><span class="badge optional">Optional</span></td>
                        <td>Array of bank codes (default: ["100033"])</td>
                    </tr>
                </tbody>
            </table>

            <div class="alert info">
                <strong>💡 Note:</strong> If <code>id_number</code> (BVN/NIN) is not provided, the system will use your company's RC number (aggregator mode). This requires PalmPay approval.
            </div>

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

            <h3>Request Example</h3>
            <div class="code-block"><code>POST /v1/virtual-accounts
Content-Type: application/json
Authorization: Bearer YOUR_SECRET_KEY
x-business-id: YOUR_BUSINESS_ID
x-api-key: YOUR_API_KEY
Idempotency-Key: unique-request-id

{
  "first_name": "Jamil",
  "last_name": "Abubakar",
  "email": "jamil@example.com",
  "phone_number": "08078889419",
  "account_type": "static",
  "external_reference": "customer-12345"
}</code></div>

            <h3>Success Response (201 Created)</h3>
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
        "bank_code": "100033",
        "bank_name": "PalmPay",
        "account_number": "6690945661",
        "account_name": "YourBusiness-Jamil Abubakar(PointWave)",
        "account_type": "static",
        "virtual_account_id": "PWV_VA_71B1A38C2F"
      }
    ]
  }
}</code></div>
        </section>

        <section class="section">
            <h2>Customer Deduplication</h2>
            <p>The system automatically prevents duplicate customers:</p>

            <h3>Deduplication Logic</h3>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li><strong>By Email:</strong> If a customer with the same email exists, returns existing customer</li>
                <li><strong>By Phone:</strong> If a customer with the same phone exists, returns existing customer</li>
                <li><strong>By Virtual Account:</strong> If a virtual account already exists for this customer and bank, returns existing account</li>
            </ol>

            <div class="alert success">
                <strong>✅ Benefit:</strong> You can safely call the API multiple times without creating duplicates. The system will return the existing customer/account.
            </div>
        </section>

        <section class="section">
            <h2>Get Customer Details</h2>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/v1/customers/{customer_id}</code>
            </div>

            <h3>Request Example</h3>
            <div class="code-block"><code>GET /v1/customers/1efdfc4845a7327bc9271ff0daafdae551d07524
Authorization: Bearer YOUR_SECRET_KEY
x-business-id: YOUR_BUSINESS_ID
x-api-key: YOUR_API_KEY</code></div>

            <h3>Success Response (200 OK)</h3>
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
            <h2>Update Customer</h2>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/v1/customers/{customer_id}</code>
            </div>

            <p>Update customer information. All fields are optional.</p>

            <h3>Request Example</h3>
            <div class="code-block"><code>POST /v1/customers/1efdfc4845a7327bc9271ff0daafdae551d07524
Content-Type: application/json
Authorization: Bearer YOUR_SECRET_KEY
x-business-id: YOUR_BUSINESS_ID
x-api-key: YOUR_API_KEY
Idempotency-Key: unique-request-id

{
  "first_name": "Jamil",
  "last_name": "Abubakar Bashir",
  "phone_number": "08078889420"
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
                        <td>PalmPay</td>
                        <td><code>100033</code></td>
                        <td>✅ Active (Default)</td>
                    </tr>
                    <tr>
                        <td>Blooms MFB</td>
                        <td><code>090743</code></td>
                        <td>✅ Active</td>
                    </tr>
                </tbody>
            </table>

            <p style="margin-top: 15px;">To create accounts on multiple banks, pass an array of bank codes:</p>
            <div class="code-block"><code>{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone_number": "08012345678",
  "account_type": "static",
  "bank_codes": ["100033", "090743"]
}</code></div>
        </section>

        <section class="section">
            <h2>Receiving Payments</h2>
            <p>When a customer receives a payment to their virtual account:</p>

            <ol style="margin-left: 20px; margin-top: 10px;">
                <li>Payment is received by the bank (PalmPay)</li>
                <li>Bank sends webhook notification to PointPay</li>
                <li>PointPay processes the payment and credits customer balance</li>
                <li>PointPay sends webhook notification to your system</li>
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
                <li>✅ Always use <code>external_reference</code> to track accounts in your system</li>
                <li>✅ Store the <code>customer_id</code> and <code>virtual_account_id</code> in your database</li>
                <li>✅ Use static accounts for wallets and recurring payments</li>
                <li>✅ Use dynamic accounts for one-time payments and invoices</li>
                <li>✅ Implement webhook handlers to receive payment notifications</li>
                <li>✅ Handle duplicate requests gracefully (system auto-deduplicates)</li>
            </ul>
        </section>
    </div>
</body>
</html>
