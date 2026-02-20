<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         SETTINGS TABLE COLUMN CHECKER                      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    // Get all columns from settings table
    $columns = DB::select("SHOW COLUMNS FROM settings");
    
    echo "📋 All columns in settings table:\n";
    echo str_repeat("-", 60) . "\n";
    foreach ($columns as $column) {
        echo "  • {$column->Field} ({$column->Type})\n";
    }
    
    echo "\n🔍 Checking for fee-related columns:\n";
    echo str_repeat("-", 60) . "\n";
    
    $feeColumns = [
        'transfer_charge_type',
        'transfer_charge_value', 
        'transfer_charge_cap',
        'virtual_funding_type',
        'virtual_funding_value',
        'virtual_funding_cap'
    ];
    
    foreach ($feeColumns as $col) {
        $exists = collect($columns)->contains('Field', $col);
        $status = $exists ? "✅ EXISTS" : "❌ MISSING";
        echo "  {$status}: {$col}\n";
    }
    
    // Get current settings values
    echo "\n💰 Current fee configuration values:\n";
    echo str_repeat("-", 60) . "\n";
    
    $settings = DB::table('settings')->first();
    
    if ($settings) {
        foreach ($feeColumns as $col) {
            if (property_exists($settings, $col)) {
                $value = $settings->$col ?? 'NULL';
                echo "  • {$col}: {$value}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
