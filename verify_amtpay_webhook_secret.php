<?php

/**
 * Verify Amtpay's Webhook Secret
 * Run this on PointWave LIVE server
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Amtpay Webhook Secret Verification ===\n\n";

// Get Amtpay company (ID: 10)
$company = DB::table('companies')->where('id', 10)->first();

if (!$company) {
    echo "ERROR: Company ID 10 (Amtpay) not found!\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "Email: {$company->email}\n";
echo "Webhook Secret: {$company->webhook_secret}\n";
echo "Secret Length: " . strlen($company->webhook_secret) . "\n\n";

// Test with the actual payload from their logs
$payload = '{"event":"payment.success","event_id":"354f528a-ed80-44f6-bb96-2249871842d3","timestamp":"2026-02-25T14:29:32+01:00","data":{"transaction_id":"txn_699ef93ca6ddd70574","amount":"100.00","fee":"0.60","net_amount":"99.40","reference":"REF699EF93CA6DF38380","status":"success","customer":{"account_number":"6604877046","sender_name":"ABOKI TELECOMMUNICATION SERVICES","sender_account":"7040540018","sender_bank":"OPAY"},"narration":"Transfer from ABOKI TELECOMMUNICATION SERVICES","created_at":"2026-02-25T14:29:32+01:00"}}';

$pointwaveSentSignature = '978ad8405b2f656b19d9f6da39512052618733d75e7d99e5874d273b1fc3ad39';
$amtpayCalculatedSignature = '105e7b5b3a3cfc708338ac1066e906d66e1003e33bc32530732d6386c94cf799';

echo "=== Signature Test ===\n";
echo "Payload length: " . strlen($payload) . "\n";
echo "PointWave sent: {$pointwaveSentSignature}\n";
echo "Amtpay calculated: {$amtpayCalculatedSignature}\n\n";

// Calculate what signature we would send
$ourCalculatedSignature = hash_hmac('sha256', $payload, $company->webhook_secret);
echo "Our calculation with DB secret: {$ourCalculatedSignature}\n\n";

if ($ourCalculatedSignature === $pointwaveSentSignature) {
    echo "✅ SUCCESS: Our calculation matches what PointWave sent!\n";
    echo "   The webhook secret in our database is CORRECT.\n";
    echo "   Amtpay needs to use: {$company->webhook_secret}\n";
} else {
    echo "❌ MISMATCH: Our calculation doesn't match!\n";
    echo "   This means either:\n";
    echo "   1. The webhook secret in our database is wrong\n";
    echo "   2. PointWave is using a different secret when sending\n";
    echo "   3. The payload was modified somehow\n\n";
    
    // Try to reverse-engineer what secret would produce the signature PointWave sent
    echo "   Checking if Amtpay's secret would work...\n";
    $amtpaySecret = 'whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24';
    $testSig = hash_hmac('sha256', $payload, $amtpaySecret);
    echo "   With Amtpay's .env secret: {$testSig}\n";
    
    if ($testSig === $pointwaveSentSignature) {
        echo "   ✅ Amtpay's secret from .env is CORRECT!\n";
        echo "   Problem: Our database has wrong secret for Amtpay\n";
    } elseif ($testSig === $amtpayCalculatedSignature) {
        echo "   ✅ Amtpay is calculating correctly with their secret\n";
        echo "   Problem: PointWave is sending with different secret\n";
    }
}

echo "\n=== Action Required ===\n";
echo "Tell Amtpay to use this webhook secret:\n";
echo $company->webhook_secret . "\n";

