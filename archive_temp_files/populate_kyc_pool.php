<?php

/**
 * Script to populate the Global KYC Pool with test BVN and NIN entries
 * 
 * Usage: php populate_kyc_pool.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\GlobalKycPool;

echo "========================================\n";
echo "Global KYC Pool Population Script\n";
echo "========================================\n\n";

// Generate test NIN entries (11 digits)
$testNins = [
    '12345678901',
    '23456789012',
    '34567890123',
    '45678901234',
    '56789012345',
    '67890123456',
    '78901234567',
    '89012345678',
    '90123456789',
    '01234567890',
];

// Generate test BVN entries (11 digits)
$testBvns = [
    '11111111111',
    '22222222222',
    '33333333333',
    '44444444444',
    '55555555555',
    '66666666666',
    '77777777777',
    '88888888888',
    '99999999999',
    '10101010101',
];

$added = 0;
$skipped = 0;

echo "Adding NIN entries...\n";
foreach ($testNins as $nin) {
    $exists = GlobalKycPool::where('kyc_number', $nin)->exists();
    if ($exists) {
        echo "  ⏭️  Skipped NIN: $nin (already exists)\n";
        $skipped++;
        continue;
    }
    
    GlobalKycPool::create([
        'kyc_type'      => 'nin',
        'kyc_number'    => $nin,
        'is_active'     => true,
        'usage_count'   => 0,
        'success_count' => 0,
        'failure_count' => 0,
        'max_usage'     => 130,
    ]);
    
    echo "  ✅ Added NIN: $nin\n";
    $added++;
}

echo "\nAdding BVN entries...\n";
foreach ($testBvns as $bvn) {
    $exists = GlobalKycPool::where('kyc_number', $bvn)->exists();
    if ($exists) {
        echo "  ⏭️  Skipped BVN: $bvn (already exists)\n";
        $skipped++;
        continue;
    }
    
    GlobalKycPool::create([
        'kyc_type'      => 'bvn',
        'kyc_number'    => $bvn,
        'is_active'     => true,
        'usage_count'   => 0,
        'success_count' => 0,
        'failure_count' => 0,
        'max_usage'     => 130,
    ]);
    
    echo "  ✅ Added BVN: $bvn\n";
    $added++;
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "✅ Added: $added entries\n";
echo "⏭️  Skipped: $skipped entries (duplicates)\n";
echo "\nTotal pool entries: " . GlobalKycPool::count() . "\n";
echo "  - NIns: " . GlobalKycPool::where('kyc_type', 'nin')->count() . "\n";
echo "  - BVNs: " . GlobalKycPool::where('kyc_type', 'bvn')->count() . "\n";
echo "  - Available: " . GlobalKycPool::available()->count() . "\n";
echo "\n✅ Pool populated successfully!\n";
echo "========================================\n";
