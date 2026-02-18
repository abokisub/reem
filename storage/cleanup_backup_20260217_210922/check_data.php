<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

try {
    echo "WALLET_FUNDING (First 2):\n";
    try {
        print_r(DB::table('wallet_funding')->limit(2)->get()->toArray());
    } catch (\Exception $e) {
        echo "Error reading wallet_funding: " . $e->getMessage() . "\n";
    }

    echo "\nDEPOSIT (First 2):\n";
    print_r(DB::table('deposit')->limit(2)->get()->toArray());

    echo "\nMESSAGE (First 2):\n";
    print_r(DB::table('message')->limit(2)->get()->toArray());

} catch (\Exception $e) {
    echo "Global Error: " . $e->getMessage();
}
