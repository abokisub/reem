<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "Check Amtpay RC Number Issue\n";
echo "========================================\n\n";

// Find amtpay company
$company = DB::table('companies')
    ->where('email', 'amttelcom@gmail.com')
    ->orWhere('name', 'like', '%amtpay%')
    ->first();

if (!$company) {
    echo "❌ Company 'amtpay' not found\n";
    exit(1);
}

echo "Company Details:\n";
echo "  ID: {$company->id}\n";
echo "  Name: {$company->name}\n";
echo "  Email: {$company->email}\n";
echo "  Phone: {$company->phone}\n";
echo "  KYC Status: {$company->kyc_status}\n";
echo "  Is Active: " . ($company->is_active ? 'Yes' : 'No') . "\n\n";

echo "Company KYC Information:\n";
echo "  RC Number: " . ($company->cac_number ?? 'NOT SET') . "\n";
echo "  Director BVN: " . ($company->director_bvn ?? 'NOT SET') . "\n";
echo "  Business Type: " . ($company->business_type ?? 'NOT SET') . "\n";
echo "  Business Category: " . ($company->business_category ?? 'NOT SET') . "\n\n";

echo "========================================\n";
echo "PROBLEM IDENTIFIED:\n";
echo "========================================\n\n";

echo "PalmPay Error: LicenseNumber verification failed (Code: AC100007)\n\n";

echo "This means PalmPay cannot verify the RC number: {$company->cac_number}\n\n";

echo "Possible Reasons:\n";
echo "1. RC number format is incorrect\n";
echo "2. RC number doesn't exist in CAC database\n";
echo "3. RC number hasn't been verified with CAC yet\n";
echo "4. Company needs to use director BVN instead of RC for aggregator model\n\n";

echo "========================================\n";
echo "SOLUTION:\n";
echo "========================================\n\n";

echo "For AGGREGATOR MODEL, we should use DIRECTOR BVN, not RC number!\n\n";

echo "The company should:\n";
echo "1. Submit their director's BVN in KYC section\n";
echo "2. Admin approves the KYC\n";
echo "3. System will create master account using director BVN\n";
echo "4. All customer accounts will use the director's BVN\n\n";

if (!$company->director_bvn) {
    echo "❌ CRITICAL: Director BVN is NOT SET\n";
    echo "   Company must submit director BVN first!\n\n";
    echo "   Tell the company to:\n";
    echo "   1. Login to dashboard\n";
    echo "   2. Go to KYC section\n";
    echo "   3. Submit director BVN\n";
    echo "   4. Wait for admin approval\n";
} else {
    echo "✅ Director BVN exists: {$company->director_bvn}\n";
    echo "   But PalmPay is still using RC number for verification\n\n";
    echo "   The issue is that PalmPay requires VALID RC number\n";
    echo "   even when using director BVN for aggregator model\n\n";
    echo "   Options:\n";
    echo "   1. Get the correct/verified RC number from company\n";
    echo "   2. Use 'individual' identity type instead of 'company'\n";
    echo "   3. Contact PalmPay to verify why RC-9351002 is failing\n";
}

echo "\n========================================\n";
echo "TEMPORARY WORKAROUND:\n";
echo "========================================\n\n";

echo "Change identity type from 'company' to 'individual':\n";
echo "This will use director BVN only, without RC number\n\n";

echo "UPDATE companies SET business_type = 'individual' WHERE id = {$company->id};\n\n";

echo "Then the company can create customer accounts immediately!\n";
