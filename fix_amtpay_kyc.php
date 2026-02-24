<?php

/**
 * Fix Amtpay Company - Add Director BVN
 * 
 * Issue: Amtpay only has RC number, but PalmPay requires BVN/NIN for virtual accounts
 * Solution: Admin needs to add director BVN or NIN to the company
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Fix Amtpay Company KYC\n";
echo "========================================\n\n";

// Get Amtpay company
$company = DB::table('companies')->where('id', 8)->first();

if (!$company) {
    echo "❌ Amtpay company not found\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "Email: {$company->email}\n";
echo "Current KYC:\n";
echo "  - Director BVN: " . ($company->director_bvn ?: 'NOT SET') . "\n";
echo "  - Director NIN: " . ($company->director_nin ?: 'NOT SET') . "\n";
echo "  - RC Number: " . ($company->business_registration_number ?: 'NOT SET') . "\n";
echo "\n";

echo "========================================\n";
echo "SOLUTION\n";
echo "========================================\n\n";

echo "To create a master virtual account for Amtpay, you need to:\n\n";

echo "1. Get the director's BVN or NIN from Amtpay\n";
echo "2. Update the company record:\n\n";

echo "   Option A - Using MySQL:\n";
echo "   ```sql\n";
echo "   UPDATE companies \n";
echo "   SET director_bvn = '12345678901' \n";
echo "   WHERE id = 8;\n";
echo "   ```\n\n";

echo "   Option B - Using Admin Panel:\n";
echo "   - Go to: https://app.pointwave.ng/secure/companies\n";
echo "   - Click on Amtpay\n";
echo "   - Click 'Edit' button\n";
echo "   - Add Director BVN or NIN\n";
echo "   - Save\n\n";

echo "3. Then run this script to create master wallet:\n";
echo "   ```bash\n";
echo "   php fix_all_activated_companies_master_wallets.php\n";
echo "   ```\n\n";

echo "========================================\n";
echo "WHY THIS IS NEEDED\n";
echo "========================================\n\n";

echo "PalmPay requires BVN or NIN for virtual account creation.\n";
echo "RC Number alone is not sufficient.\n\n";

echo "This is a PalmPay requirement, not a PointWave limitation.\n\n";

echo "========================================\n";
