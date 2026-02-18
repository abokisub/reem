<?php

/**
 * Check PalmPay Configuration on Production Server
 * Run this on your production server to verify PalmPay settings
 */

// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PalmPay Configuration Check ===\n\n";

// Check environment
echo "Environment: " . config('app.env') . "\n";
echo "Debug Mode: " . (config('app.debug') ? 'ON' : 'OFF') . "\n\n";

// Check PalmPay configuration
echo "PalmPay Configuration:\n";
echo str_repeat("-", 50) . "\n";

$config = [
    'PALMPAY_BASE_URL' => config('services.palmpay.base_url'),
    'PALMPAY_MERCHANT_ID' => config('services.palmpay.merchant_id'),
    'PALMPAY_API_KEY' => config('services.palmpay.api_key'),
    'PALMPAY_SECRET_KEY' => config('services.palmpay.secret_key'),
    'PALMPAY_MASTER_WALLET' => config('services.palmpay.master_wallet'),
];

foreach ($config as $key => $value) {
    if (empty($value)) {
        echo "❌ $key: NOT SET\n";
    } else {
        // Mask sensitive values
        if (in_array($key, ['PALMPAY_API_KEY', 'PALMPAY_SECRET_KEY'])) {
            $masked = substr($value, 0, 10) . '...' . substr($value, -5);
            echo "✅ $key: $masked\n";
        } else {
            echo "✅ $key: $value\n";
        }
    }
}

echo "\n";

// Check database connection
echo "Database Connection:\n";
echo str_repeat("-", 50) . "\n";
try {
    DB::connection()->getPdo();
    echo "✅ Database connected\n";
    echo "Database: " . config('database.connections.mysql.database') . "\n";
} catch (\Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Check companies table
echo "Companies Check:\n";
echo str_repeat("-", 50) . "\n";
try {
    $companies = DB::table('companies')
        ->where('palmpay_merchant_id', '!=', '')
        ->get(['id', 'name', 'palmpay_merchant_id', 'palmpay_master_wallet']);
    
    if ($companies->count() > 0) {
        echo "✅ Found " . $companies->count() . " companies with PalmPay config\n\n";
        foreach ($companies as $company) {
            echo "Company: {$company->name}\n";
            echo "  Merchant ID: {$company->palmpay_merchant_id}\n";
            echo "  Master Wallet: {$company->palmpay_master_wallet}\n\n";
        }
    } else {
        echo "⚠️  No companies found with PalmPay configuration\n";
    }
} catch (\Exception $e) {
    echo "❌ Error checking companies: " . $e->getMessage() . "\n";
}

echo "\n";
echo "=== Recommendations ===\n";
echo str_repeat("-", 50) . "\n";

if (empty(config('services.palmpay.api_key'))) {
    echo "❌ PalmPay API credentials are not configured in .env\n";
    echo "   Add these to your production .env file:\n";
    echo "   PALMPAY_BASE_URL=https://api.palmpay.com\n";
    echo "   PALMPAY_MERCHANT_ID=your_merchant_id\n";
    echo "   PALMPAY_API_KEY=your_api_key\n";
    echo "   PALMPAY_SECRET_KEY=your_secret_key\n";
    echo "   PALMPAY_MASTER_WALLET=6644694207\n\n";
} else {
    echo "✅ PalmPay credentials are configured\n";
    echo "   If sync still fails, contact PalmPay support to:\n";
    echo "   1. Verify your API credentials are active\n";
    echo "   2. Enable transaction history API access\n";
    echo "   3. Activate webhook notifications\n\n";
}

echo "After updating .env, run:\n";
echo "  php artisan config:clear\n";
echo "  php artisan config:cache\n\n";
