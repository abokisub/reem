#!/usr/bin/env php
<?php

/**
 * Check Company BVN Status
 * 
 * This script checks if a company has BVN/NIN/RC Number in the database
 * and shows what KYC will be used for virtual account creation.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;

echo "\n=== Company BVN/KYC Status Checker ===\n\n";

// Get company ID from command line or use default
$companyId = $argv[1] ?? 10;

$company = Company::find($companyId);

if (!$company) {
    echo "❌ Company not found (ID: {$companyId})\n\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "Email: {$company->email}\n";
echo "ID: {$company->id}\n";
echo "Status: {$company->kyc_status}\n\n";

echo "=== KYC Information ===\n";
echo "Director BVN: " . ($company->director_bvn ? "✅ {$company->director_bvn}" : "❌ NULL") . "\n";
echo "Director NIN: " . ($company->director_nin ? "✅ {$company->director_nin}" : "❌ NULL") . "\n";
echo "RC Number: " . ($company->business_registration_number ? "✅ {$company->business_registration_number}" : "❌ NULL") . "\n\n";

// Determine what will be used for virtual account creation
echo "=== Virtual Account Creation Priority ===\n";
if ($company->director_bvn) {
    echo "✅ Will use: Director BVN (HIGHEST PRIORITY - AGGREGATOR MODEL)\n";
    echo "   Identity Type: personal\n";
    echo "   License Number: {$company->director_bvn}\n";
} elseif ($company->director_nin) {
    echo "⚠️  Will use: Director NIN (MEDIUM PRIORITY)\n";
    echo "   Identity Type: personal_nin\n";
    echo "   License Number: {$company->director_nin}\n";
} elseif ($company->business_registration_number) {
    echo "⚠️  Will use: RC Number (FALLBACK - CORPORATE MODE)\n";
    echo "   Identity Type: company\n";
    echo "   License Number: RC-{$company->business_registration_number}\n";
    echo "\n⚠️  WARNING: RC Number verification often fails with PalmPay!\n";
    echo "   Recommendation: Add Director BVN for better success rate.\n";
} else {
    echo "❌ NO KYC AVAILABLE - Virtual account creation will FAIL!\n";
    echo "   Company needs at least one of: BVN, NIN, or RC Number\n";
}

echo "\n=== Recommendation ===\n";
if (!$company->director_bvn) {
    echo "🔧 Action Required: Add Director BVN to this company\n";
    echo "   1. Go to admin panel: app.pointwave.ng/secure/companies/{$company->id}\n";
    echo "   2. Click 'Edit' button\n";
    echo "   3. Add Director BVN in the 'Director/Owner KYC' section\n";
    echo "   4. Save changes\n";
    echo "   5. Try activating company again\n\n";
    echo "   From logs, the BVN appears to be: 22500464896\n";
} else {
    echo "✅ Company has Director BVN - Virtual account creation should work!\n";
}

echo "\n";
