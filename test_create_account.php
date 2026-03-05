<?php
// Simple test script to create virtual account and verify on server terminal
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\PalmPay\VirtualAccountService;

$customerName = $argv[1] ?? 'Test Customer';
$customerEmail = $argv[2] ?? 'test@example.com';
$customerPhone = $argv[3] ?? '08012345678';

echo "🧪 TESTING VIRTUAL ACCOUNT CREATION\n";
echo "===================================\n\n";

echo "📋 TEST CUSTOMER DATA:\n";
echo "- Name: $customerName\n";
echo "- Email: $customerEmail\n";
echo "- Phone: $customerPhone\n\n";

try {
    $palmPayService = new VirtualAccountService();
    
    echo "🔄 Creating virtual account...\n";
    
    $customerData = [
        'name' => $customerName,
        'email' => $customerEmail,
        'phone' => $customerPhone
    ];
    
    $account = $palmPayService->createVirtualAccount(
        4, // Company ID (KoboPoint)
        'test_' . uniqid(),
        $customerData,
        '100033' // PalmPay bank code
    );
    
    echo "✅ SUCCESS! Virtual account created:\n\n";
    echo "📋 ACCOUNT DETAILS:\n";
    echo "- Account Number: {$account->account_number}\n";
    echo "- Account Name: {$account->palmpay_account_name}\n";
    echo "- Customer Name: {$account->customer_name}\n";
    echo "- Bank Name: {$account->palmpay_bank_name}\n";
    echo "- KYC Source: {$account->kyc_source}\n";
    echo "- Identity Type: {$account->identity_type}\n";
    echo "- Status: {$account->status}\n";
    echo "- Created: {$account->created_at}\n\n";
    
    echo "🎯 VERIFICATION:\n";
    echo "- Account created successfully ✅\n";
    echo "- No 'licenseNumber duplicate' error ✅\n";
    echo "- KYC method: {$account->kyc_source} ✅\n";
    echo "- PalmPay integration working ✅\n\n";
    
    // Ask if user wants to delete the test account
    echo "🗑️  DELETE TEST ACCOUNT?\n";
    echo "This is a test account. Do you want to delete it? (y/n): ";
    
    $handle = fopen("php://stdin", "r");
    $response = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($response) === 'y' || strtolower($response) === 'yes') {
        echo "\n🧹 Deleting test account...\n";
        
        // Delete from PalmPay
        $deleteResult = $palmPayService->deleteVirtualAccount($account->account_number);
        if ($deleteResult['success']) {
            echo "✅ Deleted from PalmPay\n";
        } else {
            echo "⚠️  PalmPay deletion warning: " . $deleteResult['message'] . "\n";
        }
        
        // Delete from database
        $account->forceDelete();
        echo "✅ Deleted from database\n";
        echo "🎉 Test account cleaned up successfully!\n\n";
    } else {
        echo "\n📝 Test account kept for manual verification\n";
        echo "Account Number: {$account->account_number}\n";
        echo "You can delete it later if needed.\n\n";
    }
    
} catch (\Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n\n";
    
    echo "🔍 ERROR ANALYSIS:\n";
    if (strpos($e->getMessage(), 'licenseNumber duplicate') !== false) {
        echo "- Issue: KYC conflict (BVN/NIN duplicate)\n";
        echo "- Solution: Run smart KYC fix script\n";
        echo "- Command: php smart_kyc_conflict_fix.php CONFIRM\n";
    } elseif (strpos($e->getMessage(), 'Circuit Breaker') !== false) {
        echo "- Issue: PalmPay API temporarily unavailable\n";
        echo "- Solution: Wait and retry in a few minutes\n";
    } else {
        echo "- Issue: " . $e->getMessage() . "\n";
        echo "- Check logs for more details\n";
    }
    echo "\n";
}

echo "✅ TEST COMPLETED\n";