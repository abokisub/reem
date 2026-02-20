<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    LIVE SERVER - FEE CONFIGURATION ANALYSIS                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Get all columns from settings table
$columns = DB::select("SHOW COLUMNS FROM settings");

echo "📋 ALL FEE-RELATED COLUMNS IN SETTINGS TABLE:\n";
echo str_repeat("-", 60) . "\n";

$feeRelatedColumns = [];
foreach ($columns as $column) {
    if (strpos($column->Field, 'charge') !== false || 
        strpos($column->Field, 'funding') !== false ||
        strpos($column->Field, 'payout') !== false) {
        $feeRelatedColumns[] = $column->Field;
        echo "  ✅ {$column->Field} ({$column->Type})\n";
    }
}

// Get current values
echo "\n💰 CURRENT FEE VALUES:\n";
echo str_repeat("-", 60) . "\n";

$settings = DB::table('settings')->first();

echo "\n1️⃣  Funding with Bank Transfer (Admin Panel):\n";
if (property_exists($settings, 'transfer_charge_type')) {
    echo "   transfer_charge_type: {$settings->transfer_charge_type}\n";
    echo "   transfer_charge_value: {$settings->transfer_charge_value}\n";
    echo "   transfer_charge_cap: {$settings->transfer_charge_cap}\n";
} else {
    echo "   ❌ transfer_charge_* columns don't exist\n";
}

echo "\n2️⃣  Internal Transfer/Wallet (Admin Panel):\n";
if (property_exists($settings, 'wallet_charge_type')) {
    echo "   wallet_charge_type: {$settings->wallet_charge_type}\n";
    echo "   wallet_charge_value: {$settings->wallet_charge_value}\n";
    echo "   wallet_charge_cap: {$settings->wallet_charge_cap}\n";
} else {
    echo "   ❌ wallet_charge_* columns don't exist\n";
}

echo "\n3️⃣  Settlement Withdrawal/PalmPay (Admin Panel):\n";
if (property_exists($settings, 'payout_palmpay_charge_type')) {
    echo "   payout_palmpay_charge_type: {$settings->payout_palmpay_charge_type}\n";
    echo "   payout_palmpay_charge_value: {$settings->payout_palmpay_charge_value}\n";
    echo "   payout_palmpay_charge_cap: {$settings->payout_palmpay_charge_cap}\n";
} else {
    echo "   ❌ payout_palmpay_charge_* columns don't exist\n";
}

echo "\n4️⃣  External Transfer/Other Banks (Admin Panel):\n";
if (property_exists($settings, 'payout_bank_charge_type')) {
    echo "   payout_bank_charge_type: {$settings->payout_bank_charge_type}\n";
    echo "   payout_bank_charge_value: {$settings->payout_bank_charge_value}\n";
    echo "   payout_bank_charge_cap: {$settings->payout_bank_charge_cap}\n";
} else {
    echo "   ❌ payout_bank_charge_* columns don't exist\n";
}

echo "\n5️⃣  Virtual Funding (Separate columns for VA deposits?):\n";
if (property_exists($settings, 'virtual_funding_type')) {
    echo "   virtual_funding_type: {$settings->virtual_funding_type}\n";
    echo "   virtual_funding_value: {$settings->virtual_funding_value}\n";
    echo "   virtual_funding_cap: {$settings->virtual_funding_cap}\n";
} else {
    echo "   ❌ virtual_funding_* columns DON'T EXIST\n";
    echo "   ⚠️  This is why FeeService uses 0.5% fallback!\n";
}

echo "\n🔍 THE PROBLEM:\n";
echo str_repeat("-", 60) . "\n";
echo "FeeService looks for 'virtual_funding_*' columns for VA deposits\n";
echo "But these columns don't exist in the database!\n";
echo "So it falls back to hardcoded 0.5%\n";

echo "\n✅ THE SOLUTION:\n";
echo str_repeat("-", 60) . "\n";
echo "We need to either:\n";
echo "A) Add virtual_funding_* columns to settings table\n";
echo "B) Make FeeService use transfer_charge_* for VA deposits\n";
echo "C) Add a separate admin panel section for VA deposit fees\n";

echo "\n📝 RECOMMENDATION:\n";
echo str_repeat("-", 60) . "\n";
echo "Since 'Funding with Bank Transfer' in admin panel is meant\n";
echo "for VA deposits, we should make FeeService use transfer_charge_*\n";
echo "for va_deposit transaction type.\n";
