<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfers - PointPay API Documentation</title>
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
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Transfers</h1>
            <p>Send money to bank accounts</p>
        </div>
    </header>

    <div class="container">
        <nav class="nav">
            <a href="{{ route('docs.index') }}">← Back</a>
            <a href="{{ route('docs.webhooks') }}">Webhooks</a>
        </nav>

        <section class="section">
            <h2>Initiate Transfer</h2>
            <div class="code-block"><code>POST /v1/transfers
Content-Type: application/json
Authorization: Bearer YOUR_SECRET_KEY
x-business-id: YOUR_BUSINESS_ID
x-api-key: YOUR_API_KEY
Idempotency-Key: unique-request-id

{
  "amount": 5000,
  "account_number": "0123456789",
  "bank_code": "058",
  "narration": "Payment for services",
  "reference": "YOUR-REF-123"
}

Response:
{
  "status": true,
  "message": "Transfer initiated successfully",
  "data": {
    "transaction_id": "TXN_ABC123",
    "reference": "YOUR-REF-123",
    "amount": 5000,
    "fee": 50,
    "status": "pending"
  }
}</code></div>
        </section>

        <section class="section">
            <h2>Get Transactions</h2>
            <div class="code-block"><code>GET /v1/transactions?page=1&limit=50

Response:
{
  "status": true,
  "data": {
    "transactions": [...],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_records": 250
    }
  }
}</code></div>
        </section>
    </div>
</body>
</html>
