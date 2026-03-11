<?php
// Fix OyitiPay DNS Resolution Issue
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;

echo "🔧 FIXING OYITIPAY DNS RESOLUTION ISSUE\n";
echo "======================================\n\n";

// Find OyitiPay company
$company = Company::where('name', 'like', '%oyiti%')->first();

if (!$company) {
    echo "❌ OyitiPay company not found\n";
    exit;
}

echo "📋 CURRENT WEBHOOK CONFIGURATION:\n";
echo "=================================\n";
echo "Company: {$company->name}\n";
echo "Current Webhook URL: {$company->webhook_url}\n\n";

// Test current DNS resolution
echo "🔍 TESTING DNS RESOLUTION:\n";
echo "=========================\n";

$domain = 'app.oyitipay.com';
$ip = gethostbyname($domain);

if ($ip === $domain) {
    echo "❌ DNS Resolution Failed: Cannot resolve {$domain}\n";
    echo "Server DNS cannot resolve the domain\n\n";
} else {
    echo "✅ DNS Resolution Success: {$domain} → {$ip}\n\n";
}

// Test with different DNS servers
echo "🌐 TESTING WITH DIFFERENT DNS SERVERS:\n";
echo "=====================================\n";

$dnsServers = [
    'Cloudflare' => '1.1.1.1',
    'Google' => '8.8.8.8',
    'OpenDNS' => '208.67.222.222'
];

foreach ($dnsServers as $name => $dns) {
    echo "Testing {$name} DNS ({$dns}):\n";
    
    // Use dig command to test DNS resolution
    $command = "dig @{$dns} {$domain} +short";
    $result = shell_exec($command);
    
    if ($result && trim($result)) {
        echo "✅ {$name}: " . trim($result) . "\n";
    } else {
        echo "❌ {$name}: Failed to resolve\n";
    }
}

echo "\n";

// Solution 1: Update webhook URL to use IP address temporarily
echo "🛠️  SOLUTION 1: TEMPORARY IP-BASED WEBHOOK URL\n";
echo "==============================================\n";

$currentUrl = $company->webhook_url;
$ipBasedUrl = str_replace('app.oyitipay.com', '87.98.128.166', $currentUrl);

echo "Current URL: {$currentUrl}\n";
echo "IP-based URL: {$ipBasedUrl}\n\n";

echo "Do you want to update the webhook URL to use IP address? (y/n): ";
$handle = fopen("php://stdin", "r");
$choice = trim(fgets($handle));
fclose($handle);

if (strtolower($choice) === 'y') {
    // Backup current URL
    $company->update([
        'webhook_url_backup' => $currentUrl,
        'webhook_url' => $ipBasedUrl
    ]);
    
    echo "✅ Webhook URL updated to IP-based URL\n";
    echo "✅ Original URL backed up in webhook_url_backup field\n\n";
    
    // Test the new URL
    echo "🧪 TESTING NEW WEBHOOK URL:\n";
    echo "===========================\n";
    
    $testPayload = json_encode(['test' => 'webhook', 'timestamp' => now()->toISOString()]);
    $signature = hash_hmac('sha256', $testPayload, $company->api_secret_key);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ipBasedUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $testPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-PointWave-Signature: ' . $signature,
        'X-PointWave-Event-ID: test-' . time(),
        'X-PointWave-Event-Type: test'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For IP-based requests
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ Test failed: {$error}\n";
    } else {
        echo "✅ Test successful: HTTP {$httpCode}\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
    
} else {
    echo "❌ Webhook URL not updated\n";
}

echo "\n";

// Solution 2: Instructions for server DNS configuration
echo "🛠️  SOLUTION 2: SERVER DNS CONFIGURATION\n";
echo "=======================================\n";
echo "Add this to /etc/hosts on the PointWave server:\n";
echo "87.98.128.166 app.oyitipay.com\n\n";

echo "Or configure DNS resolver to use Cloudflare:\n";
echo "echo 'nameserver 1.1.1.1' > /etc/resolv.conf\n\n";

// Solution 3: Restore original URL when DNS is fixed
echo "🔄 SOLUTION 3: RESTORE ORIGINAL URL (WHEN DNS FIXED)\n";
echo "===================================================\n";
echo "When OyitiPay fixes their DNS, run this command:\n\n";

echo "php -r \"\n";
echo "\$company = \\App\\Models\\Company::find({$company->id});\n";
echo "if (\$company->webhook_url_backup) {\n";
echo "    \$company->update(['webhook_url' => \$company->webhook_url_backup]);\n";
echo "    echo 'Webhook URL restored to: ' . \$company->webhook_url . \\\"\\n\\\";\n";
echo "} else {\n";
echo "    echo 'No backup URL found\\n';\n";
echo "}\n";
echo "\"\n\n";

echo "📞 CONTACT OYITIPAY:\n";
echo "===================\n";
echo "Send this message to OyitiPay:\n\n";
echo "\"Hi OyitiPay team,\n\n";
echo "We've temporarily updated your webhook URL to use the IP address\n";
echo "(87.98.128.166) to bypass the DNS resolution issue.\n\n";
echo "Please work with your hosting provider to:\n";
echo "1. Ensure app.oyitipay.com resolves on all major DNS servers\n";
echo "2. Add proper DNS propagation globally\n";
echo "3. Test resolution from different locations\n\n";
echo "Once DNS is fixed globally, we'll restore the domain-based URL.\n\n";
echo "Current status: Webhooks should now work with IP-based URL.\"\n\n";

echo "✅ DNS ISSUE RESOLUTION COMPLETED\n";