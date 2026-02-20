<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=================================================================\n";
echo "CHECKING ADMIN ENDPOINTS\n";
echo "=================================================================\n\n";

// 1. Check webhook_logs table
echo "1. Checking webhook_logs table:\n";
echo "-----------------------------------------------------------\n";
try {
    $webhookCount = DB::table('webhook_logs')->count();
    echo "✓ webhook_logs table exists\n";
    echo "  Total records: $webhookCount\n";
    
    if ($webhookCount > 0) {
        $sample = DB::table('webhook_logs')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();
        
        echo "\n  Sample records:\n";
        foreach ($sample as $log) {
            echo "  - ID: {$log->id}, Company ID: {$log->company_id}, Created: {$log->created_at}\n";
        }
    } else {
        echo "  ⚠️ Table is empty\n";
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Check transactions table for statement
echo "2. Checking transactions table for statement:\n";
echo "-----------------------------------------------------------\n";
try {
    $transCount = DB::table('transactions')->count();
    echo "✓ transactions table exists\n";
    echo "  Total records: $transCount\n";
    
    if ($transCount > 0) {
        // Test the statement query
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-d');
        
        $statement = DB::table('transactions')
            ->leftJoin('companies', 'transactions.company_id', '=', 'companies.id')
            ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
            ->leftJoin('customers', 'transactions.customer_id', '=', 'customers.id')
            ->whereBetween('transactions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select(
                'transactions.id',
                'transactions.reference',
                'transactions.amount',
                'transactions.fee as charges',
                'transactions.type',
                'transactions.status',
                'transactions.description',
                'transactions.created_at',
                'transactions.recipient_account_number as customer_account_number',
                'transactions.recipient_account_name as customer_name',
                'users.username',
                'companies.name as company_name'
            )
            ->orderBy('transactions.created_at', 'desc')
            ->limit(5)
            ->get();
        
        echo "\n  Sample statement records (this month):\n";
        foreach ($statement as $trans) {
            echo "  - Ref: {$trans->reference}, Amount: {$trans->amount}, Company: {$trans->company_name}\n";
        }
        
        // Test summary
        $summary = DB::table('transactions')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END) as total_credit,
                SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END) as total_debit,
                SUM(fee) as total_charges
            ')
            ->first();
        
        echo "\n  Summary for this month:\n";
        echo "  - Total transactions: {$summary->total_count}\n";
        echo "  - Total credit: ₦{$summary->total_credit}\n";
        echo "  - Total debit: ₦{$summary->total_debit}\n";
        echo "  - Total charges: ₦{$summary->total_charges}\n";
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Check if customers table exists (used in statement join)
echo "3. Checking customers table:\n";
echo "-----------------------------------------------------------\n";
try {
    $customerCount = DB::table('customers')->count();
    echo "✓ customers table exists\n";
    echo "  Total records: $customerCount\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "  This might cause issues with the statement query\n";
}

echo "\n=================================================================\n";
echo "CHECK COMPLETE\n";
echo "=================================================================\n";
