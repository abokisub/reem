<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    CURRENT FEE SETTING FOR VA DEPOSITS                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$settings = DB::table('settings')->first();

echo "📊 Funding with Bank Transfer (VA Deposits):\n";
echo str_repeat("-", 60) . "\n";
echo "  Type: " . $settings->transfer_charge_type . "\n";
echo "  Value: " . $settings->transfer_charge_value . "\n";
echo "  Cap: " . $settings->transfer_charge_cap . "\n\n";

echo "💡 What this means:\n";
echo str_repeat("-", 60) . "\n";

if ($settings->transfer_charge_type === 'FLAT') {
    echo "  Flat fee of ₦" . number_format($settings->transfer_charge_value, 2) . " per deposit\n";
    echo "  Example: ₦100 deposit → Fee: ₦{$settings->transfer_charge_value} → Net: ₦" . (100 - $settings->transfer_charge_value) . "\n";
} elseif ($settings->transfer_charge_type === 'PERCENT') {
    echo "  Percentage fee of {$settings->transfer_charge_value}% per deposit\n";
    $exampleFee = (100 * $settings->transfer_charge_value) / 100;
    echo "  Example: ₦100 deposit → Fee: ₦{$exampleFee} → Net: ₦" . (100 - $exampleFee) . "\n";
}

echo "\n❓ USER QUESTION:\n";
echo str_repeat("-", 60) . "\n";
echo "You said you updated it to 0.70% but it's showing as:\n";
echo "  {$settings->transfer_charge_type} {$settings->transfer_charge_value}\n\n";

echo "Did you:\n";
echo "  A) Set it to PERCENT 0.70 (meaning 0.70%)?\n";
echo "  B) Set it to FLAT 100 (meaning ₦100 flat fee)?\n\n";

echo "Please check the admin panel and confirm what you see there.\n";

