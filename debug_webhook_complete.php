<?php
/**
 * Complete Webhook Debugging Script
 * Run this on production: php debug_webhook_complete.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PALMPAY WEBHOOK DEBUGGING ===\n\n";

// 1. Check Environment
echo "1. ENVIRONMENT CHECK:\n";
echo "   APP_ENV: " . env('APP_ENV') . "\n";
echo "   APP_URL: " . env('APP_URL') . "\n";
echo "   APP_DEBUG: " . (env('APP_DEBUG') ? 'true' : 'false') . "\n\n";

// 2. Check PalmPay Configuration
echo "2. PALMPAY CONFIGURATION:\n";
echo "   Base URL: " . config('services.palmpay.base_url') . "\n";
echo "   Merchant ID: " . config('services.palmpay.merchant_id') . "\n";
echo "   App ID: " . config('services.palmpay.app_id') . "\n";
echo "   Public Key: " . (config('services.palmpay.public_key') ? 'Set ✓' : 'Missing ✗') . "\n";
echo "   Private Key: " . (config('services.palmpay.private_key') ? 'Set ✓' : 'Missing ✗') . "\n\n";

// 3. Check Database Tables
echo "3. DATABASE TABLES:\n";
try {
    $webhookCount = DB::table('webhook_logs')->count();
    echo "   webhook_logs table: EXISTS ✓ ({$webhookCount} records)\n";
} catch (\Exception $e) {
    echo "   webhook_logs table: ERROR ✗ - " . $e->getMessage() . "\n";
}

try {
    $palmpayWebhookCount = DB::table('palmpay_webhooks')->count();
    echo "   palmpay_webhooks table: EXISTS ✓ ({$palmpayWebhookCount} records)\n";
} catch (\Exception $e) {
    echo "   palmpay_webhooks table: ERROR ✗ - " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Check Webhook Route
echo "4. WEBHOOK ROUTE CHECK:\n";
$routes = Route::getRoutes();
$webhookRoute = null;
foreach ($routes as $route) {
    if (str_contains($route->uri(), 'webhooks/palmpay')) {
        $webhookRoute = $route;
        break;
    }
}

if ($webhookRoute) {
    echo "   Route: EXISTS ✓\n";
    echo "   URI: " . $webhookRoute->uri() . "\n";
    echo "   Methods: " . implode(', ', $webhookRoute->methods()) . "\n";
    echo "   Action: " . $webhookRoute->getActionName() . "\n";
} else {
    echo "   Route: NOT FOUND ✗\n";
}

echo "\n";

// 5. Test Webhook Endpoint
echo "5. WEBHOOK ENDPOINT TEST:\n";
$webhookUrl = env('APP_URL') . '/api/webhooks/palmpay';
echo "   Testing: {$webhookUrl}\n";

try {
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => 'data']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "   Status: ERROR ✗\n";
        echo "   Error: {$error}\n";
    } else {
        echo "   Status: HTTP {$httpCode}\n";
        echo "   Response: {$response}\n";
        if ($httpCode == 200) {
            echo "   Result: ACCESSIBLE ✓\n";
        } else {
            echo "   Result: ISSUE DETECTED ✗\n";
        }
    }
} catch (\Exception $e) {
    echo "   Status: EXCEPTION ✗\n";
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// 6. Check Recent Logs
echo "6. RECENT LARAVEL LOGS:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recentLines = array_slice($lines, -20);
    $webhookLines = array_filter($recentLines, function($line) {
        return stripos($line, 'webhook') !== false || stripos($line, 'palmpay') !== false;
    });
    
    if (empty($webhookLines)) {
        echo "   No recent webhook-related logs found\n";
    } else {
        foreach ($webhookLines as $line) {
            echo "   " . trim($line) . "\n";
        }
    }
} else {
    echo "   Log file not found\n";
}

echo "\n";

// 7. Check Companies with Virtual Accounts
echo "7. COMPANIES WITH VIRTUAL ACCOUNTS:\n";
try {
    $companies = DB::table('companies')
        ->whereNotNull('palmpay_account_number')
        ->select('id', 'name', 'palmpay_account_number')
        ->get();
    
    if ($companies->isEmpty()) {
        echo "   No companies with PalmPay accounts found\n";
    } else {
        foreach ($companies as $company) {
            echo "   - {$company->name} (ID: {$company->id})\n";
            echo "     Account: {$company->palmpay_account_number}\n";
        }
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// 8. Check Recent Transactions
echo "8. RECENT TRANSACTIONS:\n";
try {
    $transactions = DB::table('transactions')
        ->where('created_at', '>=', now()->subDays(7))
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get(['transaction_id', 'type', 'amount', 'status', 'palmpay_reference', 'created_at']);
    
    if ($transactions->isEmpty()) {
        echo "   No recent transactions found\n";
    } else {
        foreach ($transactions as $txn) {
            echo "   - {$txn->transaction_id}: {$txn->type} ₦{$txn->amount} ({$txn->status})\n";
            echo "     PalmPay Ref: " . ($txn->palmpay_reference ?? 'N/A') . "\n";
            echo "     Date: {$txn->created_at}\n";
        }
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// 9. Middleware Check
echo "9. MIDDLEWARE CHECK:\n";
if ($webhookRoute) {
    $middleware = $webhookRoute->middleware();
    if (empty($middleware)) {
        echo "   No middleware applied ✓\n";
    } else {
        echo "   Middleware: " . implode(', ', $middleware) . "\n";
        if (in_array('auth', $middleware) || in_array('auth:sanctum', $middleware)) {
            echo "   WARNING: Auth middleware detected! Webhooks should be public ✗\n";
        }
    }
} else {
    echo "   Cannot check - route not found\n";
}

echo "\n";

// 10. Recommendations
echo "10. RECOMMENDATIONS:\n";

$issues = [];

if (env('APP_ENV') !== 'production') {
    $issues[] = "Set APP_ENV=production in .env";
}

if (env('APP_DEBUG') === true) {
    $issues[] = "Set APP_DEBUG=false in .env (security risk)";
}

if (!config('services.palmpay.merchant_id')) {
    $issues[] = "PALMPAY_MERCHANT_ID is not configured";
}

if ($webhookRoute && (in_array('auth', $webhookRoute->middleware()) || in_array('auth:sanctum', $webhookRoute->middleware()))) {
    $issues[] = "Remove authentication middleware from webhook route";
}

if (empty($issues)) {
    echo "   ✓ No configuration issues detected\n";
    echo "\n";
    echo "   If webhooks still not working:\n";
    echo "   1. Verify webhook URL in PalmPay dashboard: {$webhookUrl}\n";
    echo "   2. Confirm IP whitelist includes production server IP\n";
    echo "   3. Send test payment and check: tail -f storage/logs/laravel.log\n";
    echo "   4. Contact PalmPay support to verify webhook activation\n";
} else {
    echo "   Issues found:\n";
    foreach ($issues as $issue) {
        echo "   ✗ {$issue}\n";
    }
}

echo "\n=== END OF DEBUGGING ===\n";
