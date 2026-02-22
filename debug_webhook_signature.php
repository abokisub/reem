<?php

/**
 * Debug Webhook Signature Mismatch
 * 
 * This will show exactly what PointWave is sending vs what Kobopoint expects
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\CompanyWebhookLog;

echo "\n";
echo "================================================================================\n";
echo "WEBHOOK SIGNATURE DEBUG\n";
echo "================================================================================\n";
echo "\n";

// Get Kobopoint company
$company = Company::find(4);

if (!$company) {
    echo "❌ Company not found!\n\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "Webhook URL: {$company->webhook_url}\n";
echo "Webhook Secret: " . ($company->webhook_secret ? substr($company->webhook_secret, 0, 20) . '...' : 'NOT SET') . "\n";
echo "\n";

// Get the most recent failed webhook
$webhook = CompanyWebhookLog::where('company_id', 4)
    ->where('status', 'delivery_failed')
    ->orderBy('created_at', 'desc')
    ->first();

if (!$webhook) {
    echo "❌ No failed webhooks found!\n\n";
    exit(0);
}

echo "Most Recent Failed Webhook:\n";
echo "----------------------------\n";
echo "ID: {$webhook->id}\n";
echo "Event: {$webhook->event_type}\n";
echo "HTTP Status: {$webhook->http_status}\n";
echo "Created: {$webhook->created_at}\n";
echo "\n";

// Recreate what PointWave sent
$payload = json_encode($webhook->payload);
$secret = $company->webhook_secret;

echo "Payload (what PointWave sent):\n";
echo "------------------------------\n";
echo $payload . "\n";
echo "\n";

echo "Payload Length: " . strlen($payload) . " bytes\n";
echo "\n";

// Compute signature the way PointWave does it
$pointwaveSignature = hash_hmac('sha256', $payload, $secret);

echo "Signature Calculation:\n";
echo "----------------------\n";
echo "Secret: " . substr($secret, 0, 20) . "...\n";
echo "Algorithm: HMAC-SHA256\n";
echo "PointWave Signature: {$pointwaveSignature}\n";
echo "With Prefix: sha256={$pointwaveSignature}\n";
echo "\n";

// Show what Kobopoint should expect
echo "What Kobopoint Should Do:\n";
echo "-------------------------\n";
echo "1. Get raw payload: \$payload = file_get_contents('php://input');\n";
echo "2. Get signature: \$sig = \$_SERVER['HTTP_X_POINTWAVE_SIGNATURE'];\n";
echo "3. Strip prefix: \$sig = str_replace('sha256=', '', \$sig);\n";
echo "4. Get secret from .env: \$secret = env('POINTWAVE_WEBHOOK_SECRET');\n";
echo "5. Compute: \$expected = hash_hmac('sha256', \$payload, \$secret);\n";
echo "6. Compare: hash_equals(\$expected, \$sig)\n";
echo "\n";

// Check if Kobopoint's .env has the right secret
echo "Verification:\n";
echo "-------------\n";
echo "✅ PointWave has webhook_secret for company 4\n";
echo "❓ Does Kobopoint's .env have: POINTWAVE_WEBHOOK_SECRET={$secret}\n";
echo "❓ Is Kobopoint using the EXACT same secret?\n";
echo "\n";

echo "Next Steps:\n";
echo "-----------\n";
echo "1. Check Kobopoint's .env file\n";
echo "2. Verify POINTWAVE_WEBHOOK_SECRET matches exactly\n";
echo "3. Make sure there are no extra spaces or quotes\n";
echo "4. Clear config cache: php artisan config:clear\n";
echo "\n";

echo "Test Command for Kobopoint:\n";
echo "---------------------------\n";
echo "Run this on Kobopoint's server:\n";
echo "\n";
echo "php -r \"echo hash_hmac('sha256', '{$payload}', env('POINTWAVE_WEBHOOK_SECRET'));\"" . "\n";
echo "\n";
echo "Expected output: {$pointwaveSignature}\n";
echo "\n";

echo "================================================================================\n";
echo "\n";
