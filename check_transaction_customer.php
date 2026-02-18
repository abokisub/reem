<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING TRANSACTION CUSTOMER DATA ===\n\n";

// Get a sample transaction
$transaction = DB::table('transactions')->first();

if ($transaction) {
    echo "Sample Transaction Structure:\n";
    foreach ($transaction as $key => $value) {
        echo "  - {$key}: " . (is_null($value) ? 'NULL' : $value) . "\n";
    }
    echo "\n";
    
    // Check if there's a customer_id or virtual_account_id
    if (isset($transaction->virtual_account_id) && $transaction->virtual_account_id) {
        echo "Virtual Account ID found: {$transaction->virtual_account_id}\n";
        
        $va = DB::table('virtual_accounts')->where('id', $transaction->virtual_account_id)->first();
        if ($va) {
            echo "Virtual Account Details:\n";
            echo "  - Account Name: {$va->account_name}\n";
            echo "  - Account Number: {$va->account_number}\n";
            
            // Check if customer_id exists before accessing it
            if (isset($va->customer_id) && $va->customer_id) {
                echo "  - Customer ID: {$va->customer_id}\n\n";
                
                // Get customer details
                $customer = DB::table('company_users')->where('id', $va->customer_id)->first();
                if ($customer) {
                    echo "Customer Details:\n";
                    echo "  - Name: {$customer->name}\n";
                    echo "  - Email: {$customer->email}\n";
                    echo "  - Phone: " . ($customer->phone ?? 'N/A') . "\n";
                }
            } else {
                echo "  - Customer ID: Not set\n";
            }
        }
    } else {
        echo "No virtual account linked to this transaction\n";
    }
    
    // Check metadata
    if (isset($transaction->metadata)) {
        echo "\nMetadata: {$transaction->metadata}\n";
        $meta = json_decode($transaction->metadata, true);
        if ($meta) {
            echo "Parsed Metadata:\n";
            print_r($meta);
        }
    }
}

echo "\n=== END ===\n";
