<?php
/**
 * Debug Kobopoint Webhook Signature Issue
 * Run this to see what's actually being signed and sent
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Transaction;

// Get Kobopoint company
$kobopoint = Company::where('name', 'LIKE', '%kobopoint%')->first();

if (!$kobopoint) {
    echo "❌ Kobopoint company not found\n";
    exit(1);
}

echo "=== KOBOPOINT COMPANY INFO ===\n";
echo "Company ID: {$kobopoint->id}\n";
echo "Company Name: {$kobopoint->name}\n";
echo "Webhook URL: {$kobopoint->webhook_url}\n\n";

echo "=== SECRET KEY INFO ===\n";
echo "secret_key (from DB): " . substr($kobopoint->secret_key, 0, 20) . "...\n";
echo "secret_key length: " . strlen($kobopoint->secret_key) . " chars\n";
echo "api_secret_key (from DB): " . substr($kobopoint->api_secret_key, 0, 20) . "...\n";
echo "api_secret_key length: " . strlen($kobopoint->api_secret_key) . " chars\n\n";

// Check if they're the same
if ($kobopoint->secret_key === $kobopoint->api_secret_key) {
    echo "✅ secret_key and api_secret_key are THE SAME\n\n";
} else {
    echo "⚠️  secret_key and api_secret_key are DIFFERENT!\n\n";
}

// Get a recent transaction
$transaction = Transaction::where('company_id', $kobopoint->id)
    ->orderBy('created_at', 'desc')
    ->first();

if (!$transaction) {
    echo "No transactions found for Kobopoint\n";
    exit(0);
}

echo "=== SAMPLE WEBHOOK PAYLOAD ===\n";
$payload = [
    'event' => 'payment.received',
    'event_id' => 'test_' . uniqid(),
    'timestamp' => now()->toISOString(),
    'data' => [
        'transaction_id' => $transaction->transaction_ref,
        'reference' => $transaction->provider_reference ?? $transaction->transaction_ref,
        'session_id' => $transaction->session_id,
        'type' => $transaction->transaction_type,
        'amount' => $transaction->amount,
        'fee' => $transaction->fee,
        'net_amount' => $transaction->net_amount,
        'currency' => $transaction->currency ?? 'NGN',
        'status' => $transaction->status,
        'settlement_status' => $transaction->settlement_status,
        'customer' => [
            'name' => $transaction->payer_name ?? null,
            'account_number' => $transaction->payer_account_number ?? null,
            'bank_name' => $transaction->payer_bank_name ?? null,
            'email' => null,
        ],
        'virtual_account' => [
            'account_number' => $transaction->virtualAccount->account_number ?? null,
            'account_name' => $transaction->virtualAccount->account_name ?? null,
        ],
        'created_at' => $transaction->created_at->toISOString(),
        'updated_at' => $transaction->updated_at->toISOString(),
    ]
];

$jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
echo $jsonPayload . "\n\n";

echo "=== SIGNATURE CALCULATION ===\n";
$signature = hash_hmac('sha256', $jsonPayload, $kobopoint->secret_key);
echo "Signature (using secret_key): " . $signature . "\n\n";

echo "=== WHAT KOBOPOINT SHOULD DO ===\n";
echo "1. Get raw body: \$rawBody = file_get_contents('php://input');\n";
echo "2. Get signature: \$receivedSig = \$_SERVER['HTTP_X_POINTWAVE_SIGNATURE'];\n";
echo "3. Use Secret Key: \$secretKey = '{$kobopoint->secret_key}';\n";
echo "4. Calculate: \$expectedSig = hash_hmac('sha256', \$rawBody, \$secretKey);\n";
echo "5. Verify: hash_equals(\$expectedSig, \$receivedSig)\n\n";

echo "=== KOBOPOINT'S SECRET KEY (FULL) ===\n";
echo $kobopoint->secret_key . "\n\n";

echo "Done!\n";
