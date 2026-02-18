<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

$merchantId = config('services.palmpay.merchant_id');
$appId = config('services.palmpay.app_id');
$baseUrl = config('services.palmpay.base_url');
$signatureService = app(App\Services\PalmPay\PalmPaySignature::class);

$paths = [
    "/$merchantId/v2/payment/payout/queryBankList",
    "/$merchantId/api/v2/payment/payout/queryBankList",
    "/api/v2/$merchantId/payment/payout/queryBankList",
    "/v2/$merchantId/payment/payout/queryBankList",
    "/$merchantId/v2/payout/queryBankList",
];

foreach ($paths as $path) {
    echo "Testing Path: $path\n";
    $data = [
        'requestTime' => (int) (microtime(true) * 1000),
        'version' => 'V1.1',
        'nonceStr' => Str::random(32),
    ];

    $signature = $signatureService->generateSignature($data);

    try {
        $response = Http::timeout(5)->withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'CountryCode' => 'NG',
            'Authorization' => 'Bearer ' . $appId,
            'Signature' => $signature,
        ])->post($baseUrl . $path, $data);

        $body = $response->json();
        $respCode = $body['respCode'] ?? ($body['code'] ?? 'N/A');
        echo "Result: [$respCode] " . ($body['respMsg'] ?? ($body['message'] ?? 'N/A')) . "\n";

        if ($respCode !== 'OPEN_GW_000022') {
            echo "SUCCESS OR DIFFERENT ERROR: " . json_encode($body) . "\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "----------------------------------------\n";
}
