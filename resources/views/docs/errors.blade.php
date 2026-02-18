<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Codes - PointPay API Documentation</title>
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
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f5f5f5; font-weight: 600; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Error Codes</h1>
            <p>Understanding API errors</p>
        </div>
    </header>

    <div class="container">
        <nav class="nav">
            <a href="{{ route('docs.index') }}">← Back to Docs</a>
        </nav>

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
                        <td>Invalid request format</td>
                    </tr>
                    <tr>
                        <td><code>401</code></td>
                        <td>Unauthorized</td>
                        <td>Invalid or missing credentials</td>
                    </tr>
                    <tr>
                        <td><code>403</code></td>
                        <td>Forbidden</td>
                        <td>Account not activated or API locked</td>
                    </tr>
                    <tr>
                        <td><code>404</code></td>
                        <td>Not Found</td>
                        <td>Resource doesn't exist</td>
                    </tr>
                    <tr>
                        <td><code>422</code></td>
                        <td>Validation Error</td>
                        <td>Request data failed validation</td>
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
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Error Response Format</h2>
            <p>All errors follow this format:</p>
            <pre style="background: #f5f5f5; padding: 20px; border-radius: 5px; overflow-x: auto;">{
  "status": false,
  "request_id": "abc123...",
  "message": "Error description",
  "data": []
}</pre>
        </section>

        <section class="section">
            <h2>Common Errors</h2>
            <table>
                <thead>
                    <tr>
                        <th>Error</th>
                        <th>Cause</th>
                        <th>Solution</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Invalid credentials</td>
                        <td>Wrong API keys</td>
                        <td>Check Business ID, API Key, Secret Key</td>
                    </tr>
                    <tr>
                        <td>Account not activated</td>
                        <td>KYC pending</td>
                        <td>Complete KYC and wait for approval</td>
                    </tr>
                    <tr>
                        <td>Insufficient balance</td>
                        <td>Not enough funds</td>
                        <td>Top up your account</td>
                    </tr>
                    <tr>
                        <td>Provider error</td>
                        <td>Bank API issue</td>
                        <td>Retry or contact support</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>
