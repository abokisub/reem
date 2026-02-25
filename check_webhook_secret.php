<?php

/**
 * Check Webhook Secret for a Company
 * 
 * This script checks the actual webhook secret stored in the database
 * 
 * Usage: php check_webhook_secret.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use Illuminate\Support\Facades\DB;

echo "=== Webhook Secret Checker ===\n\n";

// Get company name from user
echo "Enter company name (e.g., Amtpay): ";
$companyName = trim(fgets(STDIN));

if (empty($companyName)) {
    echo "Error: Company name is required\n";
    exit(1);
}

// Find company
$company = Company::where('name', 'LIKE', "%{$companyName}%")->first();

if (!$company) {
    echo "Error: Company not found\n";
    exit(1);
}

echo "\n=== Company Details ===\n";
echo "ID: {$company->id}\n";
echo "Name: {$company->name}\n";
echo "Webhook URL: {$company->webhook_url}\n\n";

echo "=== Webhook Secrets ===\n";

// Try to get the decrypted webhook secret
try {
    $encryptedWebhookSecret = DB::table('companies')
        ->where('id', $company->id)
        ->value('webhook_secret');
    
    if ($encryptedWebhookSecret) {
        try {
            $webhookSecret = decrypt($encryptedWebhookSecret);
            
            // Laravel's encrypted cast serializes values, so we need to unserialize
            if (is_string($webhookSecret) && (strpos($webhookSecret, 's:') === 0 || strpos($webhookSecret, 'a:') === 0)) {
                $webhookSecret = unserialize($webhookSecret);
            }
            
            echo "Live Webhook Secret: {$webhookSecret}\n";
        } catch (\Exception $e) {
            echo "Live Webhook Secret: [DECRYPTION FAILED] - {$e->getMessage()}\n";
            echo "Raw encrypted value length: " . strlen($encryptedWebhookSecret) . " bytes\n";
        }
    } else {
        echo "Live Webhook Secret: [NOT SET]\n";
    }
} catch (\Exception $e) {
    echo "Error reading webhook secret: {$e->getMessage()}\n";
}

// Try to get test webhook secret
try {
    $encryptedTestWebhookSecret = DB::table('companies')
        ->where('id', $company->id)
        ->value('test_webhook_secret');
    
    if ($encryptedTestWebhookSecret) {
        try {
            $testWebhookSecret = decrypt($encryptedTestWebhookSecret);
            
            if (is_string($testWebhookSecret) && (strpos($testWebhookSecret, 's:') === 0 || strpos($testWebhookSecret, 'a:') === 0)) {
                $testWebhookSecret = unserialize($testWebhookSecret);
            }
            
            echo "Test Webhook Secret: {$testWebhookSecret}\n";
        } catch (\Exception $e) {
            echo "Test Webhook Secret: [DECRYPTION FAILED] - {$e->getMessage()}\n";
        }
    } else {
        echo "Test Webhook Secret: [NOT SET]\n";
    }
} catch (\Exception $e) {
    echo "Error reading test webhook secret: {$e->getMessage()}\n";
}

echo "\n=== What Amtpay Should Use ===\n";
echo "Copy the 'Live Webhook Secret' value above and use it in their .env file:\n";
echo "POINTWAVE_WEBHOOK_SECRET=[the value shown above]\n\n";

echo "=== Recent Webhook Logs ===\n";
$webhookLogs = DB::table('company_webhook_logs')
    ->where('company_id', $company->id)
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get(['id', 'url', 'status_code', 'response_body', 'created_at']);

if ($webhookLogs->count() > 0) {
    foreach ($webhookLogs as $log) {
        echo "  - ID: {$log->id} | Status: {$log->status_code} | Time: {$log->created_at}\n";
        if ($log->response_body) {
            $response = json_decode($log->response_body, true);
            if (isset($response['error'])) {
                echo "    Error: {$response['error']}\n";
            }
        }
    }
} else {
    echo "  No webhook logs found\n";
}

echo "\n";
