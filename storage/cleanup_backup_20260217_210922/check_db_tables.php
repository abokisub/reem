<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    echo "Checking database tables...\n\n";

    $tables = DB::select('SHOW TABLES');
    $dbName = DB::getDatabaseName();
    $tableKey = "Tables_in_{$dbName}";

    echo "Tables in database '{$dbName}':\n";
    echo str_repeat("=", 50) . "\n";

    foreach ($tables as $table) {
        echo "- " . $table->$tableKey . "\n";
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Total tables: " . count($tables) . "\n\n";

    // Check for specific tables we care about
    $checkTables = ['user', 'xixapay_customers', 'xixapay_virtual_accounts', 'paystack_key', 'settings', 'unified_banks'];

    echo "Checking for specific tables:\n";
    echo str_repeat("=", 50) . "\n";

    foreach ($checkTables as $tableName) {
        $exists = Schema::hasTable($tableName);
        echo "- {$tableName}: " . ($exists ? "EXISTS ✓" : "NOT FOUND ✗") . "\n";
    }

}
catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}