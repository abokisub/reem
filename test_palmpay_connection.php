<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    PALMPAY CONNECTION TEST                                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "📋 CHECKING CONFIGURATION\n";
echo str_repeat("-", 60) . "\n";

$config = [
    'PALMPAY_BASE_URL' => config('services.palmpay.base_url'),
    'PALMPAY_MERCHANT_ID' => config('services.palmpay.merchant_id'),
    'PALMPAY_APP_ID' => config('services.palmpay.app_id'),
    'PALMPAY_PUBLIC_KEY' => config('services.palmpay.public_key'),
    'PALMPAY_PRIVATE_KEY' => config('services.palmpay.private_key'),
];

$allConfigured = true;

foreach ($config as $key => $value) {
    if (empty($value)) {
        echo "❌ {$key}: NOT SET\n";
        $allConfigured = false;
    } else {
        // Mask sensitive values
        if (in_array($key, ['PALMPAY_PUBLIC_KEY', 'PALMPAY_PRIVATE_KEY'])) {
            $masked = substr($value, 0, 20) . '...' . substr($value, -10);
            echo "✅ {$key}: {$masked}\n";
        } else {
            echo "✅ {$key}: {$value}\n";
        }
    }
}

echo "\n";

if (!$allConfigured) {
    echo "❌ CONFIGURATION INCOMPLETE\n";
    echo str_repeat("-", 60) . "\n";
    echo "Please configure missing PalmPay credentials in .env file:\n\n";
    echo "PALMPAY_BASE_URL=https://open-gw-prod.palmpay-inc.com\n";
    echo "PALMPAY_MERCHANT_ID=your_merchant_id\n";
    echo "PALMPAY_APP_ID=your_app_id\n";
    echo "PALMPAY_PUBLIC_KEY=your_public_key\n";
    echo "PALMPAY_PRIVATE_KEY=your_private_key\n\n";
    echo "After updating .env, run:\n";
    echo "  php artisan config:clear\n";
    echo "  php artisan cache:clear\n\n";
    exit(1);
}

echo "🔌 TESTING PALMPAY CONNECTION\n";
echo str_repeat("-", 60) . "\n";

try {
    $client = new \App\Services\PalmPay\PalmPayClient();
    
    echo "Calling PalmPay Banks API...\n";
    $startTime = microtime(true);
    
    $response = $client->get('/api/v2/banks/list', []);
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    if (isset($response['data']) && is_array($response['data'])) {
        $bankCount = count($response['data']);
        echo "\n✅ PALMPAY CONNECTION SUCCESSFUL!\n";
        echo str_repeat("-", 60) . "\n";
        echo "Response Time: {$duration}ms\n";
        echo "Banks Retrieved: {$bankCount}\n";
        echo "Status: " . ($response['respCode'] ?? 'N/A') . "\n";
        echo "Message: " . ($response['respMsg'] ?? 'N/A') . "\n";
        
        echo "\n📊 SAMPLE BANKS:\n";
        echo str_repeat("-", 60) . "\n";
        $sampleBanks = array_slice($response['data'], 0, 5);
        foreach ($sampleBanks as $bank) {
            echo "  • {$bank['bankName']} ({$bank['bankCode']})\n";
        }
        
        echo "\n✅ PALMPAY API IS WORKING CORRECTLY\n";
        echo str_repeat("-", 60) . "\n";
        echo "You can now:\n";
        echo "  1. Create virtual accounts\n";
        echo "  2. Process transfers\n";
        echo "  3. Verify account numbers\n";
        echo "  4. Handle webhooks\n";
        
        echo "\n🧪 NEXT STEPS:\n";
        echo str_repeat("-", 60) . "\n";
        echo "Test virtual account creation:\n";
        echo "  1. Use Kobopoint's integration\n";
        echo "  2. Or test via admin panel\n";
        echo "  3. Monitor logs: tail -f storage/logs/laravel.log\n";
        
    } else {
        echo "\n⚠️  UNEXPECTED RESPONSE FORMAT\n";
        echo str_repeat("-", 60) . "\n";
        echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ PALMPAY CONNECTION FAILED\n";
    echo str_repeat("-", 60) . "\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    $errorMsg = $e->getMessage();
    
    if (str_contains($errorMsg, 'sign error') || str_contains($errorMsg, 'OPEN_GW_000008')) {
        echo "🔍 DIAGNOSIS: Signature Error\n";
        echo str_repeat("-", 60) . "\n";
        echo "This error means PalmPay cannot verify your API signature.\n\n";
        echo "Possible causes:\n";
        echo "  1. ❌ Incorrect PALMPAY_PUBLIC_KEY or PALMPAY_PRIVATE_KEY\n";
        echo "  2. ❌ Incorrect PALMPAY_MERCHANT_ID or PALMPAY_APP_ID\n";
        echo "  3. ❌ Keys not activated by PalmPay\n";
        echo "  4. ❌ Using sandbox keys in production (or vice versa)\n\n";
        echo "Solutions:\n";
        echo "  1. Verify credentials with PalmPay support\n";
        echo "  2. Ensure you're using production credentials\n";
        echo "  3. Check if PalmPay account is activated\n";
        echo "  4. Verify merchant ID matches the keys\n";
    } elseif (str_contains($errorMsg, 'Connection') || str_contains($errorMsg, 'timeout')) {
        echo "🔍 DIAGNOSIS: Network Error\n";
        echo str_repeat("-", 60) . "\n";
        echo "Cannot reach PalmPay API servers.\n\n";
        echo "Possible causes:\n";
        echo "  1. ❌ Server firewall blocking outbound connections\n";
        echo "  2. ❌ Incorrect PALMPAY_BASE_URL\n";
        echo "  3. ❌ PalmPay API is down\n\n";
        echo "Solutions:\n";
        echo "  1. Check firewall rules\n";
        echo "  2. Verify base URL: https://open-gw-prod.palmpay-inc.com\n";
        echo "  3. Test with: curl -I https://open-gw-prod.palmpay-inc.com\n";
    } else {
        echo "🔍 DIAGNOSIS: Unknown Error\n";
        echo str_repeat("-", 60) . "\n";
        echo "Please contact PalmPay support with this error message.\n";
    }
    
    echo "\n📧 SUPPORT CONTACTS:\n";
    echo str_repeat("-", 60) . "\n";
    echo "PalmPay Business: business@palmpay.com\n";
    echo "PalmPay Technical: tech-support@palmpay.com\n";
    
    exit(1);
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    TEST COMPLETE                                            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
