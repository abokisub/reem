<?php

/**
 * Get Decrypted Webhook Secret for Amtpay
 * Run this on PointWave LIVE server
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;

echo "=== Getting Decrypted Webhook Secret for Amtpay ===\n\n";

// Get Amtpay company (ID: 10) using the Model
// This will automatically decrypt the webhook_secret
$company = Company::find(10);

if (!$company) {
    echo "ERROR: Company ID 10 (Amtpay) not found!\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "Email: {$company->email}\n\n";

echo "=== DECRYPTED WEBHOOK SECRET ===\n";
echo $company->webhook_secret . "\n\n";

echo "=== INSTRUCTIONS FOR AMTPAY ===\n";
echo "1. Copy the webhook secret above\n";
echo "2. Update your .env file:\n";
echo "   POINTWAVE_WEBHOOK_SECRET=" . $company->webhook_secret . "\n\n";

echo "3. Fix your signature calculation:\n";
echo "   CHANGE: \$payload = json_encode(\$request->all());\n";
echo "   TO:     \$payload = \$request->getContent();\n\n";

echo "4. Remove the 'BYPASSING signature check' code\n\n";

// Test with actual payload
$payload = '{"event":"payment.success","event_id":"354f528a-ed80-44f6-bb96-2249871842d3","timestamp":"2026-02-25T14:29:32+01:00","data":{"transaction_id":"txn_699ef93ca6ddd70574","amount":"100.00","fee":"0.60","net_amount":"99.40","reference":"REF699EF93CA6DF38380","status":"success","customer":{"account_number":"6604877046","sender_name":"ABOKI TELECOMMUNICATION SERVICES","sender_account":"7040540018","sender_bank":"OPAY"},"narration":"Transfer from ABOKI TELECOMMUNICATION SERVICES","created_at":"2026-02-25T14:29:32+01:00"}}';

$correctSignature = hash_hmac('sha256', $payload, $company->webhook_secret);

echo "=== VERIFICATION TEST ===\n";
echo "Using the decrypted secret, the signature for the test payload should be:\n";
echo $correctSignature . "\n\n";

echo "PointWave sent: 978ad8405b2f656b19d9f6da39512052618733d75e7d99e5874d273b1fc3ad39\n";
echo "Our calculation: {$correctSignature}\n\n";

if ($correctSignature === '978ad8405b2f656b19d9f6da39512052618733d75e7d99e5874d273b1fc3ad39') {
    echo "✅ SUCCESS! This is the correct webhook secret!\n";
} else {
    echo "⚠️  Signatures don't match. There may be another issue.\n";
}
