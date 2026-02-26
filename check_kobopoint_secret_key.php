<?php
/**
 * Check what secret_key is stored for Kobopoint in PointWave database
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;

// Kobopoint's actual Secret Key from their dashboard
$actualSecretKey = '2db0097ca80769411bc6d67213c95a61ce427dd7d07a1b6936a82bfa8942c6f99f9ca84bccb9c9d50bd57e05df8948f8b6d0cd3420784a037eebfdaf';

echo "=== CHECKING KOBOPOINT SECRET KEY ===\n\n";

// Try different ways to find Kobopoint
$companies = Company::where('name', 'LIKE', '%kobo%')
    ->orWhere('email', 'LIKE', '%kobo%')
    ->get();

if ($companies->isEmpty()) {
    echo "❌ No companies found matching 'kobo'\n";
    echo "\nLet's check all companies:\n";
    $allCompanies = Company::select('id', 'name', 'email')->get();
    foreach ($allCompanies as $company) {
        echo "  - ID: {$company->id}, Name: {$company->name}, Email: {$company->email}\n";
    }
    exit(1);
}

echo "Found " . $companies->count() . " company(ies) matching 'kobo':\n\n";

foreach ($companies as $company) {
    echo "Company: {$company->name} (ID: {$company->id})\n";
    echo "Email: {$company->email}\n";
    echo "Webhook URL: {$company->webhook_url}\n\n";
    
    echo "Secret Keys in Database:\n";
    echo "  secret_key: " . ($company->secret_key ?: 'NULL') . "\n";
    echo "  api_secret_key: " . ($company->api_secret_key ?: 'NULL') . "\n\n";
    
    echo "Comparison with actual Secret Key:\n";
    if ($company->secret_key === $actualSecretKey) {
        echo "  ✅ secret_key MATCHES Kobopoint's actual Secret Key\n";
    } else {
        echo "  ❌ secret_key DOES NOT MATCH\n";
        echo "     Expected: {$actualSecretKey}\n";
        echo "     Got:      {$company->secret_key}\n";
    }
    
    if ($company->api_secret_key === $actualSecretKey) {
        echo "  ✅ api_secret_key MATCHES Kobopoint's actual Secret Key\n";
    } else {
        echo "  ❌ api_secret_key DOES NOT MATCH\n";
        echo "     Expected: {$actualSecretKey}\n";
        echo "     Got:      {$company->api_secret_key}\n";
    }
    
    echo "\n" . str_repeat("=", 80) . "\n\n";
}

echo "Done!\n";
