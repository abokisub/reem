#!/usr/bin/env php
<?php

/**
 * Update Transfer Fee to ₦30
 * 
 * This script updates the transfer_charge_value in the settings table
 * from ₦100 to ₦30 as per the correct configuration.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Updating transfer fee configuration...\n\n";

// Get current settings
$settings = DB::table('settings')->first();

echo "Current Settings:\n";
echo "  Transfer Charge Type: " . ($settings->transfer_charge_type ?? 'NOT SET') . "\n";
echo "  Transfer Charge Value: ₦" . ($settings->transfer_charge_value ?? 'NOT SET') . "\n";
echo "  Transfer Charge Cap: ₦" . ($settings->transfer_charge_cap ?? 'NOT SET') . "\n\n";

// Update to ₦30
DB::table('settings')->update([
    'transfer_charge_value' => 30.00
]);

echo "✅ Updated transfer_charge_value to ₦30.00\n\n";

// Verify
$updated = DB::table('settings')->first();
echo "New Settings:\n";
echo "  Transfer Charge Type: " . $updated->transfer_charge_type . "\n";
echo "  Transfer Charge Value: ₦" . $updated->transfer_charge_value . "\n";
echo "  Transfer Charge Cap: ₦" . $updated->transfer_charge_cap . "\n\n";

echo "✅ Transfer fee configuration updated successfully!\n";
echo "\nNOTE: You need to deploy this change to production:\n";
echo "  1. git add .\n";
echo "  2. git commit -m 'Fix: Use correct transfer_charge settings for API transfers (₦30)'\n";
echo "  3. git push\n";
echo "  4. On server: git pull && php artisan config:clear && php artisan cache:clear\n";
echo "  5. Clear OPcache: https://app.pointwave.ng/clear-opcache.php?secret=YOUR_SECRET\n";
