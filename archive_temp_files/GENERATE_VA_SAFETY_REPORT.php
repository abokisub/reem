<?php

/**
 * Generate Detailed Virtual Account Safety Report
 * Creates a comprehensive report file with all company VA statuses
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$reportFile = 'VA_SAFETY_REPORT_' . date('Y-m-d_His') . '.md';

$report = "# Virtual Account Safety Report\n\n";
$report .= "**Generated:** " . date('Y-m-d H:i:s') . "\n\n";
$report .= "---\n\n";

// Get all companies
$companies = App\Models\Company::orderBy('id')->get();

$report .= "## Executive Summary\n\n";
$report .= "| Metric | Count |\n";
$report .= "|--------|-------|\n";
$report .= "| Total Companies | {$companies->count()} |\n";

$totalUsers = App\Models\CompanyUser::count();
$totalVAs = App\Models\VirtualAccount::whereNull('deleted_at')->count();
$usersWithoutVA = App\Models\CompanyUser::whereDoesntHave('virtualAccounts')->count();

$report .= "| Total Users | {$totalUsers} |\n";
$report .= "| Total Virtual Accounts | {$totalVAs} |\n";
$report .= "| Users Without VA | {$usersWithoutVA} |\n\n";

$report .= "---\n\n";
$report .= "## Company Details\n\n";

foreach ($companies as $company) {
    $report .= "### {$company->name} (ID: {$company->id})\n\n";
    
    $totalUsers = App\Models\CompanyUser::where('company_id', $company->id)->count();
    $activeUsers = App\Models\CompanyUser::where('company_id', $company->id)
        ->where('status', 'active')
        ->count();
    $usersWithoutVA = App\Models\CompanyUser::where('company_id', $company->id)
        ->whereDoesntHave('virtualAccounts')
        ->count();
    $totalVAs = App\Models\VirtualAccount::where('company_id', $company->id)
        ->whereNull('deleted_at')
        ->count();
    
    $report .= "**Status:** {$company->status}\n\n";
    $report .= "| Metric | Count |\n";
    $report .= "|--------|-------|\n";
    $report .= "| Total Users | {$totalUsers} |\n";
    $report .= "| Active Users | {$activeUsers} |\n";
    $report .= "| Users Without VA | {$usersWithoutVA} |\n";
    $report .= "| Total Virtual Accounts | {$totalVAs} |\n\n";
    
    // KYC Configuration
    $report .= "**KYC Configuration:**\n";
    $report .= "- Director BVN: " . (!empty($company->director_bvn) ? "✅ Configured" : "❌ Not set") . "\n";
    $report .= "- Director NIN: " . (!empty($company->director_nin) ? "✅ Configured" : "❌ Not set") . "\n";
    
    $backupCount = 0;
    for ($i = 2; $i <= 10; $i++) {
        if (!empty($company->{"backup_director_{$i}_bvn"}) || !empty($company->{"backup_director_{$i}_nin"})) {
            $backupCount++;
        }
    }
    $report .= "- Backup Directors: {$backupCount}/9 configured\n";
    
    if (!empty($company->preferred_kyc_method)) {
        $report .= "- Preferred KYC: {$company->preferred_kyc_method}\n";
    }
    
    if (!empty($company->kyc_method_blacklist)) {
        $blacklisted = json_decode($company->kyc_method_blacklist, true);
        if (is_array($blacklisted) && count($blacklisted) > 0) {
            $report .= "- Blacklisted KYC: " . implode(', ', $blacklisted) . "\n";
        }
    }
    
    $report .= "\n";
    
    // Issues
    if ($usersWithoutVA > 0) {
        $report .= "**⚠️ ISSUES:**\n";
        $report .= "- {$usersWithoutVA} users are missing virtual accounts\n";
        $report .= "- **Action Required:** Run `php artisan kyc:regenerate-missing-accounts --company-id={$company->id} --assign-fresh-kyc`\n\n";
    } else {
        $report .= "**✅ Status:** All users have virtual accounts\n\n";
    }
    
    // Find duplicate users
    $duplicates = App\Models\CompanyUser::where('company_id', $company->id)
        ->select('email', \DB::raw('COUNT(*) as count'))
        ->groupBy('email')
        ->having('count', '>', 1)
        ->get();
    
    if ($duplicates->count() > 0) {
        $report .= "**⚠️ Duplicate Users Found:**\n";
        foreach ($duplicates as $dup) {
            $report .= "- Email: {$dup->email} ({$dup->count} registrations)\n";
        }
        $report .= "\n";
    }
    
    $report .= "---\n\n";
}

// Global KYC Pool
$report .= "## Global KYC Pool Status\n\n";

$globalKycService = new App\Services\GlobalKycService();
$stats = $globalKycService->getUsageStats();

$report .= "| Status | Count |\n";
$report .= "|--------|-------|\n";
$report .= "| Total KYC Numbers | {$stats['total_kyc_numbers']} |\n";
$report .= "| Available | {$stats['available']} |\n";
$report .= "| In Use | {$stats['in_use']} |\n";
$report .= "| Exhausted | {$stats['exhausted']} |\n";
$report .= "| Blacklisted | {$stats['blacklisted']} |\n\n";

if ($stats['available'] < 5) {
    $report .= "**⚠️ WARNING:** Low KYC availability! Consider adding more KYC numbers to the pool.\n\n";
}

// Recent KYC Usage
$recentUsage = App\Models\GlobalKycUsageLog::orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($recentUsage->count() > 0) {
    $report .= "### Recent KYC Usage (Last 10)\n\n";
    $report .= "| Date | Company | KYC Number | Status |\n";
    $report .= "|------|---------|------------|--------|\n";
    
    foreach ($recentUsage as $usage) {
        $company = App\Models\Company::find($usage->company_id);
        $companyName = $company ? $company->name : "Unknown";
        $date = $usage->created_at->format('Y-m-d H:i');
        $kycNumber = substr($usage->kyc_number, 0, 5) . '***';
        $status = $usage->success ? '✅ Success' : '❌ Failed';
        
        $report .= "| {$date} | {$companyName} | {$kycNumber} | {$status} |\n";
    }
    $report .= "\n";
}

$report .= "---\n\n";
$report .= "## Recommendations\n\n";

if ($usersWithoutVA > 0) {
    $report .= "1. **Fix Missing Virtual Accounts:** Run the regeneration command for affected companies\n";
}

if ($stats['available'] < 10) {
    $report .= "2. **Add More KYC Numbers:** Current pool has only {$stats['available']} available KYC numbers\n";
}

$report .= "3. **Monitor KYC Usage:** Regularly check for exhausted KYC numbers and rotate as needed\n";
$report .= "4. **Review Duplicates:** Check duplicate user registrations and decide if they should share VAs or be removed\n\n";

$report .= "---\n\n";
$report .= "*Report generated by Pointwave Virtual Account Safety Check System*\n";

// Save report
file_put_contents($reportFile, $report);

echo "✅ Report generated successfully!\n\n";
echo "Report saved to: {$reportFile}\n\n";
echo "You can view it with: cat {$reportFile}\n";
