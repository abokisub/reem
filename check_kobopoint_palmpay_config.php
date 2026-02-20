<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    KOBOPOINT PALMPAY CONFIGURATION DIAGNOSTIC              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$businessId = '3450968aa027e86e3ff5b0169dc17edd7694a846';

echo "🔍 Checking configuration for Business ID: {$businessId}\n\n";

try {
    // Find company by business_id
    $company = DB::table('companies')->where('business_id', $businessId)->first();
    
    if (!$company) {
        echo "❌ ERROR: Company not found with Business ID: {$businessId}\n";
        echo "\nPlease verify:\n";
        echo "  1. Business ID is correct\n";
        echo "  2. Account has been created in PointWave\n";
        exit(1);
    }
    
    echo "✅ Company Found\n";
    echo str_repeat("-", 60) . "\n";
    echo "  Company ID: {$company->id}\n";
    echo "  Company Name: {$company->name}\n";
    echo "  Email: {$company->email}\n";
    echo "  Status: {$company->status}\n\n";
    
    // Check PalmPay credentials
    echo "🔐 PalmPay Credentials Check\n";
    echo str_repeat("-", 60) . "\n";
    
    $palmpayFields = [
        'palmpay_app_id' => 'PalmPay App ID',
        'palmpay_secret_key' => 'PalmPay Secret Key',
        'palmpay_public_key' => 'PalmPay Public Key',
        'palmpay_merchant_id' => 'PalmPay Merchant ID'
    ];
    
    $missingFields = [];
    $hasCredentials = false;
    
    foreach ($palmpayFields as $field => $label) {
        if (property_exists($company, $field)) {
            $value = $company->$field;
            if (!empty($value)) {
                $masked = substr($value, 0, 10) . '...' . substr($value, -4);
                echo "  ✅ {$label}: {$masked}\n";
                $hasCredentials = true;
            } else {
                echo "  ❌ {$label}: NOT SET\n";
                $missingFields[] = $label;
            }
        } else {
            echo "  ⚠️  {$label}: Column doesn't exist (needs migration)\n";
            $missingFields[] = $label;
        }
    }
    
    echo "\n";
    
    // Check API keys
    echo "🔑 API Keys Check\n";
    echo str_repeat("-", 60) . "\n";
    
    if (!empty($company->live_public_key)) {
        $masked = substr($company->live_public_key, 0, 10) . '...' . substr($company->live_public_key, -4);
        echo "  ✅ Live Public Key: {$masked}\n";
    } else {
        echo "  ❌ Live Public Key: NOT SET\n";
    }
    
    if (!empty($company->live_secret_key)) {
        $masked = substr($company->live_secret_key, 0, 10) . '...' . substr($company->live_secret_key, -4);
        echo "  ✅ Live Secret Key: {$masked}\n";
    } else {
        echo "  ❌ Live Secret Key: NOT SET\n";
    }
    
    echo "\n";
    
    // Diagnosis
    echo "📊 DIAGNOSIS\n";
    echo str_repeat("-", 60) . "\n";
    
    if (empty($missingFields) && $hasCredentials) {
        echo "✅ All PalmPay credentials are configured\n";
        echo "\nThe signature error might be due to:\n";
        echo "  1. Incorrect credentials (need to verify with PalmPay)\n";
        echo "  2. PalmPay account not activated\n";
        echo "  3. IP whitelist issue\n";
        echo "\n📧 Contact PointWave support to verify credentials\n";
    } else {
        echo "❌ MISSING PALMPAY CREDENTIALS\n\n";
        echo "Missing fields:\n";
        foreach ($missingFields as $field) {
            echo "  • {$field}\n";
        }
        echo "\n";
        echo "🔧 SOLUTION:\n";
        echo "  1. Contact PointWave support\n";
        echo "  2. Request PalmPay credentials for your account\n";
        echo "  3. Admin will configure them in your account\n";
        echo "\n";
        echo "📧 Email: support@pointwave.ng\n";
        echo "📱 Include your Business ID: {$businessId}\n";
    }
    
    echo "\n";
    echo "💡 NEXT STEPS:\n";
    echo str_repeat("-", 60) . "\n";
    echo "1. Send this diagnostic output to PointWave support\n";
    echo "2. Include your Business ID: {$businessId}\n";
    echo "3. Request PalmPay credential configuration\n";
    echo "4. Wait for confirmation before retrying VA creation\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nPlease contact PointWave support with this error message.\n";
}
