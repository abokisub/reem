<?php

/**
 * Verify if the webhook fix code is deployed
 * Run this on PointWave LIVE server
 */

echo "=== Verifying Deployed Code ===\n\n";

// Check OutgoingWebhookService
$file1 = __DIR__ . '/app/Services/Webhook/OutgoingWebhookService.php';
echo "Checking: {$file1}\n";

if (file_exists($file1)) {
    $content = file_get_contents($file1);
    
    // Check if our fix is present
    if (strpos($content, 'unserialize($webhookSecret)') !== false) {
        echo "✅ OutgoingWebhookService HAS the fix\n";
    } else {
        echo "❌ OutgoingWebhookService DOES NOT have the fix\n";
    }
    
    // Show the relevant code section
    if (preg_match('/\/\/ Get company webhook secret.*?hash_hmac/s', $content, $matches)) {
        echo "\nCurrent code:\n";
        echo "---\n";
        echo trim($matches[0]) . "\n";
        echo "---\n\n";
    }
} else {
    echo "❌ File not found!\n\n";
}

// Check TransactionController
$file2 = __DIR__ . '/app/Http/Controllers/API/TransactionController.php';
echo "Checking: {$file2}\n";

if (file_exists($file2)) {
    $content = file_get_contents($file2);
    
    // Check if our fix is present
    if (strpos($content, 'unserialize($webhookSecret)') !== false) {
        echo "✅ TransactionController HAS the fix\n";
    } else {
        echo "❌ TransactionController DOES NOT have the fix\n";
    }
} else {
    echo "❌ File not found!\n\n";
}

echo "\n=== Git Status ===\n";
echo "Current commit: ";
system('git log -1 --oneline');

echo "\n=== Conclusion ===\n";
echo "If both files show ✅, the code is deployed.\n";
echo "If they show ❌, the git pull didn't work or files are in a different location.\n";
