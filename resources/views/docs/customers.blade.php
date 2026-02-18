<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - PointPay API Documentation</title>
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
        .code-block { background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #667eea; }
        .code-block code { font-family: 'Courier New', monospace; white-space: pre; }
        .alert { padding: 15px; border-radius: 5px; margin: 15px 0; }
        .alert.info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Customers</h1>
            <p>Manage your end-users with PalmPay KYC verification</p>
            <p style="margin-top: 10px; font-size: 0.95rem; opacity: 0.9;">
                <strong>🏦 Provider:</strong> PalmPay | <strong>🔐 KYC:</strong> BVN & NIN Verification
            </p>
        </div>
    </header>

    <div class="container">
        <nav class="nav">
            <a href="{{ route('docs.index') }}">← Back</a>
            <a href="{{ route('docs.virtual-accounts') }}">Virtual Accounts</a>
        </nav>

        <section class="section">
            <h2>Overview</h2>
            <p>Customers are automatically created when you create virtual accounts. You can also create customers separately if needed.</p>

            <div class="alert info">
                <strong>🏦 PalmPay KYC Integration:</strong> All customers are verified using PalmPay's BVN/NIN verification system. This ensures compliance with CBN regulations and enables higher transaction limits.
            </div>

            <div class="alert info">
                <strong>💡 Recommended:</strong> Use the Virtual Accounts endpoint directly. It will create customers automatically with KYC verification.
            </div>

            <h3>KYC Tiers</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li><strong>Tier 1 (BVN):</strong> ₦300,000 daily limit - Requires BVN verification</li>
                <li><strong>Tier 3 (NIN):</strong> ₦5,000,000 daily limit - Requires NIN verification</li>
            </ul>
        </section>

        <section class="section">
            <h2>Get Customer</h2>
            <div class="code-block"><code>GET /v1/customers/{customer_id}

Response:
{
  "status": true,
  "data": {
    "uuid": "1efdfc4845a7327bc9271ff0daafdae551d07524",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "08012345678"
  }
}</code></div>
        </section>
    </div>
</body>
</html>
