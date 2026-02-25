<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Fixing Director BVN/NIN for All Companies ===\n\n";
echo "This script copies BVN/NIN from users table to companies.director_bvn/director_nin\n";
echo "for companies that are missing this data.\n\n";

// Get all companies with users
$companies = DB::table('companies')
    ->join('users', 'companies.user_id', '=', 'users.id')
    ->select(
        'companies.id as company_id',
        'companies.name as company_name',
        'companies.director_bvn',
        'companies.director_nin',
        'users.id as user_id',
        'users.email',
        'users.bvn as user_bvn',
        'users.nin as user_nin'
    )
    ->get();

$fixedCount = 0;
$skippedCount = 0;

foreach ($companies as $record) {
    $needsFix = false;
    $updates = [];
    
    // Check if user has BVN but company doesn't have director_bvn
    if ($record->user_bvn && !$record->director_bvn) {
        $updates['director_bvn'] = $record->user_bvn;
        $needsFix = true;
    }
    
    // Check if user has NIN but company doesn't have director_nin
    if ($record->user_nin && !$record->director_nin) {
        $updates['director_nin'] = $record->user_nin;
        $needsFix = true;
    }
    
    if ($needsFix) {
        $updates['updated_at'] = now();
        
        DB::table('companies')
            ->where('id', $record->company_id)
            ->update($updates);
        
        echo "✅ Fixed Company ID {$record->company_id} ({$record->company_name}):\n";
        if (isset($updates['director_bvn'])) {
            echo "   - Copied BVN: {$updates['director_bvn']}\n";
        }
        if (isset($updates['director_nin'])) {
            echo "   - Copied NIN: {$updates['director_nin']}\n";
        }
        echo "\n";
        
        $fixedCount++;
    } else {
        $skippedCount++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total companies: " . count($companies) . "\n";
echo "Fixed: {$fixedCount}\n";
echo "Skipped (no fix needed): {$skippedCount}\n";
