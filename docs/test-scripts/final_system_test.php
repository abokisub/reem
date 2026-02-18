<?php
/**
 * FINAL SYSTEM TEST
 * Comprehensive test of all system components
 */

require __DIR__.'/../../vendor/autoload.php';

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "                    FINAL SYSTEM TEST                    \n";
echo str_repeat("=", 70) . "\n\n";

$errors = [];
$warnings = [];
$passed = 0;
$failed = 0;

function test($name, $condition, $errorMsg = '') {
    global $passed, $failed, $errors;
    if ($condition) {
        echo "✅ {$name}\n";
        $passed++;
        return true;
    } else {
        echo "❌ {$name}\n";
        if ($errorMsg) echo "   Error: {$errorMsg}\n";
        $failed++;
        $errors[] = $name;
        return false;
    }
}

function warn($message) {
    global $warnings;
    echo "⚠️  {$message}\n";
    $warnings[] = $message;
}

// 1. DATABASE TESTS
echo "1. DATABASE TESTS\n";
echo str_repeat("-", 70) . "\n";

try {
    DB::connection()->getPdo();
    test("Database Connection", true);
} catch (\Exception $e) {
    test("Database Connection", false, $e->getMessage());
}

$requiredTables = [
    'users', 'companies', 'company_wallets', 'virtual_accounts',
    'transactions', 'service_charges', 'settings', 'settlement_queue',
    'api_request_logs', 'palmpay_webhooks'
];

foreach ($requiredTables as $table) {
    test("Table: {$table}", Schema::hasTable($table));
}

// 2. CHARGES SYSTEM TESTS
echo "\n2. CHARGES SYSTEM TESTS\n";
echo str_repeat("-", 70) . "\n";

$palmpayCharge = DB::table('service_charges')
    ->where('service_name', 'palmpay_va')
    ->where('company_id', 1)
    ->first();

test("PalmPay VA Charge Configured", $palmpayCharge !== null);
if ($palmpayCharge) {
    test("PalmPay VA Charge Active", $palmpayCharge->is_active == 1);
    echo "   Type: {$palmpayCharge->charge_type}, Value: {$palmpayCharge->charge_value}, Cap: {$palmpayCharge->charge_cap}\n";
}

$kycCharges = DB::table('service_charges')
    ->where('service_category', 'kyc')
    ->where('company_id', 1)
    ->count();

test("KYC Charges Configured", $kycCharges >= 10, "Found {$kycCharges}, expected 10+");

$settings = DB::table('settings')->first();
test("Settings Table Has Data", $settings !== null);

if ($settings) {
    $hasSettlement = property_exists($settings, 'auto_settlement_enabled');
    test("Settlement Rules Configured", $hasSettlement);
    
    if ($hasSettlement) {
        echo "   Auto Settlement: " . ($settings->auto_settlement_enabled ? 'Enabled' : 'Disabled') . "\n";
        echo "   Delay: {$settings->settlement_delay_hours}h\n";
        echo "   Min Amount: ₦{$settings->settlement_minimum_amount}\n";
    }
}

// 3. API LOGGING TESTS
echo "\n3. API LOGGING TESTS\n";
echo str_repeat("-", 70) . "\n";

$apiLogsCount = DB::table('api_request_logs')->count();
test("API Request Logs Table Working", Schema::hasTable('api_request_logs'));
echo "   Total API Logs: {$apiLogsCount}\n";

if ($apiLogsCount > 0) {
    $recentLog = DB::table('api_request_logs')
        ->orderBy('created_at', 'desc')
        ->first();
    echo "   Latest: {$recentLog->method} {$recentLog->path} ({$recentLog->status_code})\n";
}

// 4. WEBHOOK TESTS
echo "\n4. WEBHOOK TESTS\n";
echo str_repeat("-", 70) . "\n";

$webhooksCount = DB::table('palmpay_webhooks')->count();
test("PalmPay Webhooks Table Working", Schema::hasTable('palmpay_webhooks'));
echo "   Total Webhooks: {$webhooksCount}\n";

if ($webhooksCount > 0) {
    $successfulWebhooks = DB::table('palmpay_webhooks')
        ->where('verified', true)
        ->where('processed', true)
        ->count();
    echo "   Successful: {$successfulWebhooks}\n";
    echo "   Success Rate: " . round(($successfulWebhooks / $webhooksCount) * 100, 1) . "%\n";
}

// 5. COMPANY & WALLET TESTS
echo "\n5. COMPANY & WALLET TESTS\n";
echo str_repeat("-", 70) . "\n";

$companiesCount = DB::table('companies')->count();
test("Companies Table Has Data", $companiesCount > 0);
echo "   Total Companies: {$companiesCount}\n";

$walletsCount = DB::table('company_wallets')->count();
test("Company Wallets Created", $walletsCount > 0);

if ($walletsCount > 0) {
    $totalBalance = DB::table('company_wallets')->sum('balance');
    echo "   Total Balance: ₦" . number_format($totalBalance, 2) . "\n";
}

// 6. TRANSACTION TESTS
echo "\n6. TRANSACTION TESTS\n";
echo str_repeat("-", 70) . "\n";

$transactionsCount = DB::table('transactions')->count();
test("Transactions Table Working", Schema::hasTable('transactions'));
echo "   Total Transactions: {$transactionsCount}\n";

if ($transactionsCount > 0) {
    $successfulTxns = DB::table('transactions')->where('status', 'success')->count();
    $failedTxns = DB::table('transactions')->where('status', 'failed')->count();
    echo "   Successful: {$successfulTxns}\n";
    echo "   Failed: {$failedTxns}\n";
    
    $totalRevenue = DB::table('transactions')
        ->where('status', 'success')
        ->sum('fee');
    echo "   Platform Revenue: ₦" . number_format($totalRevenue, 2) . "\n";
}

// 7. SETTLEMENT QUEUE TESTS
echo "\n7. SETTLEMENT QUEUE TESTS\n";
echo str_repeat("-", 70) . "\n";

test("Settlement Queue Table Exists", Schema::hasTable('settlement_queue'));

if (Schema::hasTable('settlement_queue')) {
    $pendingSettlements = DB::table('settlement_queue')
        ->where('status', 'pending')
        ->count();
    echo "   Pending Settlements: {$pendingSettlements}\n";
    
    if ($pendingSettlements > 0) {
        $pendingAmount = DB::table('settlement_queue')
            ->where('status', 'pending')
            ->sum('amount');
        echo "   Pending Amount: ₦" . number_format($pendingAmount, 2) . "\n";
    }
}

// 8. FILE SYSTEM TESTS
echo "\n8. FILE SYSTEM TESTS\n";
echo str_repeat("-", 70) . "\n";

$requiredFiles = [
    'app/Services/PalmPay/WebhookHandler.php',
    'app/Services/ChargeCalculator.php',
    'app/Http/Controllers/API/AdminController.php',
    'app/Http/Middleware/ApiRequestLogMiddleware.php',
    'public/.htaccess',
    'routes/web.php',
    'routes/api.php',
];

foreach ($requiredFiles as $file) {
    test("File: {$file}", file_exists($file));
}

// 9. CONFIGURATION TESTS
echo "\n9. CONFIGURATION TESTS\n";
echo str_repeat("-", 70) . "\n";

test("APP_ENV Set", env('APP_ENV') !== null);
echo "   Environment: " . env('APP_ENV') . "\n";

test("APP_DEBUG Set", env('APP_DEBUG') !== null);
echo "   Debug Mode: " . (env('APP_DEBUG') ? 'Enabled' : 'Disabled') . "\n";

test("APP_URL Set", env('APP_URL') !== null);
echo "   URL: " . env('APP_URL') . "\n";

test("Database Configured", env('DB_DATABASE') !== null);
echo "   Database: " . env('DB_DATABASE') . "\n";

// 10. PALMPAY CONFIGURATION
echo "\n10. PALMPAY CONFIGURATION\n";
echo str_repeat("-", 70) . "\n";

test("PalmPay Merchant ID Set", env('PALMPAY_MERCHANT_ID') !== null);
test("PalmPay App ID Set", env('PALMPAY_APP_ID') !== null);
test("PalmPay Public Key Set", env('PALMPAY_PUBLIC_KEY') !== null);
test("PalmPay Private Key Set", env('PALMPAY_PRIVATE_KEY') !== null);

if (env('PALMPAY_MERCHANT_ID')) {
    echo "   Merchant ID: " . env('PALMPAY_MERCHANT_ID') . "\n";
}

// 11. ROUTES TEST
echo "\n11. ROUTES TEST\n";
echo str_repeat("-", 70) . "\n";

try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $apiRoutes = 0;
    $webRoutes = 0;
    
    foreach ($routes as $route) {
        if (strpos($route->uri(), 'api/') === 0) {
            $apiRoutes++;
        } else {
            $webRoutes++;
        }
    }
    
    test("Routes Loaded", true);
    echo "   API Routes: {$apiRoutes}\n";
    echo "   Web Routes: {$webRoutes}\n";
    echo "   Total Routes: " . ($apiRoutes + $webRoutes) . "\n";
} catch (\Exception $e) {
    test("Routes Loaded", false, $e->getMessage());
}

// 12. LOGS TEST
echo "\n12. LOGS TEST\n";
echo str_repeat("-", 70) . "\n";

$logFile = storage_path('logs/laravel.log');
test("Laravel Log File Exists", file_exists($logFile));

if (file_exists($logFile)) {
    $logSize = filesize($logFile);
    echo "   Log Size: " . round($logSize / 1024, 2) . " KB\n";
    
    if ($logSize > 10 * 1024 * 1024) { // 10MB
        warn("Log file is large (>10MB). Consider rotating logs.");
    }
}

// SUMMARY
echo "\n" . str_repeat("=", 70) . "\n";
echo "                         SUMMARY                         \n";
echo str_repeat("=", 70) . "\n\n";

echo "✅ Passed: {$passed}\n";
echo "❌ Failed: {$failed}\n";
echo "⚠️  Warnings: " . count($warnings) . "\n\n";

if ($failed > 0) {
    echo "Failed Tests:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "Warnings:\n";
    foreach ($warnings as $warning) {
        echo "  - {$warning}\n";
    }
    echo "\n";
}

$successRate = $passed > 0 ? round(($passed / ($passed + $failed)) * 100, 1) : 0;
echo "Success Rate: {$successRate}%\n\n";

if ($failed == 0) {
    echo "🎉 ALL TESTS PASSED! SYSTEM IS READY FOR PRODUCTION!\n\n";
    exit(0);
} else {
    echo "⚠️  SOME TESTS FAILED. PLEASE FIX ISSUES BEFORE DEPLOYING.\n\n";
    exit(1);
}
