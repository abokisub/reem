<?php
/**
 * Clean up test "Aboki Sub" account for developer testing
 * 
 * This script permanently removes the test account created during the fix
 * so the developer can test the API with a clean slate and confirm the fix works
 */

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VirtualAccount;
use App\Models\CompanyUser;
use App\Services\PalmPay\VirtualAccountService;

$confirm = $argv[1] ?? null;

if ($confirm !== 'CONFIRM') {
    echo "🧹 CLEANUP TEST ABOKI SUB ACCOUNT\n";
    echo "=================================\n\n";
    echo "This script will permanently delete the test 'Aboki Sub' account\n";
    echo "created during the deduplication fix process.\n\n";
    echo "This allows the developer to test with a clean slate and confirm\n";
    echo "the API fix is working correctly.\n\n";
    echo "⚠️  WARNING: This will permanently delete test data\n";
    echo "To proceed, run: php cleanup_test_aboki_account.php CONFIRM\n";
    exit(1);
}

echo "🧹 CLEANUP TEST ABOKI SUB ACCOUNT\n";
echo "=================================\n\n";

$palmPayService = new VirtualAccountService();
$deletedAccounts = [];
$deletedUsers = [];

try {
    // Find the test "Aboki Sub" account created during fix
    echo "1. SEARCHING FOR TEST ABOKI SUB ACCOUNTS\n";
    echo "----------------------------------------\n";
    
    $abokiAccounts = VirtualAccount::where('customer_name', 'Aboki Sub')
        ->where('customer_email', 'habukhan001@gmail.com')
        ->where('customer_phone', '07040540018')
        ->get();
    
    echo "Found " . $abokiAccounts->count() . " Aboki Sub test accounts:\n";
    
    foreach ($abokiAccounts as $account) {
        echo "- Account: {$account->account_number}\n";
        echo "  Customer: {$account->customer_name}\n";
        echo "  Email: {$account->customer_email}\n";
        echo "  Phone: {$account->customer_phone}\n";
        echo "  Created: {$account->created_at}\n";
        
        // Delete on PalmPay side first
        if ($account->palmpay_account_number) {
            echo "  Deleting on PalmPay side...\n";
            $result = $palmPayService->deleteVirtualAccount($account->palmpay_account_number);
            
            if ($result['success']) {
                echo "  ✅ Deleted on PalmPay\n";
            } else {
                echo "  ⚠️  PalmPay deletion warning: " . $result['message'] . "\n";
            }
        }
        
        // Permanently delete from our database
        echo "  Permanently deleting from database...\n";
        $account->forceDelete();
        echo "  ✅ Permanently deleted from database\n";
        
        $deletedAccounts[] = [
            'account_number' => $account->account_number,
            'customer_name' => $account->customer_name,
            'email' => $account->customer_email,
            'phone' => $account->customer_phone
        ];
        
        echo "\n";
    }
    
    // Also clean up any CompanyUser records for this test data
    echo "2. CLEANING UP COMPANY USER RECORDS\n";
    echo "-----------------------------------\n";
    
    $abokiUsers = CompanyUser::where('first_name', 'Aboki')
        ->where('last_name', 'Sub')
        ->where('email', 'habukhan001@gmail.com')
        ->where('phone', '07040540018')
        ->get();
    
    echo "Found " . $abokiUsers->count() . " Aboki Sub company user records:\n";
    
    foreach ($abokiUsers as $user) {
        echo "- User ID: {$user->id}\n";
        echo "  Name: {$user->first_name} {$user->last_name}\n";
        echo "  Email: {$user->email}\n";
        echo "  Phone: {$user->phone}\n";
        echo "  Company: {$user->company_id}\n";
        
        $deletedUsers[] = [
            'id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'company_id' => $user->company_id
        ];
        
        $user->delete();
        echo "  ✅ Deleted company user record\n\n";
    }
    
    // Summary
    echo "3. CLEANUP SUMMARY\n";
    echo "------------------\n";
    echo "✅ Virtual Accounts Deleted: " . count($deletedAccounts) . "\n";
    echo "✅ Company Users Deleted: " . count($deletedUsers) . "\n\n";
    
    if (count($deletedAccounts) > 0) {
        echo "Deleted Virtual Accounts:\n";
        foreach ($deletedAccounts as $account) {
            echo "- {$account['account_number']} ({$account['customer_name']})\n";
        }
        echo "\n";
    }
    
    if (count($deletedUsers) > 0) {
        echo "Deleted Company Users:\n";
        foreach ($deletedUsers as $user) {
            echo "- {$user['name']} (ID: {$user['id']})\n";
        }
        echo "\n";
    }
    
    echo "4. DEVELOPER TESTING READY\n";
    echo "--------------------------\n";
    echo "✅ Test data cleaned up completely\n";
    echo "✅ Developer can now test with fresh data\n";
    echo "✅ API will create new account for 'Aboki Sub' registration\n";
    echo "✅ Deduplication fix can be verified\n\n";
    
    echo "NEXT STEPS FOR DEVELOPER:\n";
    echo "1. Register 'Aboki Sub' with email: habukhan001@gmail.com\n";
    echo "2. Register 'Aboki Sub' with phone: 07040540018\n";
    echo "3. Verify new virtual account is created correctly\n";
    echo "4. Verify account name shows 'kobopoint-Aboki Sub(PointWave)'\n";
    echo "5. Confirm no data corruption occurs\n\n";
    
    // Log the cleanup
    \Log::info('Test Aboki Sub account cleanup completed', [
        'deleted_accounts' => count($deletedAccounts),
        'deleted_users' => count($deletedUsers),
        'account_details' => $deletedAccounts,
        'user_details' => $deletedUsers,
        'timestamp' => now()->toISOString()
    ]);
    
    echo "✅ CLEANUP COMPLETED SUCCESSFULLY\n";
    echo "Developer can now test the fixed API with clean data.\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR DURING CLEANUP\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n\n";
    
    \Log::error('Test account cleanup failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'timestamp' => now()->toISOString()
    ]);
    
    exit(1);
}