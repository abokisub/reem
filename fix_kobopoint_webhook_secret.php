<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Fix KoboPoint Webhook Secret\n";
echo "========================================\n\n";

// Find KoboPoint by email
$company = DB::table('companies')
    ->where('email', 'kobopointng@gmail.com')
    ->first();

if (!$company) {
    echo "❌ KoboPoint company not found\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "Email: {$company->email}\n";
echo "ID: {$company->id}\n\n";

echo "Current webhook secrets:\n";
echo "- webhook_secret: " . ($company->webhook_secret ? 'EXISTS (possibly corrupted)' : 'NULL') . "\n";
echo "- test_webhook_secret: " . ($company->test_webhook_secret ? 'EXISTS (possibly corrupted)' : 'NULL') . "\n\n";

// Force regenerate webhook secrets
$liveSecret = 'whsec_' . bin2hex(random_bytes(32));
$testSecret = 'whsec_test_' . bin2hex(random_bytes(32));

DB::table('companies')
    ->where('id', $company->id)
    ->update([
        'webhook_secret' => $liveSecret,
        'test_webhook_secret' => $testSecret,
    ]);

echo "✅ Generated new webhook secrets!\n\n";
echo "Live Secret: $liveSecret\n";
echo "Test Secret: $testSecret\n\n";

echo "========================================\n";
echo "Next Steps:\n";
echo "========================================\n";
echo "1. KoboPoint should refresh their Developer API page\n";
echo "2. They'll see the webhook secrets with copy buttons\n";
echo "3. They should copy the Live Secret and add to their code\n\n";
