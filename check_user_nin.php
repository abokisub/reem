<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check Company ID 10 (Amtpay)
$company = DB::table('companies')->where('id', 10)->first();
$user = DB::table('users')->where('id', $company->user_id)->first();

echo "Company: {$company->name}\n";
echo "User ID: {$user->id}\n";
echo "User Email: {$user->email}\n";
echo "\n";
echo "=== USERS TABLE ===\n";
echo "User BVN: " . ($user->bvn ?? 'NULL') . "\n";
echo "User NIN: " . ($user->nin ?? 'NULL') . "\n";
echo "\n";
echo "=== COMPANIES TABLE ===\n";
echo "Company BVN: " . ($company->bvn ?? 'NULL') . "\n";
echo "Company NIN: " . ($company->nin ?? 'NULL') . "\n";
echo "Director BVN: " . ($company->director_bvn ?? 'NULL') . "\n";
echo "Director NIN: " . ($company->director_nin ?? 'NULL') . "\n";
echo "\n";

if ($user->nin && !$company->director_nin) {
    echo "⚠️  ISSUE FOUND: User has NIN but company.director_nin is NULL\n";
    echo "This means the NIN was saved to users table but not copied to companies table\n";
} else {
    echo "✅ No issue found\n";
}
