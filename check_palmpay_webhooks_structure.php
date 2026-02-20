<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PALMPAY_WEBHOOKS TABLE STRUCTURE ===\n\n";

$columns = DB::select("DESCRIBE palmpay_webhooks");

foreach ($columns as $column) {
    echo "Column: {$column->Field}\n";
    echo "Type: {$column->Type}\n";
    echo "---\n";
}

echo "\n=== SAMPLE WEBHOOK LOG ===\n";
$sample = DB::table('palmpay_webhooks')->first();
if ($sample) {
    print_r($sample);
}
