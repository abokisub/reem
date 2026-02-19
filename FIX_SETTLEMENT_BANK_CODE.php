<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FIXING SETTLEMENT BANK CODE ===\n\n";

// Find company with settlement account 7040540018
$company = DB::table('companies')
    ->where('settlement_account_number', '7040540018')
    ->first();

if (!$company) {
    echo "ERROR: Company with settlement account 7040540018 not found!\n";
    exit(1);
}

echo "Found Company:\n";
echo "ID: {$company->id}\n";
echo "Name: {$company->name}\n";
echo "Settlement Account: {$company->settlement_account_number}\n";
echo "Current Bank Code: " . ($company->bank_code ?? 'NULL') . "\n\n";

echo "Updating bank_code to '100004'...\n";

$updated = DB::table('companies')
    ->where('id', $company->id)
    ->update(['bank_code' => '100004']);

if ($updated) {
    echo "✓ SUCCESS! Bank code updated.\n\n";
    
    // Verify the update
    $verifyCompany = DB::table('companies')
        ->where('id', $company->id)
        ->first();
    
    echo "Verification:\n";
    echo "Settlement Account: {$verifyCompany->settlement_account_number}\n";
    echo "Bank Code: {$verifyCompany->bank_code}\n\n";
    
    echo "Now when you transfer to account 7040540018 with bank code 100004,\n";
    echo "the system will detect it as a Settlement Withdrawal and charge ₦15 fee.\n";
} else {
    echo "✗ FAILED to update bank code.\n";
}
