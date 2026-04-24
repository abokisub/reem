<?php

/**
 * Diagnostic Script for Live KYC Pool Issues
 * 
 * Usage: php diagnose_live_kyc_pool.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\GlobalKycPool;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Live KYC Pool Diagnostic Report\n";
echo "========================================\n\n";

// Check database connection
echo "1. Database Connection\n";
echo "----------------------------------------\n";
try {
    DB::connection()->getPdo();
    echo "✅ Database connected successfully\n";
    echo "   Database: " . DB::connection()->getDatabaseName() . "\n\n";
} catch (\Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Check global_kyc_pool table structure
echo "2. Table Structure Check\n";
echo "----------------------------------------\n";
try {
    $columns = DB::select("DESCRIBE global_kyc_pool");
    echo "✅ Table 'global_kyc_pool' exists\n";
    echo "   Columns: " . count($columns) . "\n";
    foreach ($columns as $col) {
        echo "   - {$col->Field} ({$col->Type})\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "❌ Table check failed: " . $e->getMessage() . "\n\n";
}

// Check actual pool entries
echo "3. Pool Entries Analysis\n";
echo "----------------------------------------\n";
$allEntries = GlobalKycPool::all();
echo "Total entries in database: " . $allEntries->count() . "\n\n";

if ($allEntries->count() > 0) {
    echo "Sample entries (first 5):\n";
    foreach ($allEntries->take(5) as $entry) {
        echo "  ID: {$entry->id}\n";
        echo "    Type: {$entry->kyc_type}\n";
        echo "    Number: " . substr($entry->kyc_number, 0, 5) . "***\n";
        echo "    Active: " . ($entry->is_active ? 'Yes' : 'No') . "\n";
        echo "    Usage: {$entry->usage_count}/{$entry->max_usage}\n";
        echo "    Success: {$entry->success_count}, Failures: {$entry->failure_count}\n";
        echo "    Blacklisted: " . ($entry->isBlacklisted() ? 'Yes' : 'No') . "\n";
        if ($entry->blacklisted_until) {
            echo "    Blacklist expires: {$entry->blacklisted_until}\n";
        }
        echo "    Last used: " . ($entry->last_used_at ?? 'Never') . "\n";
        echo "\n";
    }
}

// Check available entries
echo "4. Available Entries Check\n";
echo "----------------------------------------\n";
try {
    $availableNins = GlobalKycPool::available()->where('kyc_type', 'nin')->get();
    $availableBvns = GlobalKycPool::available()->where('kyc_type', 'bvn')->get();
    
    echo "Available NIns: " . $availableNins->count() . "\n";
    if ($availableNins->count() > 0) {
        echo "  Sample NIN: " . substr($availableNins->first()->kyc_number, 0, 5) . "*** (usage: {$availableNins->first()->usage_count})\n";
    }
    
    echo "Available BVNs: " . $availableBvns->count() . "\n";
    if ($availableBvns->count() > 0) {
        echo "  Sample BVN: " . substr($availableBvns->first()->kyc_number, 0, 5) . "*** (usage: {$availableBvns->first()->usage_count})\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "❌ Available check failed: " . $e->getMessage() . "\n\n";
}

// Check exhausted entries
echo "5. Exhausted Entries\n";
echo "----------------------------------------\n";
$exhausted = $allEntries->filter(fn($p) => $p->max_usage && $p->usage_count >= $p->max_usage);
echo "Exhausted entries: " . $exhausted->count() . "\n";
if ($exhausted->count() > 0) {
    foreach ($exhausted->take(3) as $entry) {
        echo "  - " . substr($entry->kyc_number, 0, 5) . "*** ({$entry->kyc_type}): {$entry->usage_count}/{$entry->max_usage}\n";
    }
}
echo "\n";

// Check blacklisted entries
echo "6. Blacklisted Entries\n";
echo "----------------------------------------\n";
$blacklisted = $allEntries->filter(fn($p) => $p->isBlacklisted());
echo "Blacklisted entries: " . $blacklisted->count() . "\n";
if ($blacklisted->count() > 0) {
    foreach ($blacklisted->take(3) as $entry) {
        echo "  - " . substr($entry->kyc_number, 0, 5) . "*** ({$entry->kyc_type}): expires at {$entry->blacklisted_until}\n";
    }
}
echo "\n";

// Check companies
echo "7. Company KYC Status\n";
echo "----------------------------------------\n";
$companies = Company::whereNotNull('director_nin')->orWhereNotNull('director_bvn')->get();
echo "Companies with KYC: " . $companies->count() . "\n\n";

foreach ($companies->take(3) as $company) {
    echo "Company: {$company->name}\n";
    echo "  Director NIN: " . ($company->director_nin ? substr($company->director_nin, 0, 5) . '***' : 'Not set') . "\n";
    echo "  Director BVN: " . ($company->director_bvn ? substr($company->director_bvn, 0, 5) . '***' : 'Not set') . "\n";
    echo "  Blacklist: " . ($company->kyc_method_blacklist ?? 'None') . "\n";
    echo "  Last refreshed: " . ($company->kyc_refreshed_at ?? 'Never') . "\n";
    
    // Check if director NIN is in pool
    if ($company->director_nin) {
        $poolEntry = GlobalKycPool::where('kyc_number', $company->director_nin)->first();
        if ($poolEntry) {
            echo "  ✅ Director NIN found in pool (usage: {$poolEntry->usage_count}/{$poolEntry->max_usage})\n";
        } else {
            echo "  ⚠️  Director NIN NOT in pool\n";
        }
    }
    echo "\n";
}

// Check GlobalKycPool model methods
echo "8. Model Methods Check\n";
echo "----------------------------------------\n";
try {
    $testEntry = GlobalKycPool::first();
    if ($testEntry) {
        echo "Testing model methods on entry ID {$testEntry->id}:\n";
        echo "  - isBlacklisted(): " . ($testEntry->isBlacklisted() ? 'true' : 'false') . "\n";
        echo "  - is_active: " . ($testEntry->is_active ? 'true' : 'false') . "\n";
        echo "  - blacklisted_until: " . ($testEntry->blacklisted_until ?? 'null') . "\n";
    }
    
    // Test available scope
    $availableCount = GlobalKycPool::available()->count();
    echo "\n  - available() scope returns: $availableCount entries\n";
    
    // Test leastUsedFirst scope
    $leastUsed = GlobalKycPool::available()->leastUsedFirst()->first();
    if ($leastUsed) {
        echo "  - leastUsedFirst() returns: ID {$leastUsed->id} (usage: {$leastUsed->usage_count})\n";
    }
} catch (\Exception $e) {
    echo "❌ Model methods check failed: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "Diagnostic Complete\n";
echo "========================================\n";
