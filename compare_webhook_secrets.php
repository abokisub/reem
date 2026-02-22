<?php

/**
 * Compare Webhook Secrets Between PointWave and Kobopoint
 * 
 * This script checks if both systems are using the same webhook secret
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;

echo "\n";
echo "================================================================================\n";
echo "WEBHOOK SECRET COMPARISON\n";
echo "================================================================================\n";
echo "\n";

// Get Kobopoint company from PointWave database
$company = Company::find(4);

if (!$company) {
    echo "❌ Company ID 4 (Kobopoint) not found in PointWave database!\n\n";
    exit(1);
}

echo "Company Details:\n";
echo "----------------\n";
echo "ID: {$company->id}\n";
echo "Name: {$company->name}\n";
echo "Webhook URL: {$company->webhook_url}\n";
echo "\n";

// Get webhook secrets
$liveSecret = $company->webhook_secret;
$testSecret = $company->test_webhook_secret;

echo "PointWave Webhook Secrets:\n";
echo "--------------------------\n";
echo "Live Secret: " . ($liveSecret ? $liveSecret : 'NOT SET') . "\n";
echo "Test Secret: " . ($testSecret ? $testSecret : 'NOT SET') . "\n";
echo "\n";

if (!$liveSecret) {
    echo "❌ ERROR: No webhook secret configured for Kobopoint in PointWave!\n";
    echo "\n";
    echo "To fix this, run:\n";
    echo "  php generate_webhook_secrets.php\n";
    echo "\n";
    exit(1);
}

echo "================================================================================\n";
echo "NEXT STEPS\n";
echo "================================================================================\n";
echo "\n";
echo "1. Copy the Live Secret above\n";
echo "\n";
echo "2. SSH into Kobopoint server and check .env file:\n";
echo "   grep POINTWAVE_WEBHOOK_SECRET /path/to/kobopoint/.env\n";
echo "\n";
echo "3. Verify it matches EXACTLY:\n";
echo "   POINTWAVE_WEBHOOK_SECRET={$liveSecret}\n";
echo "\n";
echo "4. If it doesn't match, update Kobopoint's .env:\n";
echo "   echo 'POINTWAVE_WEBHOOK_SECRET={$liveSecret}' >> .env\n";
echo "\n";
echo "5. Clear Kobopoint's config cache:\n";
echo "   php artisan config:clear\n";
echo "\n";
echo "6. Retry webhooks from PointWave:\n";
echo "   php retry_failed_company_webhooks.php\n";
echo "\n";

echo "================================================================================\n";
echo "DEBUGGING SIGNATURE MISMATCH\n";
echo "================================================================================\n";
echo "\n";
echo "If secrets match but signatures still don't match, the issue is:\n";
echo "\n";
echo "❌ PointWave and Kobopoint are hashing DIFFERENT payloads\n";
echo "\n";
echo "This happens when:\n";
echo "  - JSON encoding produces different formatting\n";
echo "  - Payload is modified in transit (proxy, load balancer)\n";
echo "  - One system uses json_encode(), other uses raw body\n";
echo "\n";
echo "Solution:\n";
echo "  - PointWave must send: hash_hmac('sha256', json_encode(\$payload), \$secret)\n";
echo "  - Kobopoint must compute: hash_hmac('sha256', file_get_contents('php://input'), \$secret)\n";
echo "  - Both must use the EXACT same JSON string\n";
echo "\n";

echo "Run this to see exact payload being sent:\n";
echo "  php debug_webhook_signature.php\n";
echo "\n";

echo "================================================================================\n";
echo "\n";
