<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "Checking for settlement_queue table...\n\n";

if (Schema::hasTable('settlement_queue')) {
    echo "✓ settlement_queue table EXISTS\n\n";
    
    // Show structure
    $columns = DB::select("DESCRIBE settlement_queue");
    echo "Table Structure:\n";
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type})\n";
    }
    
    // Show count
    $count = DB::table('settlement_queue')->count();
    echo "\nTotal records: {$count}\n";
} else {
    echo "✗ settlement_queue table DOES NOT EXIST\n\n";
    echo "This table should have been created by migration:\n";
    echo "  2026_02_18_120000_add_settlement_rules_to_settings.php\n\n";
    
    echo "Checking if migration file exists...\n";
    $migrationFile = 'database/migrations/2026_02_18_120000_add_settlement_rules_to_settings.php';
    if (file_exists($migrationFile)) {
        echo "✓ Migration file exists\n";
        echo "  Run: php artisan migrate\n";
    } else {
        echo "✗ Migration file NOT FOUND\n";
    }
}
