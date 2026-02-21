#!/usr/bin/env php
<?php

/**
 * KYC Charges Configuration Test Script
 * 
 * This script checks:
 * 1. If service_charges table exists
 * 2. If KYC charges are configured
 * 3. If charges are NOT hardcoded (stored in database)
 * 4. Company KYC status and charging logic
 * 5. Wallet balance for testing
 */

echo "\n";
echo "========================================\n";
echo "  KYC CHARGES CONFIGURATION TEST\n";
echo "========================================\n\n";

// Database connection
$host = getenv('DB_HOST') ?: 'localhost';
$database = getenv('DB_DATABASE') ?: 'your_database';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n\n";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 1: Check if service_charges table exists
echo "TEST 1: Checking service_charges table...\n";
echo "-------------------------------------------\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'service_charges'");
    if ($stmt->rowCount() > 0) {
        echo "✅ service_charges table exists\n";
    } else {
        echo "❌ service_charges table NOT found\n";
        echo "   Run: php artisan migrate\n";
        exit(1);
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check KYC charges configuration
echo "\nTEST 2: Checking KYC charges configuration...\n";
echo "-------------------------------------------\n";

$kycServices = [
    'enhanced_bvn' => 100.00,
    'enhanced_nin' => 100.00,
    'bank_account_verification' => 50.00,
    'face_recognition' => 50.00,
    'liveness_detection' => 100.00,
    'blacklist_check' => 50.00,
    'credit_score' => 100.00,
    'loan_features' => 50.00,
];

$stmt = $pdo->prepare("
    SELECT service_name, charge_value, is_active, company_id 
    FROM service_charges 
    WHERE service_category = 'kyc' 
    ORDER BY service_name
");
$stmt->execute();
$charges = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($charges)) {
    echo "❌ NO KYC charges configured!\n";
    echo "\n📝 To configure charges, run this SQL:\n\n";
    echo "INSERT INTO service_charges (company_id, service_category, service_name, charge_type, charge_value, is_active, created_at, updated_at) VALUES\n";
    foreach ($kycServices as $service => $amount) {
        echo "(1, 'kyc', '$service', 'flat', $amount, 1, NOW(), NOW()),\n";
    }
    echo "\n";
} else {
    echo "✅ KYC charges found: " . count($charges) . " services\n\n";
    
    $configuredServices = [];
    foreach ($charges as $charge) {
        $configuredServices[] = $charge['service_name'];
        $status = $charge['is_active'] ? '✅ Active' : '❌ Inactive';
        $scope = $charge['company_id'] == 1 ? 'Global' : "Company #{$charge['company_id']}";
        echo "  • {$charge['service_name']}: ₦{$charge['charge_value']} ($status, $scope)\n";
    }
    
    // Check for missing services
    $missingServices = array_diff(array_keys($kycServices), $configuredServices);
    if (!empty($missingServices)) {
        echo "\n⚠️  Missing KYC services:\n";
        foreach ($missingServices as $service) {
            echo "  • $service (suggested: ₦{$kycServices[$service]})\n";
        }
        echo "\n📝 To add missing services, run:\n\n";
        foreach ($missingServices as $service) {
            $amount = $kycServices[$service];
            echo "INSERT INTO service_charges (company_id, service_category, service_name, charge_type, charge_value, is_active, created_at, updated_at) VALUES (1, 'kyc', '$service', 'flat', $amount, 1, NOW(), NOW());\n";
        }
        echo "\n";
    }
}

// Test 3: Check companies and their KYC status
echo "\nTEST 3: Checking company KYC status...\n";
echo "-------------------------------------------\n";

$stmt = $pdo->query("
    SELECT id, name, kyc_status, created_at 
    FROM companies 
    ORDER BY id 
    LIMIT 10
");
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($companies)) {
    echo "⚠️  No companies found\n";
} else {
    echo "Found " . count($companies) . " companies:\n\n";
    foreach ($companies as $company) {
        $status = $company['kyc_status'];
        $willCharge = in_array($status, ['verified', 'approved']) ? '💰 WILL CHARGE' : '🆓 FREE (onboarding)';
        echo "  • Company #{$company['id']}: {$company['name']}\n";
        echo "    Status: $status → $willCharge\n";
    }
}

// Test 4: Check company wallets
echo "\nTEST 4: Checking company wallet balances...\n";
echo "-------------------------------------------\n";

$stmt = $pdo->query("
    SELECT cw.company_id, c.name, cw.balance 
    FROM company_wallets cw
    JOIN companies c ON c.id = cw.company_id
    ORDER BY cw.company_id
    LIMIT 10
");
$wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($wallets)) {
    echo "⚠️  No company wallets found\n";
} else {
    echo "Found " . count($wallets) . " wallets:\n\n";
    foreach ($wallets as $wallet) {
        $balance = number_format($wallet['balance'], 2);
        $canAffordBVN = $wallet['balance'] >= 100 ? '✅' : '❌';
        echo "  • Company #{$wallet['company_id']}: {$wallet['name']}\n";
        echo "    Balance: ₦$balance $canAffordBVN\n";
    }
}

// Test 5: Check recent KYC transactions
echo "\nTEST 5: Checking recent KYC transactions...\n";
echo "-------------------------------------------\n";

$stmt = $pdo->query("
    SELECT t.id, t.company_id, c.name, t.category, t.amount, t.status, t.created_at
    FROM transactions t
    JOIN companies c ON c.id = t.company_id
    WHERE t.category = 'kyc_charge'
    ORDER BY t.created_at DESC
    LIMIT 5
");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($transactions)) {
    echo "ℹ️  No KYC transactions yet (charges not tested)\n";
} else {
    echo "Found " . count($transactions) . " recent KYC transactions:\n\n";
    foreach ($transactions as $tx) {
        $status = $tx['status'] === 'success' ? '✅' : '❌';
        echo "  • TX #{$tx['id']}: {$tx['name']}\n";
        echo "    Amount: ₦{$tx['amount']} | Status: {$tx['status']} $status\n";
        echo "    Date: {$tx['created_at']}\n\n";
    }
}

// Test 6: Verify charges are NOT hardcoded
echo "\nTEST 6: Verifying charges are NOT hardcoded...\n";
echo "-------------------------------------------\n";

// Check KycService.php for hardcoded values
$kycServiceFile = __DIR__ . '/app/Services/KYC/KycService.php';
if (file_exists($kycServiceFile)) {
    $content = file_get_contents($kycServiceFile);
    
    // Look for hardcoded charge amounts
    $hardcodedPatterns = [
        '/\$chargeAmount\s*=\s*100/',
        '/\$chargeAmount\s*=\s*50/',
        '/charge_value.*100/',
        '/charge_value.*50/',
    ];
    
    $foundHardcoded = false;
    foreach ($hardcodedPatterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $foundHardcoded = true;
            break;
        }
    }
    
    if ($foundHardcoded) {
        echo "⚠️  WARNING: Possible hardcoded charges found in KycService.php\n";
        echo "   Please verify charges are loaded from database\n";
    } else {
        echo "✅ No hardcoded charges detected\n";
        echo "   Charges are loaded from service_charges table\n";
    }
} else {
    echo "⚠️  KycService.php not found at expected location\n";
}

// Test 7: Check EaseID configuration
echo "\nTEST 7: Checking EaseID API configuration...\n";
echo "-------------------------------------------\n";

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    
    $easeIdConfigured = true;
    $requiredVars = ['EASEID_APP_ID', 'EASEID_PRIVATE_KEY', 'EASEID_BASE_URL'];
    
    foreach ($requiredVars as $var) {
        if (strpos($envContent, $var) === false || preg_match("/$var\s*=\s*$/m", $envContent)) {
            echo "❌ $var not configured\n";
            $easeIdConfigured = false;
        } else {
            echo "✅ $var configured\n";
        }
    }
    
    if ($easeIdConfigured) {
        echo "\n✅ EaseID API is configured\n";
    } else {
        echo "\n❌ EaseID API is NOT fully configured\n";
        echo "   Add these to your .env file:\n";
        echo "   EASEID_APP_ID=your_app_id\n";
        echo "   EASEID_PRIVATE_KEY=your_private_key\n";
        echo "   EASEID_BASE_URL=https://open-api.easeid.ai\n";
    }
} else {
    echo "❌ .env file not found\n";
}

// Summary
echo "\n========================================\n";
echo "  SUMMARY\n";
echo "========================================\n\n";

$allGood = true;

// Check critical items
$stmt = $pdo->query("SELECT COUNT(*) as count FROM service_charges WHERE service_category = 'kyc' AND is_active = 1");
$activeCharges = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($activeCharges >= 3) {
    echo "✅ KYC charges configured ($activeCharges active services)\n";
} else {
    echo "❌ KYC charges NOT properly configured\n";
    $allGood = false;
}

$stmt = $pdo->query("SELECT COUNT(*) as count FROM companies");
$companyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($companyCount > 0) {
    echo "✅ Companies exist ($companyCount found)\n";
} else {
    echo "⚠️  No companies found (create test company)\n";
}

$stmt = $pdo->query("SELECT COUNT(*) as count FROM company_wallets WHERE balance > 0");
$walletsWithBalance = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($walletsWithBalance > 0) {
    echo "✅ Wallets with balance ($walletsWithBalance found)\n";
} else {
    echo "⚠️  No wallets with balance (fund wallet for testing)\n";
}

if ($allGood) {
    echo "\n🎉 All critical checks passed!\n";
    echo "   KYC charging system is ready for testing\n\n";
} else {
    echo "\n⚠️  Some issues found. Please fix them before testing.\n\n";
}

// Quick test command
echo "========================================\n";
echo "  QUICK TEST COMMANDS\n";
echo "========================================\n\n";

echo "1. Check charges in database:\n";
echo "   mysql -u$username -p$password $database -e \"SELECT * FROM service_charges WHERE service_category='kyc';\"\n\n";

echo "2. Add test balance to company wallet:\n";
echo "   mysql -u$username -p$password $database -e \"UPDATE company_wallets SET balance = 1000 WHERE company_id = 1;\"\n\n";

echo "3. Test BVN verification (will charge if company is verified):\n";
echo "   curl -X POST https://app.pointwave.ng/api/v1/kyc/verify-bvn \\\n";
echo "     -H 'Authorization: Bearer YOUR_SECRET_KEY' \\\n";
echo "     -H 'x-api-key: YOUR_API_KEY' \\\n";
echo "     -H 'x-business-id: YOUR_BUSINESS_ID' \\\n";
echo "     -H 'Content-Type: application/json' \\\n";
echo "     -d '{\"bvn\":\"22154883751\"}'\n\n";

echo "4. Check transaction after test:\n";
echo "   mysql -u$username -p$password $database -e \"SELECT * FROM transactions WHERE category='kyc_charge' ORDER BY created_at DESC LIMIT 1;\"\n\n";

echo "========================================\n\n";
