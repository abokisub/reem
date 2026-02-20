<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Updating settlement time to 3am (PalmPay settles at 2am)...\n\n";

DB::table('settings')->update([
    'settlement_time' => '03:00:00'
]);

echo "✓ Settlement time updated to 03:00:00\n";
echo "✓ System will now settle at 3am (1 hour after PalmPay settles at 2am)\n";
