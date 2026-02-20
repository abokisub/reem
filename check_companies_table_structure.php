<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "Checking Companies Table Structure\n";
echo "===================================\n\n";

// Get all columns in companies table
$columns = DB::select("SHOW COLUMNS FROM companies");

echo "Companies Table Columns:\n";
echo "------------------------\n";
foreach ($columns as $column) {
    echo "- {$column->Field} ({$column->Type})\n";
}

echo "\n";

// Check if settlement_account_number exists
$hasSettlementAccount = collect($columns)->contains(function($col) {
    return $col->Field === 'settlement_account_number';
});

if ($hasSettlementAccount) {
    echo "✅ settlement_account_number column EXISTS\n";
} else {
    echo "❌ settlement_account_number column DOES NOT EXIST\n";
    echo "\nThis is the problem! The field doesn't exist in the database.\n";
}

echo "\n✅ Done!\n";
