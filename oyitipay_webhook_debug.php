<?php
// Oyitipay Webhook Debug Script
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;

echo "🔍 OYITIPAY WEBHOOK DEBUG\n";
echo "========================\n\n";

// Find oyitipay company
$company = Company::where('name', 'like', '%oyiti%')->first();

if (!$company) {
    echo "❌ No oyitipay company found\n";
    echo "Available companies:\n";
    $companies = Company::select('id', 'name')->get();
    foreach ($companies as $c) {
        echo "- ID: {$c->id}, Name: {$c->name}\n";
    }
    exit;
}

echo "✅ Found Company: {$company->name}\n";
echo "- Company ID: {$company->id}\n";
echo "- API Secret Key: " . substr($company->api_secret_key, 0, 10) . "...\n\n";

// Test webhook signature generation
$testPayload = '{"test":"webhook"}';
$correctSignature = hash_hmac('sha256', $testPayload, $company->api_secret_key);

echo "🧪 WEBHOOK SIGNATURE TEST:\n";
echo "=========================\n";
echo "Test Payload: {$testPayload}\n";
echo "Secret Key (first 10 chars): " . substr($company->api_secret_key, 0, 10) . "...\n";
echo "Correct Signature: {$correctSignature}\n\n";

echo "📋 INSTRUCTIONS FOR OYITIPAY:\n";
echo "============================\n";
echo "1. Add this header to ALL webhook requests:\n";
echo "   X-PointWave-Signature: {$correctSignature}\n\n";
echo "2. Generate signature using HMAC-SHA256:\n";
echo "   - Algorithm: HMAC-SHA256\n";
echo "   - Secret: {$company->api_secret_key}\n";
echo "   - Payload: [raw JSON body]\n\n";
echo "3. PHP Example:\n";
echo "   \$signature = hash_hmac('sha256', \$jsonPayload, '{$company->api_secret_key}');\n";
echo "   \$headers[] = 'X-PointWave-Signature: ' . \$signature;\n\n";
echo "4. Test with curl:\n";
echo "   curl -X POST https://app.oyitipay.com/api/pointwave/webhook \\\n";
echo "     -H 'Content-Type: application/json' \\\n";
echo "     -H 'X-PointWave-Signature: {$correctSignature}' \\\n";
echo "     -d '{$testPayload}'\n\n";

echo "✅ DEBUG COMPLETED\n";