<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Fixing Director NIN for Company ID 10 ===\n\n";

// Get company and user
$company = DB::table('companies')->where('id', 10)->first();
if (!$company) {
    echo "❌ Company not found\n";
    exit(1);
}

$user = DB::table('users')->where('id', $company->user_id)->first();
if (!$user) {
    echo "❌ User not found\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "User: {$user->email}\n\n";

echo "BEFORE:\n";
echo "  User NIN: " . ($user->nin ?? 'NULL') . "\n";
echo "  Company director_nin: " . ($company->director_nin ?? 'NULL') . "\n\n";

// Copy NIN from users to companies if user has NIN but company doesn't
if ($user->nin && !$company->director_nin) {
    DB::table('companies')
        ->where('id', 10)
        ->update([
            'director_nin' => $user->nin,
            'updated_at' => now()
        ]);
    
    echo "✅ FIXED: Copied NIN from users table to companies.director_nin\n\n";
    
    // Verify
    $company = DB::table('companies')->where('id', 10)->first();
    echo "AFTER:\n";
    echo "  Company director_nin: " . ($company->director_nin ?? 'NULL') . "\n";
} else {
    echo "ℹ️  No fix needed:\n";
    if (!$user->nin) {
        echo "  - User doesn't have NIN in users table\n";
    }
    if ($company->director_nin) {
        echo "  - Company already has director_nin\n";
    }
}
