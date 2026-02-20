#!/bin/bash

echo "╔════════════════════════════════════════════════════════════╗"
echo "║         CHECK LIVE SERVER FEE SETTINGS                     ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

cat > /tmp/check_live_fees.php << 'PHPEOF'
<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "📊 LIVE SERVER - Settings Table Structure\n";
echo str_repeat("-", 60) . "\n\n";

// Get all columns
$columns = DB::select("SHOW COLUMNS FROM settings");

echo "All fee-related columns:\n";
foreach ($columns as $column) {
    if (strpos($column->Field, 'charge') !== false || 
        strpos($column->Field, 'funding') !== false ||
        strpos($column->Field, 'payout') !== false) {
        echo "  • {$column->Field} ({$column->Type})\n";
    }
}

echo "\n💰 Current Values:\n";
echo str_repeat("-", 60) . "\n";

$settings = DB::table('settings')->first();

$feeColumns = [
    'transfer_charge_type',
    'transfer_charge_value', 
    'transfer_charge_cap',
    'wallet_charge_type',
    'wallet_charge_value',
    'wallet_charge_cap',
    'payout_bank_charge_type',
    'payout_bank_charge_value',
    'payout_bank_charge_cap',
    'payout_palmpay_charge_type',
    'payout_palmpay_charge_value',
    'payout_palmpay_charge_cap',
    'virtual_funding_type',
    'virtual_funding_value',
    'virtual_funding_cap'
];

foreach ($feeColumns as $col) {
    if (property_exists($settings, $col)) {
        $value = $settings->$col ?? 'NULL';
        echo "  {$col}: {$value}\n";
    } else {
        echo "  {$col}: ❌ COLUMN DOES NOT EXIST\n";
    }
}

echo "\n🔍 Admin Panel Mappings:\n";
echo str_repeat("-", 60) . "\n";
echo "1. Funding with Bank Transfer → transfer_charge_*\n";
echo "2. Internal Transfer (Wallet) → wallet_charge_*\n";
echo "3. Settlement Withdrawal (PalmPay) → payout_palmpay_charge_*\n";
echo "4. External Transfer (Other Banks) → payout_bank_charge_*\n";

echo "\n❓ QUESTION: Where should VA deposit fees come from?\n";
echo str_repeat("-", 60) . "\n";
echo "Option A: transfer_charge_* (same as 'Funding with Bank Transfer')\n";
echo "Option B: virtual_funding_* (separate column - needs to be created)\n";
echo "Option C: Something else?\n";

PHPEOF

# Run on server
ssh aboksdfs@server350.web-hosting.com "cd /home/aboksdfs/app.pointwave.ng && php /tmp/check_live_fees.php"

echo ""
echo "✅ Check complete!"
