#!/usr/bin/env php
<?php

/**
 * Fix Company BVN - Manually add BVN to company
 * 
 * This script manually adds director_bvn to a company
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;

echo "\n=== Fix Company BVN ===\n\n";

$companyId = $argv[1] ?? 10;
$bvn = $argv[2] ?? '22500464896';

$company = Company::find($companyId);

if (!$company) {
    echo "❌ Company not found (ID: {$companyId})\n\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "Current BVN: " . ($company->director_bvn ?? 'NULL') . "\n";
echo "Current NIN: " . ($company->director_nin ?? 'NULL') . "\n";
echo "Current RC: " . ($company->business_registration_number ?? 'NULL') . "\n\n";

echo "Setting Director BVN to: {$bvn}\n";

try {
    $company->director_bvn = $bvn;
    $company->save();
    
    echo "✅ BVN saved successfully!\n\n";
    
    // Verify
    $company = Company::find($companyId);
    echo "Verification:\n";
    echo "Director BVN: " . ($company->director_bvn ?? 'NULL') . "\n";
    echo "Director NIN: " . ($company->director_nin ?? 'NULL') . "\n";
    echo "RC Number: " . ($company->business_registration_number ?? 'NULL') . "\n\n";
    
    if ($company->director_bvn === $bvn) {
        echo "✅ SUCCESS! BVN is now in the database.\n";
        echo "You can now activate the company from the admin panel.\n";
    } else {
        echo "❌ FAILED! BVN was not saved. Check database schema.\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nThis might mean the 'director_bvn' column doesn't exist in the companies table.\n";
    echo "Run this migration: php artisan migrate\n";
}

echo "\n";
