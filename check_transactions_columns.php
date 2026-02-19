<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Checking transactions table columns...\n\n";

$columns = DB::select("SHOW COLUMNS FROM transactions");

echo "Existing columns:\n";
foreach ($columns as $column) {
    echo "  - {$column->Field} ({$column->Type})\n";
}

echo "\n";
echo "Checking for specific columns:\n";
echo "  provider_reference: " . (Schema::hasColumn('transactions', 'provider_reference') ? 'EXISTS' : 'MISSING') . "\n";
echo "  provider: " . (Schema::hasColumn('transactions', 'provider') ? 'EXISTS' : 'MISSING') . "\n";
echo "  reconciliation_status: " . (Schema::hasColumn('transactions', 'reconciliation_status') ? 'EXISTS' : 'MISSING') . "\n";
echo "  reconciled_at: " . (Schema::hasColumn('transactions', 'reconciled_at') ? 'EXISTS' : 'MISSING') . "\n";
