<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING COMPANY SETTLEMENT ACCOUNTS ===\n\n";

$companies = DB::table('companies')
    ->select('id', 'name', 'settlement_account_number', 'settlement_bank_name', 'bank_code')
    ->get();

foreach ($companies as $company) {
    echo "Company ID: {$company->id}\n";
    echo "Name: {$company->name}\n";
    echo "Settlement Account Number: " . ($company->settlement_account_number ?? 'NOT SET') . "\n";
    echo "Settlement Bank Name: " . ($company->settlement_bank_name ?? 'NOT SET') . "\n";
    echo "Bank Code: " . ($company->bank_code ?? 'NOT SET') . "\n";
    echo "---\n\n";
}

echo "\n=== RECENT TRANSFER (from logs) ===\n";
echo "Account Number: 7040540018\n";
echo "Bank Code: 100004\n";
echo "Bank Name: ABOKI TELECOMMUNICATION SERVICES\n\n";

echo "Checking if this matches any company's settlement account...\n\n";

$match = DB::table('companies')
    ->where('settlement_account_number', '7040540018')
    ->where('bank_code', '100004')
    ->first();

if ($match) {
    echo "✓ MATCH FOUND!\n";
    echo "Company: {$match->name}\n";
    echo "This SHOULD be detected as a settlement withdrawal\n";
} else {
    echo "✗ NO MATCH FOUND\n";
    echo "This will be treated as an external transfer\n";
    echo "\nPossible reasons:\n";
    echo "1. settlement_account_number is not set to 7040540018\n";
    echo "2. bank_code is not set to 100004\n";
    echo "3. One or both fields are NULL\n";
}
