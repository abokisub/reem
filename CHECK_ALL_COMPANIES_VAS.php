<?php

/**
 * Comprehensive Virtual Account Safety Check
 * Checks all companies for missing VAs, duplicate users, and KYC issues
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     VIRTUAL ACCOUNT SAFETY CHECK - ALL COMPANIES              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Get all active companies
$companies = App\Models\Company::where('status', 'active')->get();

$totalCompanies = $companies->count();
$companiesWithIssues = 0;
$totalMissingVAs = 0;
$totalDuplicateUsers = 0;

$issuesList = [];

echo "Checking {$totalCompanies} active companies...\n\n";
echo str_repeat("=", 80) . "\n\n";

foreach ($companies as $company) {
    $hasIssues = false;
    $issues = [];
    
    // Count total users
    $totalUsers = App\Models\CompanyUser::where('company_id', $company->id)
        ->where('status', 'active')
        ->count();
    
    // Count users without VAs
    $usersWithoutVA = App\Models\CompanyUser::where('company_id', $company->id)
        ->where('status', 'active')
        ->whereDoesntHave('virtualAccounts')
        ->count();
    
    // Count total VAs
    $totalVAs = App\Models\VirtualAccount::where('company_id', $company->id)
        ->whereNull('deleted_at')
        ->count();
    
    // Find duplicate users (same email)
    $duplicateEmails = App\Models\CompanyUser::where('company_id', $company->id)
        ->where('status', 'active')
        ->select('email', \DB::raw('COUNT(*) as count'))
        ->groupBy('email')
        ->having('count', '>', 1)
        ->get();
    
    $duplicateCount = $duplicateEmails->sum('count') - $duplicateEmails->count();
    
    // Check KYC configuration
    $hasDirectorKyc = !empty($company->director_bvn) || !empty($company->director_nin);
    $hasBackupKyc = false;
    for ($i = 2; $i <= 10; $i++) {
        if (!empty($company->{"backup_director_{$i}_bvn"}) || !empty($company->{"backup_director_{$i}_nin"})) {
            $hasBackupKyc = true;
            break;
        }
    }
    
    // Identify issues
    if ($usersWithoutVA > 0) {
        $hasIssues = true;
        $issues[] = "❌ {$usersWithoutVA} users without virtual accounts";
        $totalMissingVAs += $usersWithoutVA;
    }
    
    if ($duplicateCount > 0) {
        $hasIssues = true;
        $issues[] = "⚠️  {$duplicateCount} duplicate user registrations";
        $totalDuplicateUsers += $duplicateCount;
    }
    
    if (!$hasDirectorKyc && $totalUsers > 0) {
        $hasIssues = true;
        $issues[] = "⚠️  No director KYC configured";
    }
    
    if ($totalUsers > 50 && !$hasBackupKyc) {
        $hasIssues = true;
        $issues[] = "⚠️  No backup KYC (recommended for {$totalUsers} users)";
    }
    
    // Check for blacklisted KYC
    if (!empty($company->kyc_method_blacklist)) {
        $blacklisted = json_decode($company->kyc_method_blacklist, true);
        if (is_array($blacklisted) && count($blacklisted) > 0) {
            $issues[] = "ℹ️  " . count($blacklisted) . " KYC methods blacklisted";
        }
    }
    
    // Display company status
    if ($hasIssues) {
        $companiesWithIssues++;
        echo "🔴 COMPANY ID {$company->id}: {$company->name}\n";
        echo "   Users: {$totalUsers} | VAs: {$totalVAs}\n";
        foreach ($issues as $issue) {
            echo "   {$issue}\n";
        }
        echo "\n";
        
        $issuesList[] = [
            'id' => $company->id,
            'name' => $company->name,
            'users' => $totalUsers,
            'vas' => $totalVAs,
            'missing' => $usersWithoutVA,
            'duplicates' => $duplicateCount,
            'issues' => $issues
        ];
    } else {
        echo "✅ COMPANY ID {$company->id}: {$company->name}\n";
        echo "   Users: {$totalUsers} | VAs: {$totalVAs} | Status: OK\n\n";
    }
}

echo str_repeat("=", 80) . "\n\n";

// Summary
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                         SUMMARY                                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "Total Companies Checked: {$totalCompanies}\n";
echo "Companies with Issues: {$companiesWithIssues}\n";
echo "Companies OK: " . ($totalCompanies - $companiesWithIssues) . "\n\n";

echo "Total Missing VAs: {$totalMissingVAs}\n";
echo "Total Duplicate Users: {$totalDuplicateUsers}\n\n";

if ($companiesWithIssues > 0) {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║                    RECOMMENDED ACTIONS                         ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    foreach ($issuesList as $item) {
        echo "Company: {$item['name']} (ID: {$item['id']})\n";
        
        if ($item['missing'] > 0) {
            echo "  → Run: php artisan kyc:regenerate-missing-accounts --company-id={$item['id']} --assign-fresh-kyc\n";
        }
        
        if ($item['duplicates'] > 0) {
            echo "  → Review duplicate users: Check if they should share VAs or be deleted\n";
        }
        
        echo "\n";
    }
} else {
    echo "🎉 ALL COMPANIES ARE SAFE! No issues found.\n\n";
}

// Global KYC Pool Status
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                   GLOBAL KYC POOL STATUS                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$globalKycService = new App\Services\GlobalKycService();
$stats = $globalKycService->getUsageStats();

echo "Total KYC Numbers: {$stats['total_kyc_numbers']}\n";
echo "Available: {$stats['available']}\n";
echo "In Use: {$stats['in_use']}\n";
echo "Exhausted: {$stats['exhausted']}\n";
echo "Blacklisted: {$stats['blacklisted']}\n\n";

if ($stats['available'] < 5) {
    echo "⚠️  WARNING: Low KYC availability! Consider adding more KYC numbers.\n\n";
}

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                      CHECK COMPLETE                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
