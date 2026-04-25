<?php
/**
 * Diagnose Live KYC Pool System
 * Run: php diagnose_live_kyc_pool.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\GlobalKycPool;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\VirtualAccount;

echo "========================================\n";
echo "KYC POOL SYSTEM DIAGNOSTIC REPORT\n";
echo "========================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

// 1. Pool Statistics
echo "=== GLOBAL KYC POOL STATISTICS ===\n";
$pool = GlobalKycPool::all();
$totalEntries = $pool->count();
$activeEntries = $pool->where('is_active', true)->count();
$ninEntries = $pool->where('kyc_type', 'nin')->count();
$bvnEntries = $pool->where('kyc_type', 'bvn')->count();

echo "Total Entries: $totalEntries\n";
echo "Active Entries: $activeEntries\n";
echo "NIN Entries: $ninEntries\n";
echo "BVN Entries: $bvnEntries\n";

// Check exhausted
$exhausted = 0;
$blacklisted = 0;
$available = 0;

foreach ($pool as $entry) {
    if ($entry->max_usage && $entry->usage_count >= $entry->max_usage) {
        $exhausted++;
    }
    if ($entry->blacklisted_until && $entry->blacklisted_until > now()) {
        $blacklisted++;
    }
    if ($entry->is_active && 
        (!$entry->max_usage || $entry->usage_count < $entry->max_usage) &&
        (!$entry->blacklisted_until || $entry->blacklisted_until <= now())) {
        $available++;
    }
}

echo "Exhausted: $exhausted\n";
echo "Blacklisted: $blacklisted\n";
echo "Available: $available\n\n";

// 2. Pool Entries Detail
echo "=== POOL ENTRIES DETAIL ===\n";
foreach ($pool->sortBy('kyc_type')->sortBy('usage_count') as $entry) {
    $status = 'ACTIVE';
    if (!$entry->is_active) $status = 'INACTIVE';
    elseif ($entry->max_usage && $entry->usage_count >= $entry->max_usage) $status = 'EXHAUSTED';
    elseif ($entry->blacklisted_until && $entry->blacklisted_until > now()) $status = 'BLACKLISTED';
    
    $masked = substr($entry->kyc_number, 0, 5) . '***';
    echo sprintf(
        "[%s] %s: %s | Used: %d/%s | Success: %d | Failures: %d | Status: %s\n",
        strtoupper($entry->kyc_type),
        $masked,
        $entry->kyc_number,
        $entry->usage_count,
        $entry->max_usage ?? '∞',
        $entry->success_count,
        $entry->failure_count,
        $status
    );
}
echo "\n";

// 3. Company KYC Health
echo "=== COMPANY KYC HEALTH ===\n";
$companies = Company::whereNotNull('director_nin')
    ->orWhereNotNull('director_bvn')
    ->get();

foreach ($companies as $company) {
    echo "\nCompany: {$company->name} (ID: {$company->id})\n";
    echo "  Director NIN: " . ($company->director_nin ? substr($company->director_nin, 0, 5) . '***' : 'Not Set') . "\n";
    echo "  Director BVN: " . ($company->director_bvn ? substr($company->director_bvn, 0, 5) . '***' : 'Not Set') . "\n";
    echo "  KYC Refreshed: " . ($company->kyc_refreshed_at ? $company->kyc_refreshed_at->format('Y-m-d H:i:s') : 'Never') . "\n";
    
    // Count VAs since last refresh
    $vaQuery = VirtualAccount::where('company_id', $company->id)
        ->whereNotNull('palmpay_account_number');
    
    if ($company->kyc_refreshed_at) {
        $vaQuery->where('created_at', '>=', $company->kyc_refreshed_at);
    }
    
    $vaCount = $vaQuery->count();
    $maxLimit = 130;
    $usagePct = $maxLimit > 0 ? round(($vaCount / $maxLimit) * 100) : 0;
    
    echo "  VAs Created: $vaCount / $maxLimit ($usagePct%)\n";
    
    // Check if NIN is in pool and exhausted
    if ($company->director_nin) {
        $poolEntry = GlobalKycPool::where('kyc_number', $company->director_nin)->first();
        if ($poolEntry) {
            $poolStatus = 'In Pool';
            if ($poolEntry->max_usage && $poolEntry->usage_count >= $poolEntry->max_usage) {
                $poolStatus .= ' - EXHAUSTED';
            }
            echo "  Pool Status: $poolStatus (Used: {$poolEntry->usage_count}/{$poolEntry->max_usage})\n";
        } else {
            echo "  Pool Status: NOT IN POOL\n";
        }
    }
    
    // Health status
    $status = 'HEALTHY';
    if ($usagePct >= 80) $status = 'WARNING';
    if ($usagePct >= 100) $status = 'CRITICAL';
    echo "  Health: $status\n";
}
echo "\n";

// 4. Missing Virtual Accounts
echo "=== MISSING VIRTUAL ACCOUNTS ===\n";
$customers = CompanyUser::all();
$missing = [];

foreach ($customers as $customer) {
    $va = VirtualAccount::where('company_user_id', $customer->id)
        ->whereNull('deleted_at')
        ->first();
    
    if (!$va) {
        $company = Company::find($customer->company_id);
        $missing[] = [
            'customer_id' => $customer->id,
            'customer_name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
            'company_name' => $company->name ?? 'Unknown',
            'email' => $customer->email,
        ];
    }
}

if (empty($missing)) {
    echo "✅ All customers have virtual accounts!\n";
} else {
    echo "⚠️  Found " . count($missing) . " customers without virtual accounts:\n";
    foreach ($missing as $m) {
        echo "  - {$m['customer_name']} ({$m['email']}) at {$m['company_name']}\n";
    }
}
echo "\n";

// 5. System Health Check
echo "=== SYSTEM HEALTH CHECK ===\n";
if ($available > 0) {
    echo "✅ Pool has $available available KYC entries\n";
} else {
    echo "❌ WARNING: No available KYC entries in pool!\n";
}

if (empty($missing)) {
    echo "✅ All customers have virtual accounts\n";
} else {
    echo "⚠️  " . count($missing) . " customers need virtual accounts\n";
}

$criticalCompanies = 0;
foreach ($companies as $company) {
    $vaQuery = VirtualAccount::where('company_id', $company->id)
        ->whereNotNull('palmpay_account_number');
    if ($company->kyc_refreshed_at) {
        $vaQuery->where('created_at', '>=', $company->kyc_refreshed_at);
    }
    $vaCount = $vaQuery->count();
    if ($vaCount >= 130) $criticalCompanies++;
}

if ($criticalCompanies > 0) {
    echo "⚠️  $criticalCompanies companies need KYC refresh\n";
} else {
    echo "✅ All companies have healthy KYC usage\n";
}

echo "\n========================================\n";
echo "DIAGNOSTIC COMPLETE\n";
echo "========================================\n";
