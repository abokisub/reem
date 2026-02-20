<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    VERIFY VA DEPOSIT FEE CONFIGURATION                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    $settings = DB::table('settings')->first();
    
    if (!$settings) {
        echo "❌ ERROR: Settings table is empty\n";
        exit(1);
    }
    
    echo "📊 CURRENT FEE CONFIGURATION\n";
    echo str_repeat("-", 60) . "\n\n";
    
    // Check if virtual_funding columns exist
    echo "1️⃣  Virtual Funding Columns (for VA deposits):\n";
    echo str_repeat("-", 60) . "\n";
    
    if (property_exists($settings, 'virtual_funding_type')) {
        echo "  ✅ virtual_funding_type: {$settings->virtual_funding_type}\n";
        echo "  ✅ virtual_funding_value: {$settings->virtual_funding_value}\n";
        echo "  ✅ virtual_funding_cap: {$settings->virtual_funding_cap}\n";
        
        $vfExists = true;
    } else {
        echo "  ❌ virtual_funding columns DON'T EXIST\n";
        echo "  ⚠️  Migration not run yet!\n";
        $vfExists = false;
    }
    
    echo "\n2️⃣  Transfer Charge Columns (admin panel updates):\n";
    echo str_repeat("-", 60) . "\n";
    echo "  Type: {$settings->transfer_charge_type}\n";
    echo "  Value: {$settings->transfer_charge_value}\n";
    echo "  Cap: {$settings->transfer_charge_cap}\n";
    
    echo "\n";
    
    // Test fee calculation
    if ($vfExists) {
        echo "🧪 TEST FEE CALCULATION\n";
        echo str_repeat("-", 60) . "\n";
        
        $testAmount = 10000; // ₦100.00 in kobo
        
        echo "Test deposit: ₦" . number_format($testAmount/100, 2) . "\n\n";
        
        // Simulate FeeService calculation
        $type = $settings->virtual_funding_type;
        $value = (float) $settings->virtual_funding_value;
        $cap = (float) $settings->virtual_funding_cap;
        
        if ($type === 'FLAT') {
            $fee = $value * 100; // Convert to kobo
            $feeDisplay = "₦" . number_format($value, 2);
        } elseif ($type === 'PERCENT' || $type === 'PERCENTAGE') {
            $fee = ($value / 100) * $testAmount;
            if ($cap > 0 && $fee > ($cap * 100)) {
                $fee = $cap * 100;
            }
            $feeDisplay = "{$value}%";
        } else {
            $fee = 0;
            $feeDisplay = "Unknown type";
        }
        
        $net = $testAmount - $fee;
        
        echo "  Fee Type: {$type}\n";
        echo "  Fee Value: {$feeDisplay}\n";
        echo "  Calculated Fee: ₦" . number_format($fee/100, 2) . "\n";
        echo "  Net Amount: ₦" . number_format($net/100, 2) . "\n";
        
        echo "\n";
        
        // Check if it matches what you set
        echo "✅ VERIFICATION\n";
        echo str_repeat("-", 60) . "\n";
        
        if ($settings->virtual_funding_type === $settings->transfer_charge_type &&
            $settings->virtual_funding_value == $settings->transfer_charge_value) {
            echo "✅ virtual_funding_* matches transfer_charge_*\n";
            echo "✅ Admin panel updates are synced correctly\n";
            echo "✅ VA deposits will use: {$type} {$feeDisplay}\n";
        } else {
            echo "⚠️  virtual_funding_* does NOT match transfer_charge_*\n";
            echo "⚠️  They might be out of sync\n";
        }
        
    } else {
        echo "⚠️  MIGRATION NEEDED\n";
        echo str_repeat("-", 60) . "\n";
        echo "Run: php artisan migrate\n";
        echo "This will create the virtual_funding_* columns\n";
    }
    
    echo "\n";
    echo "📝 WHAT YOU SET IN ADMIN PANEL\n";
    echo str_repeat("-", 60) . "\n";
    echo "Location: /secure/discount/banks → Funding with Bank Transfer\n";
    echo "Type: {$settings->transfer_charge_type}\n";
    echo "Value: {$settings->transfer_charge_value}\n";
    echo "Cap: {$settings->transfer_charge_cap}\n";
    
    if ($vfExists) {
        echo "\n✅ This will be used for VA deposit fees!\n";
    } else {
        echo "\n❌ Migration needed before this takes effect!\n";
    }
    
    echo "\n";
    echo "🎯 NEXT STEPS\n";
    echo str_repeat("-", 60) . "\n";
    
    if (!$vfExists) {
        echo "1. Run: php artisan migrate\n";
        echo "2. Run: php artisan cache:clear\n";
        echo "3. Run this script again to verify\n";
        echo "4. Test with a new VA deposit\n";
    } else {
        echo "✅ Configuration is ready!\n";
        echo "1. Make a test VA deposit\n";
        echo "2. Check the fee charged\n";
        echo "3. It should match: {$settings->virtual_funding_type} {$settings->virtual_funding_value}\n";
        echo "\nTo monitor: tail -f storage/logs/laravel.log | grep 'Virtual Account Credited'\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
