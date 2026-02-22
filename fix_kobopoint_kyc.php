<?php
/**
 * Fix KoboPoint KYC Data
 * Updates the director_bvn field for KoboPoint company
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Fix KoboPoint KYC Data\n";
echo "========================================\n\n";

// Get KoboPoint company
$company = DB::table('companies')->where('id', 4)->first();

if (!$company) {
    echo "❌ Company ID 4 (KoboPoint) not found!\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "Email: {$company->email}\n";
echo "Current KYC Data:\n";
echo "  director_bvn: " . ($company->director_bvn ?? 'NULL') . "\n";
echo "  director_nin: " . ($company->director_nin ?? 'NULL') . "\n";
echo "  business_registration_number: " . ($company->business_registration_number ?? 'NULL') . "\n";
echo "\n";

// The BVN from the screenshot is 22490148602
$correctBvn = '22490148602';

echo "Updating director_bvn to: {$correctBvn}\n";

DB::table('companies')->where('id', 4)->update([
    'director_bvn' => $correctBvn,
    'updated_at' => now()
]);

echo "✅ Updated successfully!\n\n";

// Verify
$company = DB::table('companies')->where('id', 4)->first();
echo "Verified KYC Data:\n";
echo "  director_bvn: " . ($company->director_bvn ?? 'NULL') . "\n";
echo "  director_nin: " . ($company->director_nin ?? 'NULL') . "\n";
echo "  business_registration_number: " . ($company->business_registration_number ?? 'NULL') . "\n";
echo "\n";

echo "========================================\n";
echo "Next Steps:\n";
echo "========================================\n";
echo "1. Run: php create_missing_master_wallets.php\n";
echo "2. Or activate the company via admin panel\n";
echo "3. Or have the company login to auto-create wallet\n";
echo "\n";
