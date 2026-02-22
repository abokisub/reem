<?php
/**
 * List all virtual accounts for KoboPoint (PointWave Business)
 * Using their API credentials
 */

// KoboPoint API Credentials
$apiKey = '7db8dbb3991382487a1fc388a05d96a7139d92ba';
$secretKey = 'd8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c';
$businessId = '3450968aa027e86e3ff5b0169dc17edd7694a846';

// API endpoint
$baseUrl = 'https://app.pointwave.ng/api/v1';

echo "========================================\n";
echo "KoboPoint Virtual Accounts List\n";
echo "========================================\n\n";

// Function to make API request
function makeRequest($url, $apiKey, $secretKey, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'X-Business-ID: ' . $businessId,
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing only
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => $httpCode];
    }
    
    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// Method 1: Try to get virtual accounts via API
echo "Method 1: Fetching via API endpoint...\n";
$result = makeRequest($baseUrl . '/virtual-accounts', $apiKey, $secretKey);

if ($result['http_code'] === 200 && isset($result['response']['data'])) {
    $accounts = $result['response']['data'];
    echo "✓ Found " . count($accounts) . " virtual account(s)\n\n";
    
    foreach ($accounts as $index => $account) {
        echo "Virtual Account #" . ($index + 1) . ":\n";
        echo "  Customer ID: " . ($account['customer_id'] ?? 'N/A') . "\n";
        echo "  Customer Name: " . ($account['customer_name'] ?? 'N/A') . "\n";
        echo "  Account Number: " . ($account['account_number'] ?? 'N/A') . "\n";
        echo "  Account Name: " . ($account['account_name'] ?? 'N/A') . "\n";
        echo "  Bank Name: " . ($account['bank_name'] ?? 'N/A') . "\n";
        echo "  Status: " . ($account['status'] ?? 'N/A') . "\n";
        echo "  Created: " . ($account['created_at'] ?? 'N/A') . "\n";
        echo "\n";
    }
} else {
    echo "✗ API endpoint not available or returned error\n";
    echo "HTTP Code: " . $result['http_code'] . "\n";
    if (isset($result['response'])) {
        echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";
}

// Method 2: Direct database query (if running on server)
echo "Method 2: Direct database query...\n";
echo "Run this SQL query on the server:\n\n";

$sql = <<<SQL
-- Find company ID for KoboPoint
SELECT id, name, email, business_id 
FROM companies 
WHERE business_id = '3450968aa027e86e3ff5b0169dc17edd7694a846'
   OR api_public_key = '7db8dbb3991382487a1fc388a05d96a7139d92ba';

-- List all virtual accounts for this company
SELECT 
    va.id,
    va.customer_id,
    va.account_number AS palmpay_account_number,
    va.account_name AS palmpay_account_name,
    va.bank_name AS palmpay_bank_name,
    va.status,
    va.created_at,
    cu.first_name,
    cu.last_name,
    cu.email,
    cu.phone
FROM virtual_accounts va
LEFT JOIN company_users cu ON va.customer_id = cu.id
WHERE va.company_id = (
    SELECT id FROM companies 
    WHERE business_id = '3450968aa027e86e3ff5b0169dc17edd7694a846'
    LIMIT 1
)
ORDER BY va.created_at DESC;

-- Count virtual accounts
SELECT 
    COUNT(*) as total_accounts,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_accounts,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_accounts
FROM virtual_accounts
WHERE company_id = (
    SELECT id FROM companies 
    WHERE business_id = '3450968aa027e86e3ff5b0169dc17edd7694a846'
    LIMIT 1
);
SQL;

echo $sql . "\n\n";

// Method 3: Using PHP artisan tinker
echo "Method 3: Using Laravel Tinker (run on server)...\n\n";

$tinkerCommands = <<<TINKER
# SSH into server and run:
cd /home/aboksdfs/app.pointwave.ng
php artisan tinker

# Then run these commands:
\$company = \App\Models\Company::where('business_id', '3450968aa027e86e3ff5b0169dc17edd7694a846')->first();
echo "Company: " . \$company->name . " (ID: " . \$company->id . ")\\n";

\$accounts = \App\Models\VirtualAccount::where('company_id', \$company->id)->get();
echo "Total Virtual Accounts: " . \$accounts->count() . "\\n\\n";

foreach (\$accounts as \$account) {
    echo "Account Number: " . \$account->account_number . "\\n";
    echo "Account Name: " . \$account->account_name . "\\n";
    echo "Bank: " . \$account->bank_name . "\\n";
    echo "Status: " . \$account->status . "\\n";
    echo "Customer: " . (\$account->customer ? \$account->customer->first_name . " " . \$account->customer->last_name : "N/A") . "\\n";
    echo "---\\n";
}
TINKER;

echo $tinkerCommands . "\n\n";

echo "========================================\n";
echo "Summary\n";
echo "========================================\n";
echo "Company: PointWave Business (KoboPoint)\n";
echo "Business ID: $businessId\n";
echo "API Key: $apiKey\n";
echo "\n";
echo "To get the list, either:\n";
echo "1. Use the API endpoint (if available)\n";
echo "2. Run the SQL query on the database\n";
echo "3. Use Laravel Tinker on the server\n";
echo "\n";

?>
