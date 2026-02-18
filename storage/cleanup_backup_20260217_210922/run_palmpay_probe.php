<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\PalmPay\PalmPayClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

$appId = config('services.palmpay.app_id');
$merchantId = config('services.palmpay.merchant_id');
$signatureService = app(App\Services\PalmPay\PalmPaySignature::class);

$baseUrls = [
    'https://open-gw-prod.palmpay-inc.com',
    'https://open-gw.palmpay.com',
    'https://open.palmpay.com',
    'https://open.palmpay-inc.com',
];

$paths = [
    '/api/v2/payment/merchant/payout/queryBankList',
    '/api/v2/merchant/payment/payout/queryBankList',
    '/api/v2/payment/payout/queryBankList',
];

foreach ($baseUrls as $baseUrl) {
    echo "=== TESTING BASE URL: $baseUrl ===\n";
    foreach ($paths as $path) {
        $url = $baseUrl . $path;
        $data = [
            'requestTime' => (int) (microtime(true) * 1000),
            'version' => 'V1.1',
            'nonceStr' => Str::random(32),
        ];

        $signature = $signatureService->generateSignature($data);

        try {
            echo "Testing POST $path: ";
            $response = Http::timeout(5)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'CountryCode' => 'NG',
                    'Authorization' => 'Bearer ' . $appId,
                    'Signature' => $signature,
                ])
                ->post($url, $data);

            if ($response->status() === 404) {
                echo "404 Not Found\n";
            } else {
                $body = $response->json();
                $respCode = $body['respCode'] ?? ($body['code'] ?? 'N/A');
                $respMsg = $body['respMsg'] ?? ($body['message'] ?? 'N/A');
                echo "[$respCode] $respMsg\n";
                if ($respCode !== 'OPEN_GW_000022') {
                    echo "HIT! Response from gateway: " . json_encode($body) . "\n";
                }
            }
        } catch (\Exception $e) {
            echo "Failed: " . $e->getMessage() . "\n";
        }
    }
    echo "--------------------------------------------------\n";
}
