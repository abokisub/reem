<?php
// Quick fix for oyitipay webhook signature issue
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;

echo "🔧 FIXING OYITIPAY WEBHOOK SIGNATURE ISSUE\n";
echo "==========================================\n\n";

echo "📋 ISSUE ANALYSIS:\n";
echo "==================\n";
echo "- Company: oyitipay.com\n";
echo "- Problem: Missing X-PointWave-Signature header\n";
echo "- Endpoint: /api/pointwave/webhook\n";
echo "- Payload: {\"test\":\"webhook\"}\n";
echo "- Status: Webhook rejected due to missing signature\n\n";

echo "✅ WORKING EXAMPLES:\n";
echo "===================\n";
echo "- amtpay.com.ng: Uses /api/pointwave/webhook WITH signature ✅\n";
echo "- kobopoint.com: Uses /webhooks/pointwave WITH signature ✅\n\n";

echo "🔍 SOLUTION FOR OYITIPAY:\n";
echo "========================\n";

// Find oyitipay company
$companies = Company::where('name', 'like', '%oyiti%')
    ->orWhere('name', 'like', '%oyitipay%')
    ->get();

if ($companies->count() > 0) {
    foreach ($companies as $company) {
        echo "📋 Found Company: {$company->name} (ID: {$company->id})\n";
        echo "- API Secret Key: " . ($company->api_secret_key ? 'SET ✅' : 'MISSING ❌') . "\n";
        echo "- Test Secret Key: " . ($company->test_secret_key ? 'SET ✅' : 'MISSING ❌') . "\n";
        echo "- Webhook URL: " . ($company->webhook_url ?? 'Not Set') . "\n\n";
        
        if ($company->api_secret_key) {
            $secretKey = $company->api_secret_key;
            $testPayload = '{"test":"webhook"}';
            $correctSignature = hash_hmac('sha256', $testPayload, $secretKey);
            
            echo "🔑 CORRECT SIGNATURE GENERATION:\n";
            echo "==============================\n";
            echo "Secret Key: " . substr($secretKey, 0, 10) . "... (length: " . strlen($secretKey) . ")\n";
            echo "Payload: $testPayload\n";
            echo "Signature: $correctSignature\n\n";
            
            echo "📝 WHAT OYITIPAY NEEDS TO DO:\n";
            echo "============================\n";
            echo "1. Add X-PointWave-Signature header to webhook requests\n";
            echo "2. Generate signature using: hash_hmac('sha256', \$payload, \$secret_key)\n";
            echo "3. Use their API Secret Key: " . substr($secretKey, 0, 10) . "...\n\n";
            
            echo "💻 EXAMPLE CODE FOR OYITIPAY:\n";
            echo "============================\n";
            echo "```php\n";
            echo "// PHP Example\n";
            echo "\$payload = json_encode(['test' => 'webhook']);\n";
            echo "\$secretKey = '{$secretKey}';\n";
            echo "\$signature = hash_hmac('sha256', \$payload, \$secretKey);\n\n";
            echo "\$headers = [\n";
            echo "    'Content-Type: application/json',\n";
            echo "    'X-PointWave-Signature: ' . \$signature\n";
            echo "];\n\n";
            echo "curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);\n";
            echo "```\n\n";
            
            echo "```javascript\n";
            echo "// Node.js Example\n";
            echo "const crypto = require('crypto');\n";
            echo "const payload = JSON.stringify({test: 'webhook'});\n";
            echo "const secretKey = '{$secretKey}';\n";
            echo "const signature = crypto.createHmac('sha256', secretKey).update(payload).digest('hex');\n\n";
            echo "const headers = {\n";
            echo "    'Content-Type': 'application/json',\n";
            echo "    'X-PointWave-Signature': signature\n";
            echo "};\n";
            echo "```\n\n";
        }
    }
} else {
    echo "❌ Oyitipay company not found in database\n";
    echo "Please check the company name or ID\n\n";
}

echo "🎯 IMMEDIATE ACTION REQUIRED:\n";
echo "============================\n";
echo "Contact oyitipay.com and tell them:\n\n";
echo "\"Your webhook requests are missing the required signature header.\n";
echo "Please add 'X-PointWave-Signature' header with HMAC-SHA256 signature\n";
echo "using your API Secret Key.\n\n";
echo "Current request: Missing signature ❌\n";
echo "Required: X-PointWave-Signature: [hmac_sha256_hash] ✅\n\n";
echo "This is the same signature method used by amtpay and kobopoint.\"\n\n";

echo "📞 SUPPORT MESSAGE FOR OYITIPAY:\n";
echo "===============================\n";
echo "Subject: Webhook Signature Required - Action Needed\n\n";
echo "Hi Oyitipay Team,\n\n";
echo "We noticed your webhook requests to PointWave are missing the required\n";
echo "signature header. This is causing your webhooks to be rejected.\n\n";
echo "Please add the 'X-PointWave-Signature' header to all webhook requests\n";
echo "using HMAC-SHA256 with your API Secret Key.\n\n";
echo "This is the same method used by other integrated companies like amtpay.\n\n";
echo "Let us know if you need any assistance!\n\n";
echo "Best regards,\n";
echo "PointWave Support Team\n\n";

echo "✅ DIAGNOSTIC COMPLETED\n";