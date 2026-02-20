<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         TRACE VA DEPOSIT FEE CALCULATION                   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    // Get settings table data
    echo "📊 STEP 1: Check settings table columns\n";
    echo str_repeat("-", 60) . "\n";
    
    $settings = DB::table('settings')->first();
    
    $feeColumns = [
        'transfer_charge_type',
        'transfer_charge_value', 
        'transfer_charge_cap',
        'virtual_funding_type',
        'virtual_funding_value',
        'virtual_funding_cap'
    ];
    
    foreach ($feeColumns as $col) {
        if (property_exists($settings, $col)) {
            $value = $settings->$col ?? 'NULL';
            echo "  ✅ {$col}: {$value}\n";
        } else {
            echo "  ❌ {$col}: COLUMN DOES NOT EXIST\n";
        }
    }
    
    // Check FeeService logic
    echo "\n🔍 STEP 2: Simulate FeeService logic\n";
    echo str_repeat("-", 60) . "\n";
    
    $transactionType = 'va_deposit';
    
    // This is what FeeService currently does
    $typeMap = [
        'va_deposit' => 'virtual_funding',
        'transfer' => 'transfer_charge',
    ];
    
    $settingsKey = $typeMap[$transactionType] ?? 'virtual_funding';
    echo "  • Transaction Type: {$transactionType}\n";
    echo "  • Settings Key: {$settingsKey}\n";
    
    $typeColumn = $settingsKey . '_type';
    $valueColumn = $settingsKey . '_value';
    $capColumn = $settingsKey . '_cap';
    
    echo "  • Looking for columns:\n";
    echo "    - {$typeColumn}\n";
    echo "    - {$valueColumn}\n";
    echo "    - {$capColumn}\n";
    
    // Check if columns exist
    if (property_exists($settings, $typeColumn)) {
        echo "  ✅ Found {$typeColumn}: " . ($settings->$typeColumn ?? 'NULL') . "\n";
    } else {
        echo "  ❌ Column {$typeColumn} does NOT exist\n";
        echo "  ⚠️  FeeService will use FALLBACK: 0.5%\n";
    }
    
    if (property_exists($settings, $valueColumn)) {
        echo "  ✅ Found {$valueColumn}: " . ($settings->$valueColumn ?? 'NULL') . "\n";
    } else {
        echo "  ❌ Column {$valueColumn} does NOT exist\n";
        echo "  ⚠️  FeeService will use FALLBACK: 0.5%\n";
    }
    
    // Show the fallback code
    echo "\n💡 STEP 3: FeeService Fallback Logic\n";
    echo str_repeat("-", 60) . "\n";
    echo "  When columns don't exist, FeeService returns:\n";
    echo "  [\n";
    echo "    'model' => 'hardcoded_fallback',\n";
    echo "    'type' => 'PERCENT',\n";
    echo "    'value' => 0.5,  ← THIS IS WHERE 0.5% COMES FROM\n";
    echo "    'cap' => 500\n";
    echo "  ]\n";
    
    // Show what admin panel uses
    echo "\n🎯 STEP 4: What Admin Panel Uses\n";
    echo str_repeat("-", 60) . "\n";
    echo "  Admin panel at /secure/discount/banks reads:\n";
    echo "  • transfer_charge_type: " . ($settings->transfer_charge_type ?? 'NULL') . "\n";
    echo "  • transfer_charge_value: " . ($settings->transfer_charge_value ?? 'NULL') . "\n";
    echo "  • transfer_charge_cap: " . ($settings->transfer_charge_cap ?? 'NULL') . "\n";
    
    // Show the mismatch
    echo "\n❌ STEP 5: THE MISMATCH\n";
    echo str_repeat("-", 60) . "\n";
    echo "  Admin Panel Updates: transfer_charge_* columns\n";
    echo "  FeeService Reads: virtual_funding_* columns (DON'T EXIST)\n";
    echo "  Result: FeeService can't find columns → uses 0.5% fallback\n";
    
    // Show the solution
    echo "\n✅ STEP 6: THE SOLUTION\n";
    echo str_repeat("-", 60) . "\n";
    echo "  Change FeeService to read transfer_charge_* for va_deposit\n";
    echo "  This matches what the admin panel updates!\n";
    echo "\n  In FeeService.php line ~70:\n";
    echo "  Change:\n";
    echo "    'va_deposit' => 'virtual_funding',\n";
    echo "  To:\n";
    echo "    'va_deposit' => 'transfer_charge',\n";
    
    // Test with actual amount
    echo "\n🧪 STEP 7: Test Calculation\n";
    echo str_repeat("-", 60) . "\n";
    $amount = 10000; // ₦100.00
    
    echo "  Deposit Amount: ₦" . number_format($amount/100, 2) . "\n\n";
    
    echo "  CURRENT (Wrong - using 0.5% fallback):\n";
    $currentFee = ($amount * 0.5) / 100;
    $currentNet = $amount - $currentFee;
    echo "    Fee: ₦" . number_format($currentFee/100, 2) . " (0.5%)\n";
    echo "    Net: ₦" . number_format($currentNet/100, 2) . "\n\n";
    
    if ($settings->transfer_charge_type === 'PERCENT') {
        $correctValue = (float) $settings->transfer_charge_value;
        echo "  CORRECT (After fix - using {$correctValue}% from admin panel):\n";
        $correctFee = ($amount * $correctValue) / 100;
        $correctNet = $amount - $correctFee;
        echo "    Fee: ₦" . number_format($correctFee/100, 2) . " ({$correctValue}%)\n";
        echo "    Net: ₦" . number_format($correctNet/100, 2) . "\n";
    } elseif ($settings->transfer_charge_type === 'FLAT') {
        $correctValue = (float) $settings->transfer_charge_value;
        echo "  CORRECT (After fix - using ₦{$correctValue} flat from admin panel):\n";
        $correctFee = $correctValue * 100; // Convert to kobo
        $correctNet = $amount - $correctFee;
        echo "    Fee: ₦" . number_format($correctFee/100, 2) . " (FLAT)\n";
        echo "    Net: ₦" . number_format($correctNet/100, 2) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
