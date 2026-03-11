<?php
// Simple script to check oyitipay webhook issue on live server
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;

echo "🔍 CHECKING OYITIPAY WEBHOOK ISSUE (LIVE SERVER)\n";
echo "===============================================\n\n";

// Find oyitipay company
$companies = Company::where('name', 'like', '%oyiti%')
    ->orWhere('name', 'like', '%oyitipay%')
    ->orWhere('webhook_url', 'like', '%oyitipay%')
    ->get();

if ($companies->count() === 0) {
    echo "❌ No oyitipay company found. Searching all companies with 'oyiti' in name...\n";
    $companies = Company::where('name', 'like', '%oyiti%')->get();
}

if ($companies->count() === 0) {
    echo "❌ Still no match. Let's check recent webhook logs...\n";
    
    // Check recent webhook logs for oyitipay
    $recentLogs = \DB::table('gateway_webhook_logs')
        ->where('created_at', '>=', now()->subHours(24))
        ->whereJsonContains('payload->host', 'app.oyitipay.com')
        ->orWhere('payload', 'like', '%oyitipay%')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    if ($recentLogs->count() > 0) {
        echo "📋 RECENT WEBHOOK LOGS FROM OYITIPAY:\n";
        foreach ($recentLogs as $log) {
            echo "- ID: {$log->id}, Status: {$log->status}, Created: {$log->created_at}\n";
        }
    } else {
        echo "❌ No recent webhook logs found for oyitipay\n";
    }
    
    echo "\n📝 MANUAL SEARCH NEEDED:\n";
    echo "Run this on live server: php artisan tinker\n";
    echo "Then: Company::where('name', 'like', '%oyiti%')->get();\n";
    exit;
}

foreach ($companies as $company) {
    echo "✅ FOUND COMPANY: {$company->name} (ID: {$company->id})\n";
    echo "================================\n";
    echo "- API Secret Key: " . ($company->api_secret_key ? 'SET ✅' : 'MISSING ❌') . "\n";
    echo "- Test Secret Key: " . ($company->test_secret_key ? 'SET ✅' : 'MISSING ❌') . "\n";
    echo "- Webhook URL: " . ($company->webhook_url ?? 'Not Set') . "\n";
    echo "- Created: {$company->created_at}\n\n";
    
    if ($company->api_secret_key) {
        $secretKey = $company->api_secret_key;
        $testPayload = '{"test":"webhook"}';
        $correctSignature = hash_hmac('sha256', $testPayload, $secretKey);
        
        echo "🔑 SIGNATURE SOLUTION:\n";
        echo "====================\n";
        echo "Secret Key Length: " . strlen($secretKey) . " characters\n";
        echo "Secret Key Preview: " . substr($secretKey, 0, 10) . "...\n";
        echo "Test Payload: $testPayload\n";
        echo "Correct Signature: $correctSignature\n\n";
        
        echo "📞 MESSAGE FOR OYITIPAY:\n";
        echo "=======================\n";
        echo "\"Hi Oyitipay team,\n\n";
        echo "Your webhook requests are missing the X-PointWave-Signature header.\n\n";
        echo "Please add this header to your webhook requests:\n";
        echo "X-PointWave-Signature: [hmac_sha256_signature]\n\n";
        echo "Generate the signature using:\n";
        echo "hash_hmac('sha256', \$payload_json, '{$secretKey}')\n\n";
        echo "Example for test payload '{\"test\":\"webhook\"}':\n";
        echo "Signature should be: $correctSignature\n\n";
        echo "This is the same method used by amtpay and kobopoint.\n\n";
        echo "Please update your webhook implementation and test again.\"\n\n";
    }
    
    // Check recent webhook attempts
    $recentAttempts = \DB::table('gateway_webhook_logs')
        ->where('created_at', '>=', now()->subHours(24))
        ->where('status', 'failed')
        ->whereJsonContains('payload', 'oyitipay')
        ->orWhere('payload', 'like', '%oyitipay%')
        ->count();
    
    echo "📊 RECENT WEBHOOK STATS:\n";
    echo "=======================\n";
    echo "- Failed attempts (24h): $recentAttempts\n";
    echo "- Issue: Missing signature header\n";
    echo "- Status: Needs client-side fix\n\n";
}

echo "✅ DIAGNOSIS COMPLETED\n";
echo "======================\n";
echo "Next step: Contact oyitipay with the signature requirements above.\n";