<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$customers = DB::table('company_users')->get(['id','uuid','company_id','first_name','last_name','email']);
$missing = [];

foreach ($customers as $c) {
    $va = DB::table('virtual_accounts')
        ->where('company_user_id', $c->id)
        ->whereNull('deleted_at')
        ->first();
    if (!$va) {
        $company = DB::table('companies')->where('id', $c->company_id)->first();
        $missing[] = [
            'customer_id'  => $c->id,
            'customer_name'=> trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')),
            'company_id'   => $c->company_id,
            'company_name' => $company->name ?? 'Unknown',
            'email'        => $c->email,
        ];
    }
}

echo "Total customers: " . $customers->count() . PHP_EOL;
echo "Missing VA: " . count($missing) . PHP_EOL . PHP_EOL;

foreach ($missing as $m) {
    echo "Customer ID:{$m['customer_id']} | Company:{$m['company_name']} (ID:{$m['company_id']}) | Name:{$m['customer_name']}" . PHP_EOL;
}
