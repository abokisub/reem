<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$client = app(App\Services\PalmPay\PalmPayClient::class);

$paths = [
    '/api/v2/payment/merchant/payout/queryBankList',
    '/api/v2/merchant/payment/payout/queryBankList',
    '/v2/payment/merchant/payout/queryBankList',
    '/v2/merchant/payment/payout/queryBankList',
    '/api/v2/payment/payout/queryBankList',
    '/api/v2/payout/queryBankList',
    '/api/v2/merchant/payout/queryBankList',
    '/api/v2/payment/banks',
    '/api/v2/merchant/banks',
];

foreach ($paths as $path) {
    echo "Testing POST Path: $path\n";
    try {
        $response = $client->post($path, []);
        if (isset($response['respCode']) && $response['respCode'] === 'OPEN_GW_000022') {
            echo "Result: Invalid URL Router\n";
        }
        else {
            echo "Result SUCCESS or DIFFERENT ERROR:\n";
            var_dump($response);
        }
    }
    catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    echo "Testing GET Path: $path\n";
    try {
        $response = $client->get($path, []);
        if (isset($response['respCode']) && $response['respCode'] === 'OPEN_GW_000022') {
            echo "Result: Invalid URL Router\n";
        }
        else {
            echo "Result SUCCESS or DIFFERENT ERROR:\n";
            var_dump($response);
        }
    }
    catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    echo "----------------------------------------\n";
}