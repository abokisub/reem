<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

$tables = ['message', 'wallet_funding', 'virtual_accounts', 'deposit', 'transfers'];

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        echo "TABLE: {$table}\n";
        echo "---------------------------------\n";
        $columns = Schema::getColumnListing($table);
        foreach ($columns as $column) {
            echo "- {$column}\n";
        }
        echo "\n";
    } else {
        echo "TABLE: {$table} NOT FOUND\n\n";
    }
}
