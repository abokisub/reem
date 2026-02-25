<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Check Company Data\n";
echo "========================================\n\n";

$companies = DB::table('companies')
    ->where('email', '!=', 'admin@pointwave.com')
    ->get();

foreach ($companies as $company) {
    echo "Company: {$company->name} (ID: {$company->id})\n";
    echo "Email: {$company->email}\n";
    echo "Director BVN: " . ($company->director_bvn ?: 'NOT SET') . "\n";
    echo "Director NIN: " . ($company->director_nin ?: 'NOT SET') . "\n";
    echo "RC Number: " . ($company->business_registration_number ?: 'NOT SET') . "\n";
    echo "Bank Name: " . ($company->bank_name ?: 'NOT SET') . "\n";
    echo "Bank Code: " . ($company->bank_code ?: 'NOT SET') . "\n";
    echo "Account Number: " . ($company->account_number ?: 'NOT SET') . "\n";
    echo "Account Name: " . ($company->account_name ?: 'NOT SET') . "\n";
    echo "KYC Status: {$company->kyc_status}\n";
    echo "Is Active: " . ($company->is_active ? 'Yes' : 'No') . "\n";
    echo "----------------------------------------\n\n";
}

echo "Total Companies: " . count($companies) . "\n";
