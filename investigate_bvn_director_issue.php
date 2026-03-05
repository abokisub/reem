<?php
// Investigate what BVN/license number is being sent to PalmPay
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\VirtualAccount;

echo "🔍 INVESTIGATING BVN DIRECTOR ISSUE\n";
echo "==================================\n\n";

// Check Company 4 (KoboPoint) director BVN
echo "📋 COMPANY 4 (KOBOPOINT) DIRECTOR BVN:\n";
$company = Company::find(4);

if ($company) {
    echo "- Company Name: {$company->name}\n";
    echo "- Director BVN: " . ($company->director_bvn ?? 'NOT SET') . "\n";
    echo "- Director NIN: " . ($company->director_nin ?? 'NOT SET') . "\n";
    echo "- Business Registration: " . ($company->business_registration_number ?? 'NOT SET') . "\n";
    echo "- KYC Status: " . ($company->kyc_status ?? 'NOT SET') . "\n\n";
} else {
    echo "❌ Company 4 not found!\n\n";
}

// Check recent virtual accounts to see what license numbers were used
echo "📋 RECENT VIRTUAL ACCOUNTS (LAST 24 HOURS):\n";
$recentAccounts = VirtualAccount::where('company_id', 4)
    ->where('created_at', '>=', now()->subDay())
    ->orderBy('created_at', 'desc')
    ->get();

echo "Found " . $recentAccounts->count() . " recent accounts:\n";
foreach ($recentAccounts as $account) {
    echo "- Account: {$account->account_number}\n";
    echo "  Customer: {$account->customer_name}\n";
    echo "  KYC Source: {$account->kyc_source}\n";
    echo "  Identity Type: {$account->identity_type}\n";
    echo "  Director BVN Used: " . ($account->director_bvn ?? 'None') . "\n";
    echo "  Customer BVN: " . ($account->bvn ?? 'None') . "\n";
    echo "  Customer NIN: " . ($account->nin ?? 'None') . "\n";
    echo "  Created: {$account->created_at}\n\n";
}

// Check if there are any accounts using customer BVN/NIN that might conflict
echo "📋 ACCOUNTS WITH CUSTOMER BVN/NIN:\n";
$customerKycAccounts = VirtualAccount::where('company_id', 4)
    ->where(function($query) {
        $query->whereNotNull('bvn')
              ->orWhereNotNull('nin');
    })
    ->get();

echo "Found " . $customerKycAccounts->count() . " accounts with customer KYC:\n";
foreach ($customerKycAccounts as $account) {
    echo "- Account: {$account->account_number}\n";
    echo "  Customer: {$account->customer_name}\n";
    echo "  Customer BVN: " . ($account->bvn ?? 'None') . "\n";
    echo "  Customer NIN: " . ($account->nin ?? 'None') . "\n";
    echo "  KYC Source: {$account->kyc_source}\n";
    echo "  Created: {$account->created_at}\n\n";
}

// Simulate what license number would be used for a new account
echo "📋 SIMULATING LICENSE NUMBER SELECTION:\n";
if ($company) {
    $customerData = [
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'phone' => '08012345678'
    ];
    
    $customerBvn = $customerData['bvn'] ?? null;
    $customerNin = $customerData['nin'] ?? null;
    
    echo "Customer Data:\n";
    echo "- Name: {$customerData['name']}\n";
    echo "- Email: {$customerData['email']}\n";
    echo "- Phone: {$customerData['phone']}\n";
    echo "- Customer BVN: " . ($customerBvn ?? 'None') . "\n";
    echo "- Customer NIN: " . ($customerNin ?? 'None') . "\n\n";
    
    // Simulate the KYC selection logic from VirtualAccountService
    $kycSource = 'director_bvn';
    $licenseNumber = null;
    $identityType = 'personal';
    
    if ($customerBvn) {
        $licenseNumber = $customerBvn;
        $identityType = 'personal';
        $kycSource = 'customer_bvn';
    } elseif ($customerNin) {
        $licenseNumber = $customerNin;
        $identityType = 'personal_nin';
        $kycSource = 'customer_nin';
    } elseif ($company->director_bvn) {
        $licenseNumber = $company->director_bvn;
        $identityType = 'personal';
        $kycSource = 'director_bvn';
    } elseif ($company->director_nin) {
        $licenseNumber = $company->director_nin;
        $identityType = 'personal_nin';
        $kycSource = 'director_nin';
    } else {
        $licenseNumber = $company->business_registration_number;
        $identityType = 'company';
        $kycSource = 'company_rc';
        
        // Add RC prefix if needed
        if ($identityType === 'company') {
            $licenseNumber = strtoupper(trim($licenseNumber));
            if (!str_starts_with($licenseNumber, 'RC') && !str_starts_with($licenseNumber, 'BN')) {
                $licenseNumber = 'RC' . $licenseNumber;
            }
        }
    }
    
    echo "SELECTED LICENSE NUMBER:\n";
    echo "- License Number: " . ($licenseNumber ?? 'NOT AVAILABLE') . "\n";
    echo "- Identity Type: $identityType\n";
    echo "- KYC Source: $kycSource\n\n";
    
    if (!$licenseNumber) {
        echo "❌ NO LICENSE NUMBER AVAILABLE!\n";
        echo "This would cause virtual account creation to fail.\n\n";
    }
}

echo "🔍 POSSIBLE ISSUES:\n";
echo "1. Director BVN not set in company record\n";
echo "2. Director BVN being reused by another company\n";
echo "3. Customer BVN/NIN conflicting with existing accounts\n";
echo "4. PalmPay has blacklisted the director BVN\n";
echo "5. License number format issue\n\n";

echo "💡 NEXT STEPS:\n";
echo "1. Verify company director BVN is properly set\n";
echo "2. Check if BVN is being used by other companies\n";
echo "3. Test with a different director BVN if available\n";
echo "4. Contact PalmPay about the specific BVN status\n";