$client = app(App\Services\PalmPay\PalmPayClient::class);
try {
echo "Testing queryBalance...\n";
$response = $client->post('/api/v2/payment/merchant/payout/queryBalance', []);
dump($response);
} catch (\Exception $e) {
echo "Error: " . $e->getMessage() . "\n";
}   echo "Error 1: " . $e->getMessage() . "\n";
}

echo "Testing Path 2: /api/v2/merchant/payment/payout/queryBalance\n";
try {
    $response = $client->post('/api/v2/merchant/payment/payout/queryBalance', []);
    dump($response);
} catch (\Exception $e) {
    echo "Error 2: " . $e->getMessage() . "\n";
}

echo "Testing Path 3: /api/v2/merchant/payment/payout/queryBankList\n";
try {
    $response = $client->post('/api/v2/merchant/payment/payout/queryBankList', []);
    dump($response);
} catch (\Exception $e) {
    echo "Error 3: " . $e->getMessage() . "\n";
}