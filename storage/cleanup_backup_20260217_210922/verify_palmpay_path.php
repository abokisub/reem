<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

$merchantId = config('services.palmpay.merchant_id');
$appId = config('services.palmpay.app_id');
$baseUrl = 'https://open-gw-prod.palmpay-inc.com';
$signatureService = app(App\Services\PalmPay\PalmPaySignature::class);

$path = '/api/v2/general/merchant/queryBankList';

echo "Testing Path: $path\n";
$data = [
    'requestTime' => (int) (microtime(true) * 1000),
    'version' => 'V1.1',
    'nonceStr' => Str::random(32),
    'businessType' => 0
];

$signature = $signatureService->generateSignature($data);

try {
    $response = Http::timeout(10)->withHeaders([
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'CountryCode' => 'NG',
        'Authorization' => 'Bearer ' . $appId,
        'Signature' => $signature,
    ])->post($baseUrl . $path, $data);

    echo "Status: " . $response->status() . "\n";
    $body = $response->json();
    echo "Body: " . json_encode($body, JSON_PRETTY_PRINT) . "\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
