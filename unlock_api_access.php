<?php

/**
 * Unlock API Access for a Company
 * 
 * This script unlocks API access by setting both status='active' and is_active=true
 * 
 * Usage: php unlock_api_access.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;

echo "=== API Access Unlock Tool ===\n\n";

// Get company name from user
echo "Enter company name or ID: ";
$input = trim(fgets(STDIN));

if (empty($input)) {
    echo "Error: Company name or ID is required\n";
    exit(1);
}

// Find company by ID or name
if (is_numeric($input)) {
    $company = Company::find($input);
} else {
    $company = Company::where('name', 'LIKE', "%{$input}%")->first();
}

if (!$company) {
    echo "Error: Company not found\n";
    echo "\nAvailable companies:\n";
    $companies = Company::select('id', 'name', 'status', 'is_active')->get();
    foreach ($companies as $c) {
        $locked = !$c->isActive() ? '🔒 LOCKED' : '✓ Active';
        echo "  - ID: {$c->id} | Name: {$c->name} | {$locked}\n";
    }
    exit(1);
}

echo "\n=== Current Status ===\n";
echo "Company: {$company->name} (ID: {$company->id})\n";
echo "status: {$company->status}\n";
echo "is_active: " . ($company->is_active ? 'true' : 'false') . "\n";
echo "API Access: " . ($company->isActive() ? '✓ Unlocked' : '🔒 LOCKED') . "\n";

if ($company->isActive()) {
    echo "\n✅ API access is already unlocked. No action needed.\n";
    exit(0);
}

echo "\n⚠️  API access is currently LOCKED\n";
echo "\nDo you want to unlock API access? (yes/no): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) !== 'yes') {
    echo "Operation cancelled.\n";
    exit(0);
}

// Unlock API access
try {
    $company->status = 'active';
    $company->is_active = true;
    $company->save();
    
    echo "\n✅ SUCCESS! API access has been unlocked.\n\n";
    echo "=== Updated Status ===\n";
    echo "status: {$company->status}\n";
    echo "is_active: " . ($company->is_active ? 'true' : 'false') . "\n";
    echo "API Access: " . ($company->isActive() ? '✓ Unlocked' : '🔒 LOCKED') . "\n";
    
    echo "\n📝 The company can now make API calls successfully.\n";
    echo "They should retry creating customers and virtual accounts.\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: Failed to unlock API access\n";
    echo "Error: {$e->getMessage()}\n";
    exit(1);
}

echo "\n";
