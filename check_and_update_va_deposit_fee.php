<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING VA DEPOSIT FEE CONFIGURATION ===\n\n";

// Check settings table
$settings = DB::table('settings')->first();

if ($settings) {
    echo "Current System Settings:\n";
    
    // Check for VA deposit fee fields
    $fields = ['va_deposit_fee_type', 'va_deposit_fee_value', 'va_deposit_fee_cap'];
    
    foreach ($fields as $field) {
        if (property_exists($settings, $field)) {
            echo "  {$field}: " . $settings->$field . "\n";
        } else {
            echo "  {$field}: NOT SET\n";
        }
    }
    
    echo "\n";
} else {
    echo "⚠️  No settings record found\n\n";
}

// Check company-specific fees
echo "Company-Specific Fee Configurations:\n";
$companies = DB::table('companies')
    ->select('id', 'name', 'custom_va_deposit_fee_enabled', 'custom_va_deposit_fee_type', 'custom_va_deposit_fee_value', 'custom_va_deposit_fee_cap')
    ->get();

foreach ($companies as $company) {
    echo "\nCompany: {$company->name} (ID: {$company->id})\n";
    
    if (property_exists($company, 'custom_va_deposit_fee_enabled') && $company->custom_va_deposit_fee_enabled) {
        echo "  Custom Fee: ENABLED\n";
        echo "  Type: " . ($company->custom_va_deposit_fee_type ?? 'NOT SET') . "\n";
        echo "  Value: " . ($company->custom_va_deposit_fee_value ?? 'NOT SET') . "\n";
        echo "  Cap: " . ($company->custom_va_deposit_fee_cap ?? 'NOT SET') . "\n";
    } else {
        echo "  Custom Fee: DISABLED (using system default)\n";
    }
}

echo "\n=== WOULD YOU LIKE TO UPDATE THE FEE? ===\n";
echo "Current fee: 0.5% (₦0.50 on ₦100)\n";
echo "What would you like to change it to?\n\n";
echo "Options:\n";
echo "1. Update system default fee (affects all companies without custom fees)\n";
echo "2. Update specific company fee\n";
echo "3. Exit\n\n";
echo "Enter choice (1-3): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$choice = trim($line);

if ($choice == '1') {
    echo "\nEnter new fee percentage (e.g., 1 for 1%, 0.5 for 0.5%): ";
    $line = fgets($handle);
    $newFee = trim($line);
    
    echo "Enter fee cap in Naira (e.g., 500 for ₦500 max, or 0 for no cap): ";
    $line = fgets($handle);
    $cap = trim($line);
    
    fclose($handle);
    
    // Update settings
    if ($settings) {
        DB::table('settings')
            ->where('id', $settings->id)
            ->update([
                'va_deposit_fee_type' => 'PERCENT',
                'va_deposit_fee_value' => $newFee,
                'va_deposit_fee_cap' => $cap > 0 ? $cap : null,
                'updated_at' => now()
            ]);
        
        echo "\n✅ System default fee updated to {$newFee}%";
        if ($cap > 0) {
            echo " (capped at ₦{$cap})";
        }
        echo "\n";
    } else {
        echo "\n❌ No settings record found. Cannot update.\n";
    }
    
} elseif ($choice == '2') {
    echo "\nEnter company ID: ";
    $line = fgets($handle);
    $companyId = trim($line);
    
    echo "Enter new fee percentage (e.g., 1 for 1%, 0.5 for 0.5%): ";
    $line = fgets($handle);
    $newFee = trim($line);
    
    echo "Enter fee cap in Naira (e.g., 500 for ₦500 max, or 0 for no cap): ";
    $line = fgets($handle);
    $cap = trim($line);
    
    fclose($handle);
    
    // Update company
    DB::table('companies')
        ->where('id', $companyId)
        ->update([
            'custom_va_deposit_fee_enabled' => true,
            'custom_va_deposit_fee_type' => 'PERCENT',
            'custom_va_deposit_fee_value' => $newFee,
            'custom_va_deposit_fee_cap' => $cap > 0 ? $cap : null,
            'updated_at' => now()
        ]);
    
    echo "\n✅ Company {$companyId} fee updated to {$newFee}%";
    if ($cap > 0) {
        echo " (capped at ₦{$cap})";
    }
    echo "\n";
    
} else {
    fclose($handle);
    echo "\nExiting...\n";
}

echo "\n=== DONE ===\n";
