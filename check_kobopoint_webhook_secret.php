<?php

/**
 * Check Kobopoint's Webhook Secret Format
 * Run this on PointWave LIVE server
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use Illuminate\Support\Facades\DB;

echo "=== Checking All Companies' Webhook Secrets ===\n\n";

// Get all active companies
$companies = Company::whereNotNull('webhook_secret')
    ->where('is_active', true)
    ->get(['id', 'name', 'email', 'webhook_secret']);

foreach ($companies as $company) {
    echo "Company ID: {$company->id}\n";
    echo "Name: {$company->name}\n";
    echo "Email: {$company->email}\n";
    
    // Get from model (auto-decrypted)
    $modelSecret = $company->webhook_secret;
    
    // Get raw from DB
    $rawSecret = DB::table('companies')->where('id', $company->id)->value('webhook_secret');
    
    echo "Model secret (first 20 chars): " . substr($modelSecret, 0, 20) . "...\n";
    echo "Model secret length: " . strlen($modelSecret) . "\n";
    
    // Check if it's serialized
    if (is_string($modelSecret) && (strpos($modelSecret, 's:') === 0 || strpos($modelSecret, 'a:') === 0)) {
        echo "⚠️  SECRET IS SERIALIZED!\n";
        $unserialized = unserialize($modelSecret);
        echo "Unserialized (first 20 chars): " . substr($unserialized, 0, 20) . "...\n";
        echo "Unserialized length: " . strlen($unserialized) . "\n";
    } else {
        echo "✅ SECRET IS PLAIN TEXT\n";
    }
    
    echo "\n" . str_repeat('-', 50) . "\n\n";
}

echo "\n=== RECOMMENDATION ===\n";
echo "If ANY company has serialized secrets, we need the unserialization fix.\n";
echo "If ALL companies have plain text secrets, the fix won't hurt but may not be needed.\n";
