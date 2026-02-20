<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Codes - PointWave API Documentation</title>
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
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f5f5f5; font-weight: 600; color: #667eea; }
        table tr:hover { background: #f8f9fa; }
        .alert { padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid; }
        .alert.info { background: #e3f2fd; border-color: #2196f3; color: #0d47a1; }
        ul { margin-left: 25px; margin-top: 15px; }
        ul li { margin-bottom: 10px; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>😠 Error Codes & Troubleshooting</h1>
            <p>Understanding and resolving API errors</p>
        </div>
    </header>

    <div class="container">
        <nav class="nav">
            <a href="{{ route('docs.index') }}">← Back to Docs</a>
            <a href="{{ route('docs.authentication') }}">Authentication</a>
            <a href="{{ route('docs.webhooks') }}">Webhooks</a>
        </nav>

        <section class="section">
            <h2>Error Response Format</h2>
            <p>All API errors follow a consistent JSON format:</p>
            <div class="code-block"><code>{
  "status": false,
  "request_id": "abc123-def456-789",
  "message": "Error description",
  "errors": {
    "field_name": ["Error message for this field"]
  }
}</code></div>

            <h3>Response Fields</h3>
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>status</code></td>
                        <td>boolean</td>
                        <td>Always <code>false</code> for errors</td>
                    </tr>
                    <tr>
                        <td><code>request_id</code></td>
                        <td>string</td>
                        <td>Unique identifier for this request (use for support)</td>
                    </tr>
                    <tr>
                        <td><code>message</code></td>
                        <td>string</td>
                        <td>Human-readable error description</td>
                    </tr>
                    <tr>
                        <td><code>errors</code></td>
                        <td>object</td>
                        <td>Field-specific validation errors (422 only)</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>HTTP Status Codes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Meaning</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>200</code></td>
                        <td>OK</td>
                        <td>Request successful</td>
                    </tr>
                    <tr>
                        <td><code>201</code></td>
                        <td>Created</td>
                        <td>Resource created successfully</td>
                    </tr>
                    <tr>
                        <td><code>400</code></td>
                        <td>Bad Request</td>
                        <td>Invalid request format or parameters</td>
                    </tr>
                    <tr>
                        <td><code>401</code></td>
                        <td>Unauthorized</td>
                        <td>Invalid or missing authentication credentials</td>
                    </tr>
                    <tr>
                        <td><code>403</code></td>
                        <td>Forbidden</td>
                        <td>Account not activated or API access locked</td>
                    </tr>
                    <tr>
                        <td><code>404</code></td>
                        <td>Not Found</td>
                        <td>Resource doesn't exist</td>
                    </tr>
                    <tr>
                        <td><code>422</code></td>
                        <td>Validation Error</td>
                        <td>Request data failed validation rules</td>
                    </tr>
                    <tr>
                        <td><code>429</code></td>
                        <td>Too Many Requests</td>
                        <td>Rate limit exceeded</td>
                    </tr>
                    <tr>
                        <td><code>500</code></td>
                        <td>Server Error</td>
                        <td>Internal server error</td>
                    </tr>
                    <tr>
                        <td><code>503</code></td>
                        <td>Service Unavailable</td>
                        <td>Temporary service disruption</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Common Errors & Solutions</h2>

            <h3>Authentication Errors (401)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Error Message</th>
                        <th>Cause</th>
                        <th>Solution</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Invalid credentials</td>
                        <td>Wrong API keys</td>
                        <td>Verify Business ID, API Key, and Secret Key from dashboard</td>
                    </tr>
                    <tr>
                        <td>Missing authentication headers</td>
                        <td>Required headers not sent</td>
                        <td>Include Authorization, x-api-key, x-business-id headers</td>
                    </tr>
                    <tr>
                        <td>Invalid token format</td>
                        <td>Malformed Bearer token</td>
                        <td>Use format: <code>Bearer {SECRET_KEY}</code></td>
                    </tr>
                </tbody>
            </table>

            <h3>Authorization Errors (403)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Error Message</th>
                        <th>Cause</th>
                        <th>Solution</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Account not activated</td>
                        <td>KYC pending or not approved</td>
                        <td>Complete KYC verification and wait for admin approval</td>
                    </tr>
                    <tr>
                        <td>API access locked</td>
                        <td>Account suspended</td>
                        <td>Contact support to unlock your account</td>
                    </tr>
                    <tr>
                        <td>Insufficient permissions</td>
                        <td>Action not allowed</td>
                        <td>Check account permissions or upgrade plan</td>
                    </tr>
                </tbody>
            </table>

            <h3>Validation Errors (422)</h3>
            <div class="code-block"><code>{
  "status": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "phone_number": ["The phone number format is invalid."],
    "amount": ["The amount must be at least 100."]
  }
}</code></div>

            <table>
                <thead>
                    <tr>
                        <th>Field Error</th>
                        <th>Cause</th>
                        <th>Solution</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Email already taken</td>
                        <td>Duplicate customer email</td>
                        <td>Use different email or retrieve existing customer</td>
                    </tr>
                    <tr>
                        <td>Invalid phone format</td>
                        <td>Wrong phone number format</td>
                        <td>Use format: 08012345678 (11 digits)</td>
                    </tr>
                    <tr>
                        <td>Amount too small</td>
                        <td>Below minimum amount</td>
                        <td>Use minimum ₦100 for transfers</td>
                    </tr>
                    <tr>
                        <td>Invalid bank code</td>
                        <td>Bank code doesn't exist</td>
                        <td>Get valid codes from /v1/banks endpoint</td>
                    </tr>
                </tbody>
            </table>

            <h3>Business Logic Errors (400)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Error Message</th>
                        <th>Cause</th>
                        <th>Solution</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Insufficient balance</td>
                        <td>Not enough funds in wallet</td>
                        <td>Top up your wallet before transfer</td>
                    </tr>
                    <tr>
                        <td>Customer not found</td>
                        <td>Invalid customer_id</td>
                        <td>Create customer first or use correct customer_id</td>
                    </tr>
                    <tr>
                        <td>Duplicate transaction</td>
                        <td>Same Idempotency-Key used</td>
                        <td>Use unique Idempotency-Key for each request</td>
                    </tr>
                    <tr>
                        <td>Transfer limit exceeded</td>
                        <td>Daily/transaction limit reached</td>
                        <td>Wait for limit reset or contact support</td>
                    </tr>
                </tbody>
            </table>

            <h3>Server Errors (500)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Error Message</th>
                        <th>Cause</th>
                        <th>Solution</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Internal server error</td>
                        <td>Unexpected server issue</td>
                        <td>Retry request or contact support with request_id</td>
                    </tr>
                    <tr>
                        <td>Provider error</td>
                        <td>Bank API issue</td>
                        <td>Retry after a few minutes or contact support</td>
                    </tr>
                    <tr>
                        <td>Database error</td>
                        <td>Database connection issue</td>
                        <td>Retry request or contact support</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Rate Limiting (429)</h2>
            <p>PointWave implements rate limiting to ensure fair usage:</p>

            <table>
                <thead>
                    <tr>
                        <th>Endpoint Type</th>
                        <th>Limit</th>
                        <th>Window</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Read operations (GET)</td>
                        <td>100 requests</td>
                        <td>Per minute</td>
                    </tr>
                    <tr>
                        <td>Write operations (POST/PUT)</td>
                        <td>50 requests</td>
                        <td>Per minute</td>
                    </tr>
                    <tr>
                        <td>Transfers</td>
                        <td>20 requests</td>
                        <td>Per minute</td>
                    </tr>
                </tbody>
            </table>

            <div class="alert info">
                <strong>💡 Tip:</strong> When rate limited, wait 60 seconds before retrying. Implement exponential backoff for better reliability.
            </div>
        </section>

        <section class="section">
            <h2>Troubleshooting Guide</h2>

            <h3>Problem: "Invalid credentials" error</h3>
            <ul>
                <li>✅ Verify you're using the correct credentials (test vs live)</li>
                <li>✅ Check for extra spaces or line breaks in credentials</li>
                <li>✅ Ensure Authorization header format: <code>Bearer {SECRET_KEY}</code></li>
                <li>✅ Confirm all three headers are present (Authorization, x-api-key, x-business-id)</li>
            </ul>

            <h3>Problem: "Account not activated" error</h3>
            <ul>
                <li>✅ Complete KYC verification in dashboard</li>
                <li>✅ Wait for admin approval (usually 24 hours)</li>
                <li>✅ Check email for approval notification</li>
                <li>✅ Contact support if waiting more than 48 hours</li>
            </ul>

            <h3>Problem: "Insufficient balance" error</h3>
            <ul>
                <li>✅ Check wallet balance in dashboard</li>
                <li>✅ Remember: transfer amount + fee is deducted</li>
                <li>✅ Top up wallet before initiating transfer</li>
                <li>✅ Use sandbox mode for testing without real money</li>
            </ul>

            <h3>Problem: Transfer not completing</h3>
            <ul>
                <li>✅ Verify bank account details before transfer</li>
                <li>✅ Check transaction status via API</li>
                <li>✅ Wait for webhook notification (may take 1-5 minutes)</li>
                <li>✅ Contact support with transaction_id if stuck</li>
            </ul>

            <h3>Problem: Webhook not received</h3>
            <ul>
                <li>✅ Verify webhook URL is publicly accessible</li>
                <li>✅ Check your server is returning HTTP 200</li>
                <li>✅ Review webhook logs in dashboard</li>
                <li>✅ Test with ngrok for local development</li>
                <li>✅ Ensure firewall allows incoming requests</li>
            </ul>
        </section>

        <section class="section">
            <h2>Getting Help</h2>
            <p>If you're still experiencing issues:</p>

            <ol>
                <li><strong>Check Documentation:</strong> Review relevant API documentation</li>
                <li><strong>Search Logs:</strong> Check your application logs for details</li>
                <li><strong>Test in Sandbox:</strong> Reproduce issue in test environment</li>
                <li><strong>Contact Support:</strong> Email support@pointwave.ng with:
                    <ul style="margin-top: 10px;">
                        <li>Request ID from error response</li>
                        <li>Timestamp of the error</li>
                        <li>Complete error message</li>
                        <li>Steps to reproduce</li>
                    </ul>
                </li>
            </ol>

            <div class="alert info">
                <strong>💡 Support Hours:</strong> Monday - Friday, 9:00 AM - 5:00 PM WAT. Response time: 2-4 hours during business hours.
            </div>
        </section>
    </div>
</body>
</html>
