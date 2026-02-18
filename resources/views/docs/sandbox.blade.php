<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sandbox - PointPay API Documentation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; line-height: 1.6; color: #333; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 0; margin-bottom: 40px; }
        header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .nav { display: flex; gap: 20px; margin-bottom: 40px; flex-wrap: wrap; }
        .nav a { padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; }
        .section { background: white; padding: 30px; margin-bottom: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section h2 { color: #667eea; margin-bottom: 20px; }
        .alert { padding: 15px; border-radius: 5px; margin: 15px 0; }
        .alert.success { background: #d4edda; border-left: 4px solid #28a745; }
        .alert.info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f5f5f5; font-weight: 600; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Sandbox Environment</h1>
            <p>Test your integration safely</p>
        </div>
    </header>

    <div class="container">
        <nav class="nav">
            <a href="{{ route('docs.index') }}">← Back to Docs</a>
        </nav>

        <section class="section">
            <h2>Overview</h2>
            <p>The sandbox environment allows you to test your integration without using real money or affecting production data.</p>

            <div class="alert success">
                <strong>✅ Safe Testing:</strong> All transactions in sandbox mode are simulated. No real money is involved.
            </div>
        </section>

        <section class="section">
            <h2>Sandbox Features</h2>
            <table>
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Initial Balance</strong></td>
                        <td>2,000,000 NGN</td>
                    </tr>
                    <tr>
                        <td><strong>Balance Reset</strong></td>
                        <td>Every 24 hours</td>
                    </tr>
                    <tr>
                        <td><strong>API Endpoint</strong></td>
                        <td>Same as production</td>
                    </tr>
                    <tr>
                        <td><strong>Credentials</strong></td>
                        <td>Use your test credentials</td>
                    </tr>
                    <tr>
                        <td><strong>Virtual Accounts</strong></td>
                        <td>Simulated (prefix: 99)</td>
                    </tr>
                    <tr>
                        <td><strong>Transfers</strong></td>
                        <td>Instant success (simulated)</td>
                    </tr>
                    <tr>
                        <td><strong>Webhooks</strong></td>
                        <td>Sent normally</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Using Sandbox Mode</h2>
            <p>Your account has separate credentials for test and live modes. Use your test credentials to access sandbox mode.</p>

            <div class="alert info">
                <strong>💡 Tip:</strong> Test credentials work exactly like live credentials. Just use them in your API requests.
            </div>

            <h3>Sandbox Limitations</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Virtual account numbers start with "99" (not real bank accounts)</li>
                <li>Transfers complete instantly (no real bank processing)</li>
                <li>Balance resets to 2M NGN every 24 hours</li>
                <li>Cannot receive real payments</li>
            </ul>
        </section>

        <section class="section">
            <h2>Testing Scenarios</h2>
            
            <h3>1. Create Virtual Account</h3>
            <p>Test creating virtual accounts for customers</p>

            <h3>2. Simulate Payment</h3>
            <p>Manually trigger payment webhooks from dashboard</p>

            <h3>3. Test Transfers</h3>
            <p>Initiate transfers (will succeed instantly)</p>

            <h3>4. Webhook Testing</h3>
            <p>Verify your webhook handlers receive and process events correctly</p>
        </section>

        <section class="section">
            <h2>Moving to Production</h2>
            <p>When you're ready to go live:</p>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li>Complete KYC verification</li>
                <li>Wait for admin approval</li>
                <li>Switch to live credentials</li>
                <li>Update webhook URLs if needed</li>
                <li>Start processing real transactions</li>
            </ol>

            <div class="alert info">
                <strong>💡 Best Practice:</strong> Keep your test environment running for ongoing testing and development.
            </div>
        </section>
    </div>
</body>
</html>
