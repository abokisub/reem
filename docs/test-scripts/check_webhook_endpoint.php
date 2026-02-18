<?php

/**
 * Test if webhook endpoint is accessible on production
 */

$webhookUrl = 'https://app.pointwave.ng/api/webhooks/palmpay';

echo "=== Testing Webhook Endpoint ===\n\n";
echo "URL: $webhookUrl\n\n";

// Test 1: Check if endpoint is accessible
echo "Test 1: Checking if endpoint is accessible...\n";
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'event' => 'test',
    'data' => ['test' => 'data']
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($error) {
    echo "❌ Error: $error\n";
} else {
    echo "✅ Endpoint is accessible\n";
    echo "Response: $response\n";
}

echo "\n";

// Test 2: Check alternative endpoint
$altUrl = 'https://app.pointwave.ng/api/v1/webhook/palmpay';
echo "Test 2: Checking alternative endpoint...\n";
echo "URL: $altUrl\n";

$ch = curl_init($altUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'event' => 'test',
    'data' => ['test' => 'data']
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n";

echo "\n=== Summary ===\n";
echo "Primary webhook: https://app.pointwave.ng/api/webhooks/palmpay\n";
echo "Alternative webhook: https://app.pointwave.ng/api/v1/webhook/palmpay\n";
echo "\nIf both return 404, you need to deploy your backend code to production.\n";
