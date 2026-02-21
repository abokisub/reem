<?php

/**
 * EaseID API Debug Script
 * Tests EaseID API authentication and signature generation
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Log;

echo "========================================\n";
echo "EASEID API DEBUG TEST\n";
echo "========================================\n\n";

// 1. Check Configuration
echo "TEST 1: Configuration Check\n";
echo "-------------------------------------------\n";
$appId = config('services.easeid.app_id');
$merchantId = config('services.easeid.merchant_id');
$privateKey = config('services.easeid.private_key');
$baseUrl = config('services.easeid.base_url');

echo "App ID: " . ($appId ? $appId : "❌ NOT SET") . "\n";
echo "Merchant ID: " . ($merchantId ? $merchantId : "❌ NOT SET") . "\n";
echo "Private Key: " . ($privateKey ? substr($privateKey, 0, 50) . "..." : "❌ NOT SET") . "\n";
echo "Base URL: " . ($baseUrl ? $baseUrl : "❌ NOT SET") . "\n\n";

if (!$appId || !$privateKey || !$baseUrl) {
    echo "❌ EaseID credentials not configured!\n";
    exit(1);
}

// 2. Test Private Key Format
echo "TEST 2: Private Key Format\n";
echo "-------------------------------------------\n";

function formatPrivateKey(string $key): string
{
    if (strpos($key, '-----BEGIN') !== false) {
        return $key;
    }

    return "-----BEGIN PRIVATE KEY-----\n" .
        chunk_split($key, 64, "\n") .
        "-----END PRIVATE KEY-----";
}

$formattedKey = formatPrivateKey($privateKey);
echo "Formatted Key (first 100 chars):\n" . substr($formattedKey, 0, 100) . "...\n\n";

$privateKeyResource = openssl_pkey_get_private($formattedKey);
if (!$privateKeyResource) {
    echo "❌ Invalid RSA private key format!\n";
    echo "OpenSSL Error: " . openssl_error_string() . "\n";
    exit(1);
} else {
    echo "✅ Private key is valid RSA format\n\n";
}

// 3. Test Signature Generation
echo "TEST 3: Signature Generation\n";
echo "-------------------------------------------\n";

function generateSignature(array $params, string $privateKey): string
{
    // Remove null/empty values
    $params = array_filter($params, function ($value) {
        return $value !== null && $value !== '';
    });

    // Sort by key (ASCII dictionary order)
    ksort($params);

    // Concatenate key=value pairs with & delimiter
    $pairs = [];
    foreach ($params as $key => $value) {
        $pairs[] = $key . '=' . $value;
    }
    $signString = implode('&', $pairs);

    echo "Sign String: " . $signString . "\n";

    // RSA sign the string directly (not MD5 hash)
    $privateKeyResource = openssl_pkey_get_private(formatPrivateKey($privateKey));

    if (!$privateKeyResource) {
        throw new Exception('Invalid RSA private key');
    }

    openssl_sign($signString, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);

    // Base64 encode the signature
    $base64Signature = base64_encode($signature);
    echo "Signature (first 50 chars): " . substr($base64Signature, 0, 50) . "...\n\n";
    
    return $base64Signature;
}

$testNin = '35257106066';
$requestTime = (int) (microtime(true) * 1000);
$nonceStr = bin2hex(random_bytes(16)); // 32 character random string

$requestData = [
    'appId' => $appId,
    'nin' => $testNin,
    'requestTime' => $requestTime,
    'version' => 'V1.1',
    'nonceStr' => $nonceStr,
];

echo "Request Data:\n";
print_r($requestData);
echo "\n";

$signature = generateSignature($requestData, $privateKey);

// 4. Make Real API Call
echo "TEST 4: Real API Call to EaseID\n";
echo "-------------------------------------------\n";

$endpoint = '/api/validator-service/open/nin/inquire';
$url = $baseUrl . $endpoint;

echo "URL: " . $url . "\n";
echo "Headers:\n";
echo "  Authorization: Bearer " . $appId . "\n";
echo "  Signature: " . substr($signature, 0, 50) . "...\n";
echo "  CountryCode: NG\n";
echo "  Content-Type: application/json\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $appId,
    'Signature: ' . $signature,
    'CountryCode: NG',
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: " . $httpCode . "\n";

if ($curlError) {
    echo "❌ CURL Error: " . $curlError . "\n";
    exit(1);
}

echo "Response:\n";
$responseData = json_decode($response, true);
print_r($responseData);
echo "\n";

// 5. Analyze Response
echo "TEST 5: Response Analysis\n";
echo "-------------------------------------------\n";

if ($httpCode === 200) {
    echo "✅ HTTP 200 OK\n";
    
    if (isset($responseData['success'])) {
        if ($responseData['success'] === true) {
            echo "✅ API Success: true\n";
            echo "✅ NIN Verification Working!\n";
        } else {
            echo "❌ API Success: false\n";
            echo "Message: " . ($responseData['message'] ?? 'No message') . "\n";
            echo "Code: " . ($responseData['code'] ?? 'No code') . "\n";
            
            // Common error codes
            if (isset($responseData['code'])) {
                switch ($responseData['code']) {
                    case '10001':
                        echo "\n⚠️  DIAGNOSIS: Invalid signature or authentication\n";
                        echo "   - Check if private key matches the one registered with EaseID\n";
                        echo "   - Verify App ID is correct\n";
                        echo "   - Ensure signature algorithm matches EaseID requirements\n";
                        break;
                    case '10002':
                        echo "\n⚠️  DIAGNOSIS: Invalid parameters\n";
                        echo "   - Check request data format\n";
                        break;
                    case '10003':
                        echo "\n⚠️  DIAGNOSIS: Insufficient balance\n";
                        echo "   - Top up EaseID account balance\n";
                        break;
                    default:
                        echo "\n⚠️  DIAGNOSIS: Unknown error code\n";
                }
            }
        }
    } else {
        echo "❌ Response missing 'success' field\n";
    }
} else {
    echo "❌ HTTP Error: " . $httpCode . "\n";
}

echo "\n========================================\n";
echo "DEBUG TEST COMPLETE\n";
echo "========================================\n";
