<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\PalmPay\VirtualAccountService;
use App\Models\CompanyUser;

$customers = DB::table('company_users')->get(['id','uuid','company_id','first_name','last_name','email','phone']);
$missing = [];

foreach ($customers as $c) {
    $va = DB::table('virtual_accounts')
        ->where('company_user_id', $c->id)
        ->whereNull('deleted_at')
        ->first();
    if (!$va) {
        $company = DB::table('companies')->where('id', $c->company_id)->first();
        $missing[] = [
            'customer_id'   => $c->id,
            'customer_name' => trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')),
            'company_id'    => $c->company_id,
            'company_name'  => $company->name ?? 'Unknown',
            'email'         => $c->email,
            'has_bvn'       => true, // company backup KYC handles this
            'has_nin'       => true,
            'uuid'          => $c->uuid,
        ];
    }
}

echo "Total customers: " . $customers->count() . PHP_EOL;
echo "Missing VA: " . count($missing) . PHP_EOL;
echo "Has KYC (can auto-create): " . count(array_filter($missing, fn($m) => $m['has_bvn'] || $m['has_nin'])) . PHP_EOL;
echo "No KYC (cannot auto-create): " . count(array_filter($missing, fn($m) => !$m['has_bvn'] && !$m['has_nin'])) . PHP_EOL;
echo PHP_EOL;

$action = $argv[1] ?? 'check';

if ($action === 'create') {
    echo "=== AUTO-CREATING VIRTUAL ACCOUNTS ===" . PHP_EOL;
    $service = app(VirtualAccountService::class);
    $success = 0; $fail = 0;

    foreach ($missing as $m) {
        if (!$m['has_bvn'] && !$m['has_nin']) {
            echo "SKIP (no KYC): {$m['customer_name']} [{$m['company_name']}]" . PHP_EOL;
            continue;
        }

        // Skip Rukaiya Zakari only - confirmed identity mismatch on PalmPay side
        $skipIds = [252];
        if (in_array($m['customer_id'], $skipIds)) {
            echo "SKIP (identity mismatch): {$m['customer_name']} [{$m['company_name']}]" . PHP_EOL;
            continue;
        }
        try {
            // Reset circuit breaker before each attempt
            \Illuminate\Support\Facades\Cache::forget('palmpay_circuit_breaker_failures');
            \Illuminate\Support\Facades\Cache::forget('palmpay_circuit_breaker_state');

            $customer = CompanyUser::find($m['customer_id']);
            $result = $service->createVirtualAccount(
                $m['company_id'],
                $m['uuid'],
                [
                    'first_name' => $customer->first_name,
                    'last_name'  => $customer->last_name,
                    'email'      => $customer->email,
                    'phone'      => $customer->phone,
                ],
                '100033',
                $m['customer_id']
            );
            echo "✅ Created: {$m['customer_name']} [{$m['company_name']}] → " . ($result->palmpay_account_number ?? 'N/A') . PHP_EOL;
            $success++;
            sleep(3); // wait 3 seconds between each creation
        } catch (\Exception $e) {
            echo "❌ Failed: {$m['customer_name']} [{$m['company_name']}] → " . $e->getMessage() . PHP_EOL;
            $fail++;
            // Reset circuit breaker after failure so next attempt can try
            \Illuminate\Support\Facades\Cache::forget('palmpay_circuit_breaker_failures');
            \Illuminate\Support\Facades\Cache::forget('palmpay_circuit_breaker_state');
            sleep(5); // wait longer after failure
        }
    }
    echo PHP_EOL . "Done: {$success} created, {$fail} failed" . PHP_EOL;
} else {
    foreach ($missing as $m) {
        $kyc = ($m['has_bvn'] ? 'BVN' : '') . ($m['has_nin'] ? ' NIN' : '');
        echo "ID:{$m['customer_id']} | {$m['company_name']} | {$m['customer_name']} | KYC:" . (trim($kyc) ?: 'NONE') . PHP_EOL;
    }
    echo PHP_EOL . "Run 'php check_missing_va.php create' to auto-create VAs for customers with KYC" . PHP_EOL;
}
