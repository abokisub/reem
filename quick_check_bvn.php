<?php
// Quick BVN Check Script
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$company = App\Models\Company::find(10);

echo "Company: " . $company->name . "\n";
echo "Director BVN: " . ($company->director_bvn ?? 'NULL') . "\n";
echo "Director NIN: " . ($company->director_nin ?? 'NULL') . "\n";
echo "RC Number: " . ($company->business_registration_number ?? 'NULL') . "\n";
