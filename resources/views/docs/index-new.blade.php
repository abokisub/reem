<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PointWave API Documentation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; line-height: 1.6; color: #2c3e50; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 60px 0; margin-bottom: 40px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        header h1 { font-size: 3rem; margin-bottom: 15px; font-weight: 700; }
        header p { font-size: 1.3rem; opacity: 0.95; margin-bottom: 10px; }
        .version { background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; display: inline-block; font-size: 0.9rem; margin-top: 15px; }
        .nav { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 40px; }
        .nav a { padding: 15px 20px; background: white; color: #667eea; text-decoration: none; border-radius: 8px; transition: all 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.1); font-weight: 600; text-align: center; border: 2px solid transparent; }
        .nav a:hover { background: #667eea; color: white; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(102,126,234,0.3); border-color: #667eea; }
        .section { background: white; padding: 40px; margin-bottom: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .section h2 { color: #667eea; margin-bottom: 25px; font-size: 2rem; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        .section h3 { color: #764ba2; margin: 30px 0 15px; font-size: 1.5rem; }
        .section h4 { color: #555; margin: 20px 0 10px; font-size: 1.2rem; }
        .code-block { background: #2d2d2d; color: #f8f8f2; padding: 25px; border-radius: 8px; overflow-x: auto; margin: 20px 0; border-left: 4px solid #667eea; font-family: 'Courier New', monospace; font-size: 0.9rem; line-height: 1.5; }
        .code-block code { white-space: pre; }
        .endpoint-box { background: #f0f4ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea; }
        .method { display: inline-block; padding: 8px 16px; border-radius: 5px; font-weight: bold; margin-right: 15px; font-size: 0.9rem; }
        .method.post { background: #49cc90; color: white; }
        .method.get { background: #61affe; color: white; }
        .method.put { background: #fca130; color: white; }
        .alert { padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid; }
        .alert.info { background: #e3f2fd; border-color: #2196f3; color: #0d47a1; }
        .alert.success { background: #e8f5e9; border-color: #4caf50; color: #1b5e20; }
        .alert.warning { background: #fff3e0; border-color: #ff9800; color: #e65100; }
        .alert.danger { background: #ffebee; border-color: #f44336; color: #b71c1c; }
        table { width: 100%; border-collapse: collapse; margin: 25px 0; background: white; }
        table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        table th { background: #f5f7fa; font-weight: 600; color: #667eea; }
        table tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: 600; }
        .badge.required { background: #f44336; color: white; }
        .badge.optional { background: #ff9800; color: white; }
        ol, ul { margin-left: 25px; margin-top: 15px; }
        ol li, ul li { margin-bottom: 10px; }
        footer { text-align: center; padding: 50px 0; color: #666; background: white; margin-top: 40px; border-top: 1px solid #e0e0e0; }
        .emoji { font-size: 1.2em; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><span class="emoji">👋</span> PointWave API Documentation</h1>
            <p>Enterprise Payment Gateway for Nigerian Businesses</p>
            <div class="version">Version 1.0 | Last Updated: February 2026</div>
            <p style="margin-top: 20px; font-size: 1rem; opacity: 0.9;">
                <strong>Base URL:</strong> <code style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 5px;">https://app.pointwave.ng/api/gateway</code>
            </p>
        </div>
    </header>

    <div class="container">
        <nav class="nav">
            <a href="{{ route('docs.authentication') }}"><span class="emoji">🔐</span> Authentication</a>
            <a href="{{ route('docs.errors') }}"><span class="emoji">😠</span> Error Codes</a>
            <a href="{{ route('docs.customers') }}"><span class="emoji">🔖</span> Customers</a>
            <a href="{{ route('docs.virtual-accounts') }}"><span class="emoji">🏘️</span> Virtual Accounts</a>
            <a href="{{ route('docs.transfers') }}"><span class="emoji">💷</span> Transfers</a>
            <a href="{{ route('docs.webhooks') }}"><span class="emoji">🔏</span> Webhooks</a>
            <a href="{{ route('docs.banks') }}"><span class="emoji">🏦</span> Banks</a>
            <a href="{{ route('docs.sandbox') }}"><span class="emoji">🧪</span> Sandbox</a>
        </nav>

        <section class="section">
            <h2><span class="emoji">📖</span> Overview</h2>
            <p style="font-size: 1.1rem; margin-bottom: 20px;">Welcome to PointWave API - Nigeria's most reliable payment gateway for virtual accounts, bank transfers, and identity verification.</p>

            <div class="alert info">
                <strong><span class="emoji">💡</span> What You Can Do:</strong>
                <ul style="margin-top: 10px;">
                    <li>Create virtual bank accounts for your customers</li>
                    <li>Receive payments via bank transfer</li>
                    <li>Send money to any Nigerian bank account</li>
                    <li>Verify customer identity (BVN/NIN)</li>
                    <li>Get real-time webhook notifications</li>
                </ul>
            </div>

            <h3><span class="emoji">🚀</span> Quick Start Guide</h3>
            <ol>
                <li><strong>Sign Up:</strong> Create account at <a href="https://app.pointwave.ng/register" style="color: #667eea;">app.pointwave.ng/register</a></li>
                <li><strong>Complete KYC:</strong> Submit your business documents</li>
                <li><strong>Get Approved:</strong> Wait for admin approval (usually 24 hours)</li>
                <li><strong>Get Credentials:</strong> Access your API keys from dashboard</li>
                <li><strong>Start Building:</strong> Make your first API call</li>
            </ol>

            <h3><span class="emoji">🔑</span> API Credentials</h3>
            <p>After approval, you'll receive three credentials:</p>
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
                        <td>Identifies your business</td>
                    </tr>
                    <tr>
                        <td><strong>API Key</strong></td>
                        <td>40-character hex string</td>
                        <td>Public key for requests</td>
                    </tr>
                    <tr>
                        <td><strong>Secret Key</strong></td>
                        <td>120-character hex string</td>
                        <td>Private key (keep secure!)</td>
                    </tr>
                </tbody>
            </table>

            <div class="alert warning">
                <strong><span class="emoji">⚠️</span> Security Warning:</strong> Never expose your Secret Key in client-side code, public repositories, or logs. Store it securely on your server.
            </div>
        </section>

        <section class="section">
            <h2><span class="emoji">🌍</span> Environments</h2>
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
                        <td><strong>Production</strong></td>
                        <td><code>https://app.pointwave.ng/api/gateway</code></td>
                        <td>Live transactions with real money</td>
                    </tr>
                    <tr>
                        <td><strong>Sandbox</strong></td>
                        <td><code>https://app.pointwave.ng/api/gateway</code></td>
                        <td>Testing with test credentials (₦2M balance)</td>
                    </tr>
                </tbody>
            </table>

            <div class="alert info">
                <strong><span class="emoji">💡</span> Tip:</strong> Use sandbox credentials (prefix: <code>test_</code>) for development and testing. Switch to live credentials when ready for production.
            </div>
        </section>

        <section class="section">
            <h2><span class="emoji">📋</span> Integration Flow</h2>
            <p style="font-size: 1.1rem; margin-bottom: 20px;">Follow this recommended integration sequence:</p>

            <div style="background: #f8f9fa; padding: 30px; border-radius: 8px; border-left: 4px solid #667eea;">
                <h3 style="margin-top: 0;">Step 1: Create Customer</h3>
                <p>First, create a customer record with their details.</p>
                <div class="code-block"><code>POST /v1/customers</code></div>

                <h3>Step 2: Update Customer (Optional)</h3>
                <p>Update customer information if needed.</p>
                <div class="code-block"><code>PUT /v1/customers/{customer_id}</code></div>

                <h3>Step 3: Create Virtual Account</h3>
                <p>Create a virtual account for the customer to receive payments.</p>
                <div class="code-block"><code>POST /v1/virtual-accounts</code></div>

                <h3>Step 4: Setup Webhooks</h3>
                <p>Configure webhook URL to receive payment notifications.</p>
                <div class="code-block"><code>Configure in Dashboard</code></div>

                <h3>Step 5: Make Transfers</h3>
                <p>Send money to bank accounts when needed.</p>
                <div class="code-block"><code>POST /v1/transfers</code></div>
            </div>

            <div class="alert success">
                <strong><span class="emoji">✅</span> Best Practice:</strong> Always create customers first before creating virtual accounts. This ensures better data quality and control.
            </div>
        </section>

        <section class="section">
            <h2><span class="emoji">📞</span> Support & Resources</h2>
            <table>
                <thead>
                    <tr>
                        <th>Resource</th>
                        <th>Link/Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Email Support</strong></td>
                        <td><a href="mailto:support@pointwave.ng">support@pointwave.ng</a></td>
                    </tr>
                    <tr>
                        <td><strong>Documentation</strong></td>
                        <td><a href="https://app.pointwave.ng/docs">app.pointwave.ng/docs</a></td>
                    </tr>
                    <tr>
                        <td><strong>Dashboard</strong></td>
                        <td><a href="https://app.pointwave.ng">app.pointwave.ng</a></td>
                    </tr>
                    <tr>
                        <td><strong>Status Page</strong></td>
                        <td><a href="https://status.pointwave.ng">status.pointwave.ng</a></td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>

    <footer>
        <div class="container">
            <p><strong>© 2026 PointWave. All rights reserved.</strong></p>
            <p style="margin-top: 10px;">Empowering Nigerian businesses with seamless payment solutions</p>
        </div>
    </footer>
</body>
</html>
