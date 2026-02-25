<?php

/**
 * Test script that writes to file instead of stdout
 * Run: php test_to_file.php
 * Then: cat test_output.txt
 */

$output = "=== Test Output ===\n";
$output .= "Time: " . date('Y-m-d H:i:s') . "\n";
$output .= "PHP Version: " . phpversion() . "\n";
$output .= "Current directory: " . getcwd() . "\n\n";

$output .= "=== Testing Laravel Bootstrap ===\n";

try {
    require __DIR__.'/vendor/autoload.php';
    $output .= "✅ Autoload successful\n";
    
    $app = require_once __DIR__.'/bootstrap/app.php';
    $output .= "✅ App bootstrap successful\n";
    
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    $output .= "✅ Kernel bootstrap successful\n";
    
} catch (\Exception $e) {
    $output .= "❌ Bootstrap failed: " . $e->getMessage() . "\n";
    $output .= "Stack trace:\n" . $e->getTraceAsString() . "\n";
    file_put_contents(__DIR__ . '/test_output.txt', $output);
    exit(1);
}

$output .= "\n=== Testing Company Model ===\n";

try {
    use App\Models\Company;
    
    $company = Company::find(10);
    
    if ($company) {
        $output .= "✅ Found Amtpay company\n";
        $output .= "ID: {$company->id}\n";
        $output .= "Name: {$company->name}\n";
        $output .= "Webhook URL: {$company->webhook_url}\n\n";
        
        // Get webhook secret
        $webhookSecret = $company->webhook_secret;
        $output .= "=== Webhook Secret ===\n";
        $output .= "Type: " . gettype($webhookSecret) . "\n";
        $output .= "Length: " . strlen($webhookSecret) . "\n";
        $output .= "First 30 chars: " . substr($webhookSecret, 0, 30) . "...\n";
        
        // Check if serialized
        if (is_string($webhookSecret) && (strpos($webhookSecret, 's:') === 0 || strpos($webhookSecret, 'a:') === 0)) {
            $output .= "⚠️  SECRET IS SERIALIZED\n";
            $unserialized = unserialize($webhookSecret);
            $output .= "After unserialize length: " . strlen($unserialized) . "\n";
            $output .= "After unserialize (first 30): " . substr($unserialized, 0, 30) . "...\n";
            $webhookSecret = $unserialized;
        } else {
            $output .= "✅ SECRET IS PLAIN TEXT\n";
        }
        
        // Test signature
        $output .= "\n=== Test Signature ===\n";
        $testPayload = '{"test":"data"}';
        $signature = hash_hmac('sha256', $testPayload, $webhookSecret);
        $output .= "Test payload: {$testPayload}\n";
        $output .= "Signature: {$signature}\n";
        
        // Compare with expected
        $expectedSecret = 'whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24';
        $expectedSignature = hash_hmac('sha256', $testPayload, $expectedSecret);
        $output .= "\nExpected signature: {$expectedSignature}\n";
        
        if ($signature === $expectedSignature) {
            $output .= "✅ SIGNATURES MATCH!\n";
        } else {
            $output .= "❌ SIGNATURES DON'T MATCH\n";
        }
        
    } else {
        $output .= "❌ Amtpay company not found\n";
    }
    
} catch (\Exception $e) {
    $output .= "❌ Company query failed: " . $e->getMessage() . "\n";
    $output .= "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

$output .= "\n=== Done ===\n";

// Write to file
file_put_contents(__DIR__ . '/test_output.txt', $output);

echo "Output written to test_output.txt\n";
echo "Run: cat test_output.txt\n";
