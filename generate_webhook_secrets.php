<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;

echo "========================================\n";
echo "Generate Webhook Secrets for All Companies\n";
echo "========================================\n\n";

$companies = Company::whereNull('webhook_secret')
    ->orWhereNull('test_webhook_secret')
    ->get();

if ($companies->isEmpty()) {
    echo "✅ All companies already have webhook secrets!\n";
    exit(0);
}

echo "Found " . $companies->count() . " company(ies) without webhook secrets\n\n";

foreach ($companies as $company) {
    echo "[{$company->id}] {$company->company_name} ({$company->email})\n";
    
    $updates = [];
    
    if (!$company->webhook_secret) {
        $updates['webhook_secret'] = 'whsec_' . bin2hex(random_bytes(32));
        echo "  ✅ Generated live webhook secret\n";
    }
    
    if (!$company->test_webhook_secret) {
        $updates['test_webhook_secret'] = 'whsec_test_' . bin2hex(random_bytes(32));
        echo "  ✅ Generated test webhook secret\n";
    }
    
    if (!empty($updates)) {
        $company->update($updates);
    }
    
    echo "\n";
}

echo "========================================\n";
echo "✅ Webhook secrets generated successfully!\n";
echo "========================================\n\n";

echo "Next steps:\n";
echo "1. Companies can view their webhook secrets in dashboard\n";
echo "2. Webhook signatures will be sent automatically\n";
echo "3. Companies should verify signatures for security\n\n";
