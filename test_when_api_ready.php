<?php
// Test account creation when PalmPay API is ready (no circuit breaker)
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\PalmPay\VirtualAccountService;

echo "🔄 TESTING PALMPAY API AVAILABILITY\n";
echo "===================================\n\n";

$maxAttempts = 5;
$waitSeconds = 30;

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    echo "🧪 ATTEMPT $attempt/$maxAttempts:\n";
    echo "Testing PalmPay API availability...\n";
    
    try {
        $palmPayService = new VirtualAccountService();
        
        $testCustomerData = [
            'name' => 'API Test Customer',
            'email' => 'api_test@example.com',
            'phone' => '08099887766'
        ];
        
        $account = $palmPayService->createVirtualAccount(
            4,
            'api_test_' . uniqid(),
            $testCustomerData,
            '100033'
        );
        
        echo "✅ SUCCESS! PalmPay API is working:\n";
        echo "- Account Number: {$account->account_number}\n";
        echo "- KYC Source: {$account->kyc_source}\n";
        echo "- Identity Type: {$account->identity_type}\n\n";
        
        // Clean up test account
        echo "🧹 Cleaning up test account...\n";
        $palmPayService->deleteVirtualAccount($account->account_number);
        $account->forceDelete();
        echo "✅ Test account cleaned up\n\n";
        
        echo "🎉 PALMPAY API IS READY!\n";
        echo "You can now create virtual accounts normally.\n";
        echo "The KYC fix should prevent 'licenseNumber duplicate' errors.\n";
        break;
        
    } catch (\Exception $e) {
        echo "❌ Failed: " . $e->getMessage() . "\n";
        
        if (strpos($e->getMessage(), 'Circuit Breaker') !== false) {
            echo "⏳ PalmPay API still unavailable (Circuit Breaker open)\n";
            
            if ($attempt < $maxAttempts) {
                echo "⏰ Waiting {$waitSeconds} seconds before next attempt...\n\n";
                sleep($waitSeconds);
            }
        } elseif (strpos($e->getMessage(), 'licenseNumber duplicate') !== false) {
            echo "🔍 KYC conflict detected - run permanent fix:\n";
            echo "   php permanent_kyc_fix.php CONFIRM\n\n";
            break;
        } else {
            echo "🚨 Unexpected error - check logs for details\n\n";
            break;
        }
    }
}

if ($attempt > $maxAttempts) {
    echo "⏰ TIMEOUT: PalmPay API still unavailable after $maxAttempts attempts\n";
    echo "💡 RECOMMENDATIONS:\n";
    echo "1. Wait longer for PalmPay infrastructure to recover\n";
    echo "2. Contact PalmPay support about extended downtime\n";
    echo "3. Try again later when circuit breaker closes\n";
}

echo "\n✅ API AVAILABILITY TEST COMPLETED\n";