<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== CHECKING PALMPAY_WEBHOOKS TABLE STRUCTURE ===\n\n";

// Get table columns
$columns = DB::select("DESCRIBE palmpay_webhooks");

echo "Columns in palmpay_webhooks table:\n";
echo str_repeat("=", 80) . "\n";

foreach ($columns as $column) {
    echo "Column: {$column->Field}\n";
    echo "  Type: {$column->Type}\n";
    echo "  Null: {$column->Null}\n";
    echo "  Default: {$column->Default}\n";
    echo str_repeat("-", 80) . "\n";
}

// Check if required columns exist
$requiredColumns = ['order_no', 'order_amount', 'account_reference', 'status', 'retry_count', 'next_retry_at'];

echo "\nRequired columns check:\n";
echo str_repeat("=", 80) . "\n";

$existingColumns = array_column($columns, 'Field');

foreach ($requiredColumns as $col) {
    $exists = in_array($col, $existingColumns);
    $status = $exists ? '✓ EXISTS' : '✗ MISSING';
    echo "$status: $col\n";
}

// Check migrations table
echo "\n\nChecking if migrations were run:\n";
echo str_repeat("=", 80) . "\n";

$migrations = [
    '2026_02_19_111538_add_retry_fields_to_palmpay_webhooks',
    '2026_02_20_100000_add_extracted_fields_to_palmpay_webhooks'
];

foreach ($migrations as $migration) {
    $exists = DB::table('migrations')->where('migration', $migration)->exists();
    $status = $exists ? '✓ RUN' : '✗ NOT RUN';
    echo "$status: $migration\n";
}
