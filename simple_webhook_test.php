<?php

/**
 * Simple Webhook Test - No Laravel Required
 * Just tests signature calculation
 */

echo "=== Simple Webhook Signature Test ===\n\n";

// Amtpay's webhook secret (from their .env)
$amtpaySecret = 'whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24';

// Test payload
$payload = '{"event":"payment.success","event_id":"test-123","timestamp":"2026-02-25T15:00:00+01:00","data":{"transaction_id":"txn_test","amount":"100.00","fee":"0.60","net_amount":"99.40","reference":"REF_TEST","status":"success","customer":{"account_number":"6604877046","sender_name":"TEST","sender_account":"1234567890","sender_bank":"TEST"},"narration":"Test","created_at":"2026-02-25T15:00:00+01:00"}}';

echo "Payload length: " . strlen($payload) . "\n";
echo "Secret: {$amtpaySecret}\n";
echo "Secret length: " . strlen($amtpaySecret) . "\n\n";

// Calculate signature
$signature = hash_hmac('sha256', $payload, $amtpaySecret);

echo "Generated signature: {$signature}\n\n";

echo "This is what PointWave SHOULD be sending to Amtpay.\n";
echo "Amtpay should calculate the same signature using:\n";
echo "  \$payload = \$request->getContent();\n";
echo "  hash_hmac('sha256', \$payload, '{$amtpaySecret}');\n";
