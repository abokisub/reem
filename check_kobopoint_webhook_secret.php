<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;

echo "========================================\n";
echo "Check KoboPoint Webhook Secret\n";
echo "========================================\n\n";

$company = Company::where('email', 'kobopointng@gmail.com')->first();

if (!$company) {
    echo "❌ KoboPoint company not found\n";
    exit(1);
}

echo "Company: {$company->company_name}\n";
echo "Email: {$company->email}\n";
echo "ID: {$company->id}\n\n";

echo "Webhook Configuration:\n";
echo "- webhook_url: " . ($company->webhook_url ?? 'NULL') . "\n";
echo "- webhook_secret: " . ($company->webhook_secret ?? 'NULL') . "\n";
echo "- webhook_enabled: " . ($company->webhook_enabled ? 'Yes' : 'No') . "\n\n";

if (!$company->webhook_secret) {
    echo "⚠️  No webhook secret found!\n\n";
    echo "Generating webhook secret...\n";
    
    $newSecret = 'whsec_' . bin2hex(random_bytes(32));
    
    $company->update([
        'webhook_secret' => $newSecret
    ]);
    
    echo "✅ Generated webhook secret: $newSecret\n\n";
    echo "⚠️  IMPORTANT: Share this secret with KoboPoint securely!\n";
} else {
    echo "✅ Webhook secret exists\n";
    echo "Secret: {$company->webhook_secret}\n";
}

echo "\n========================================\n";
