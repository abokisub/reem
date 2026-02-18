<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\PalmPay\AccountVerificationService;

$service = new AccountVerificationService();

echo "Fetching All Banks...\n";
$banks = $service->getBanks();

file_put_contents('all_banks.json', json_encode($banks, JSON_PRETTY_PRINT));
echo "Bank list saved to all_banks.json. Total count: " . count($banks) . "\n";

$opayCode = null;
$searchTerm = 'OPay';

foreach ($banks as $bank) {
    if (stripos($bank['bankName'], $searchTerm) !== false || stripos($bank['bankName'], 'Paycom') !== false) {
        echo "Found potential OPay match: " . $bank['bankName'] . " - Code: " . $bank['bankCode'] . "\n";
        $opayCode = $bank['bankCode'];
    }
}

if ($opayCode) {
    $accountNumber = '7040540018'; // Corrected
    echo "Verifying Account $accountNumber via Code $opayCode...\n";
    $result = $service->verifyAccount($accountNumber, $opayCode);
    print_r($result);
} else {
    echo "Could not find OPay or Paycom in bank list.\n";
}
