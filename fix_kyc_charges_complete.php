<?php

/**
 * FIX KYC CHARGES SYSTEM
 * 
 * 1. Activate all KYC charges
 * 2. Set reasonable prices
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "FIXING KYC CHARGES SYSTEM\n";
echo "========================================\n\n";

// 1. Activate and update KYC charges
echo "1. Activating KYC charges...\n";
echo str_repeat("-", 60) . "\n";

$kycCharges = [
    'enhanced_bvn' => ['value' => 100, 'is_active' => 1],
    'enhanced_nin' => ['value' => 100, 'is_active' => 1],
    'basic_bvn' => ['value' => 50, 'is_active' => 1],
    'basic_nin' => ['value' => 50, 'is_active' => 1],
    'bank_account_verification' => ['value' => 50, 'is_active' => 1], // Reduced from 120
];

foreach ($kycCharges as $serviceName => $config) {
    $updated = DB::table('service_charges')
        ->where('service_name', $serviceName)
        ->update([
            'charge_value' => $config['value'],
            'is_active' => $config['is_active'],
            'updated_at' => now()
        ]);
    
    if ($updated) {
        echo "✅ Activated: {$serviceName} - ₦{$config['value']}\n";
    } else {
        // Create if doesn't exist
        DB::table('service_charges')->insert([
            'service_name' => $serviceName,
            'charge_type' => 'FLAT',
            'charge_value' => $config['value'],
            'charge_cap' => null,
            'is_active' => $config['is_active'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "✅ Created: {$serviceName} - ₦{$config['value']}\n";
    }
}

echo "\n2. Verifying activation...\n";
echo str_repeat("-", 60) . "\n";

$activeCharges = DB::table('service_charges')
    ->where(function($query) {
        $query->where('service_name', 'LIKE', '%bvn%')
              ->orWhere('service_name', 'LIKE', '%nin%')
              ->orWhere('service_name', 'LIKE', '%verification%');
    })
    ->where('is_active', 1)
    ->get();

echo "✅ Active KYC charges: " . $activeCharges->count() . "\n\n";

foreach ($activeCharges as $charge) {
    echo "  • {$charge->service_name}: ₦{$charge->charge_value}\n";
}

echo "\n========================================\n";
echo "✅ KYC CHARGES ACTIVATED!\n";
echo "========================================\n\n";

echo "Next steps:\n";
echo "1. Update KycService.php to deduct charges\n";
echo "2. Test with real verification\n";
echo "3. Deploy to production\n\n";
