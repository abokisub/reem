#!/usr/bin/env php
<?php

/**
 * Check KoboPoint's PointWave Business Wallet Balance
 * 
 * Run this on the server to see the actual balance
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "KoboPoint Balance Check\n";
echo "========================================\n\n";

// Find KoboPoint company
$kobopoint = DB::table('companies')
    ->where('name', 'LIKE', '%kobo%')
    ->orWhere('name', 'LIKE', '%Kobo%')
    ->get();

if ($kobopoint->isEmpty()) {
    echo "❌ KoboPoint company not found\n";
    echo "\nSearching for all companies:\n";
    $allCompanies = DB::table('companies')->select('id', 'name', 'email')->get();
    foreach ($allCompanies as $company) {
        echo "  - ID: {$company->id}, Name: {$company->name}, Email: {$company->email}\n";
    }
    exit(1);
}

echo "Found KoboPoint companies:\n";
foreach ($kobopoint as $company) {
    echo "\n";
    echo "Company ID: {$company->id}\n";
    echo "Company Name: {$company->name}\n";
    echo "Email: {$company->email}\n";
    
    // Get wallet balance
    $wallet = DB::table('company_wallets')
        ->where('company_id', $company->id)
        ->where('currency', 'NGN')
        ->first();
    
    if ($wallet) {
        echo "Wallet Balance: ₦" . number_format($wallet->balance, 2) . "\n";
        echo "Ledger Balance: ₦" . number_format($wallet->ledger_balance, 2) . "\n";
        echo "Pending Balance: ₦" . number_format($wallet->pending_balance ?? 0, 2) . "\n";
        
        // Check if balance is sufficient for ₦100 transfer
        $transferAmount = 100;
        $fee = 30;
        $totalRequired = $transferAmount + $fee;
        
        echo "\n";
        echo "Transfer Test (₦100):\n";
        echo "  Amount: ₦{$transferAmount}\n";
        echo "  Fee: ₦{$fee}\n";
        echo "  Total Required: ₦{$totalRequired}\n";
        
        if ($wallet->balance >= $totalRequired) {
            echo "  ✅ SUFFICIENT BALANCE\n";
        } else {
            echo "  ❌ INSUFFICIENT BALANCE\n";
            echo "  Need to add: ₦" . number_format($totalRequired - $wallet->balance, 2) . "\n";
        }
    } else {
        echo "❌ No wallet found for this company\n";
    }
    
    echo "\n";
    echo "----------------------------------------\n";
}

echo "\n";
echo "To check on server, run:\n";
echo "cd /home/aboksdfs/app.pointwave.ng\n";
echo "php check_kobopoint_balance.php\n";
