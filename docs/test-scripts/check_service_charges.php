<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SERVICE CHARGES TABLE ===\n\n";

// Check if table exists
$tableExists = DB::select("SHOW TABLES LIKE 'service_charges'");

if (empty($tableExists)) {
    echo "❌ service_charges table does NOT exist!\n";
    echo "\nThis explains why charges are not being applied.\n";
    echo "The admin page at /secure/discount/other expects this table.\n";
    exit(1);
}

echo "✓ service_charges table exists\n\n";

// Show structure
echo "TABLE STRUCTURE:\n";
$columns = DB::select("DESCRIBE service_charges");
foreach ($columns as $col) {
    echo "   - {$col->Field} ({$col->Type})\n";
}

// Show all records
echo "\nALL RECORDS:\n";
$charges = DB::table('service_charges')->get();

if ($charges->isEmpty()) {
    echo "   ⚠️  No records found!\n";
} else {
    foreach ($charges as $charge) {
        echo "\n   ID: {$charge->id}\n";
        echo "   Company: {$charge->company_id}\n";
        echo "   Category: {$charge->service_category}\n";
        echo "   Service: {$charge->service_name}\n";
        echo "   Display: {$charge->display_name}\n";
        echo "   Type: {$charge->charge_type}\n";
        echo "   Value: {$charge->charge_value}\n";
        echo "   Cap: {$charge->charge_cap}\n";
        echo "   Active: " . ($charge->is_active ? 'Yes' : 'No') . "\n";
        echo "   ---\n";
    }
}

// Check specifically for PalmPay VA charge
echo "\nPALMPAY VA CHARGE (company_id = 1):\n";
$palmpayVA = DB::table('service_charges')
    ->where('company_id', 1)
    ->where('service_category', 'payment')
    ->where('service_name', 'palmpay_va')
    ->first();

if ($palmpayVA) {
    echo "   ✓ Found!\n";
    echo "   Type: {$palmpayVA->charge_type}\n";
    echo "   Value: {$palmpayVA->charge_value}\n";
    echo "   Cap: {$palmpayVA->charge_cap}\n";
    echo "   Active: " . ($palmpayVA->is_active ? 'Yes' : 'No') . "\n";
} else {
    echo "   ❌ NOT FOUND!\n";
    echo "   This is why charges are not being applied.\n";
}

echo "\n=== END ===\n";
