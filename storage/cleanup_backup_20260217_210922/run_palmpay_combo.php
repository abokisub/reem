<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

$merchantId = '126020209274801';
$appIds = [
    'L260209175060958729372', // pointwave 1
    'L260209175026597404531', // pointwave 2
];

$baseUrl = 'https://open-gw-prod.palmpay-inc.com';
$signatureService = app(App\Services\PalmPay\PalmPaySignature::class);

$combos = [
    ['auth' => $merchantId, 'appInHeader' => true, 'appInBody' => false],
    ['auth' => $merchantId, 'appInHeader' => false, 'appInBody' => true],
    ['auth' => $merchantId, 'appInHeader' => true, 'appInBody' => true],
    ['auth' => 'L260209175060958729372', 'appInHeader' => false, 'appInBody' => false], // This is what I tried before
    ['auth' => 'L260209175060958729372', 'merchantInBody' => true],
];

$paths = [
    '/api/v2/payment/merchant/payout/queryBankList',
    '/api/v2/merchant/payment/payout/queryBankList',
    '/api/v2/payment/payout/queryBankList',
    '/api/v2/payout/queryBankList',
];

foreach ($appIds as $appId) {
    echo "=== TESTING APP ID: $appId ===\n";
    foreach ($combos as $combo) {
        $authVal = ($combo['auth'] === 'L260209175060958729372') ? $appId : $combo['auth'];

        foreach ($paths as $path) {
            $data = [
                'requestTime' => (int) (microtime(true) * 1000),
                'version' => 'V1.1',
                'nonceStr' => Str::random(32),
            ];

            if ($combo['appInBody'] ?? false)
                $data['appId'] = $appId;
            if ($combo['merchantInBody'] ?? false)
                $data['merchantId'] = $merchantId;

            $signature = $signatureService->generateSignature($data);

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'CountryCode' => 'NG',
                'Authorization' => 'Bearer ' . $authVal,
                'Signature' => $signature,
            ];

            if ($combo['appInHeader'] ?? false) {
                $headers['AppId'] = $appId;
                $headers['X-App-Id'] = $appId;
            }

            try {
                $url = $baseUrl . $path;
                echo "Path $path | Auth " . substr($authVal, 0, 4) . "... | AppInBody: " . ($combo['appInBody'] ? 'Y' : 'N') . ": ";
                $response = Http::timeout(5)->withHeaders($headers)->post($url, $data);
                $body = $response->json();
                $respCode = $body['respCode'] ?? ($body['code'] ?? 'N/A');
                $respMsg = $body['respMsg'] ?? ($body['message'] ?? 'N/A');
                echo "[$respCode] $respMsg\n";

                if ($respCode !== 'OPEN_GW_000022') {
                    echo "HIT! -> " . json_encode($body) . "\n";
                    exit("SUCCESS FOUND\n");
                }
            } catch (\Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
    }
}
