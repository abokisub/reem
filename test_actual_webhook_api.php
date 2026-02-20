<?php

// Simulate the actual API call that the frontend makes
$accessToken = $argv[1] ?? null;

if (!$accessToken) {
    echo "Usage: php test_actual_webhook_api.php <accessToken>\n";
    echo "Get your accessToken from localStorage in the browser\n";
    exit(1);
}

$url = "https://app.pointwave.ng/api/secure/webhooks?id={$accessToken}&page=1&limit=20&search=";

echo "=== TESTING ACTUAL API ENDPOINT ===\n\n";
echo "URL: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n\n";

if ($error) {
    echo "cURL Error: $error\n";
    exit(1);
}

echo "Raw Response:\n";
echo $response . "\n\n";

$json = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Decode Error: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "=== PARSED JSON STRUCTURE ===\n\n";
echo "Keys in response: " . implode(', ', array_keys($json)) . "\n\n";

if (isset($json['webhook_logs'])) {
    echo "✅ webhook_logs key exists\n";
    echo "Type: " . gettype($json['webhook_logs']) . "\n";
    
    if (is_array($json['webhook_logs'])) {
        echo "Keys in webhook_logs: " . implode(', ', array_keys($json['webhook_logs'])) . "\n\n";
        
        if (isset($json['webhook_logs']['data'])) {
            echo "✅ webhook_logs.data exists\n";
            echo "Count: " . count($json['webhook_logs']['data']) . " records\n\n";
            
            if (count($json['webhook_logs']['data']) > 0) {
                echo "First record sample:\n";
                print_r($json['webhook_logs']['data'][0]);
            }
        } else {
            echo "❌ webhook_logs.data does NOT exist\n";
            echo "This is why the frontend is failing!\n";
        }
    }
} else {
    echo "❌ webhook_logs key does NOT exist in response\n";
    echo "This is why the frontend is failing!\n";
}
