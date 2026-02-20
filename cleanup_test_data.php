<?php

/**
 * Cleanup Test Data
 * Deletes the test customer and virtual account created during API testing
 */

echo "========================================\n";
echo "CLEANUP TEST DATA\n";
echo "========================================\n\n";

// Database connection
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CompanyUser;
use App\Models\VirtualAccount;

$customerId = '36990dceef4fff4d704f16afa3e9ee04a2372b70';
$vaId = 'PWV_VA_7E7A4575B2';

try {
    // Delete virtual account
    $va = VirtualAccount::where('uuid', $vaId)->first();
    if ($va) {
        $va->delete();
        echo "✅ Deleted virtual account: $vaId\n";
        echo "   Account Number: {$va->account_number}\n";
        echo "   Bank: {$va->bank_name}\n\n";
    } else {
        echo "⚠️  Virtual account not found: $vaId\n\n";
    }
    
    // Delete customer
    $customer = CompanyUser::where('uuid', $customerId)->first();
    if ($customer) {
        $customer->delete();
        echo "✅ Deleted customer: $customerId\n";
        echo "   Name: {$customer->first_name} {$customer->last_name}\n";
        echo "   Email: {$customer->email}\n";
        echo "   Phone: {$customer->phone}\n\n";
    } else {
        echo "⚠️  Customer not found: $customerId\n\n";
    }
    
    echo "========================================\n";
    echo "✅ CLEANUP COMPLETE!\n";
    echo "========================================\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
