<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking companies table structure...\n\n";

// Get table columns
$columns = DB::select("DESCRIBE companies");

echo "Companies table columns:\n";
foreach ($columns as $column) {
    echo "  - {$column->Field} ({$column->Type})\n";
}

echo "\n";
echo "Sample company data:\n";
$company = DB::table('companies')->first();
if ($company) {
    print_r($company);
} else {
    echo "No companies found\n";
}
