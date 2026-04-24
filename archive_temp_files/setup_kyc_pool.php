<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$nins = [
    '85196003625','74519375329','91722661630','99948225627','91317874064',
    '96417746714','41065828416','21651586741','35257106066','40039678666',
    '17943087353','42652964166','31809809557','80787656915','80494070584',
    '10317669760','20541837762','61059543793','81985339443','51991392935',
    '51385807070','45537484661','50858056056','14370423757','60634313040',
    '94258013398','62886691293','79023979029','60142426470','78872717072',
    '69084071185','90653536044','98991179838','71379630996','79458549942'
];

$bvns = [
    '22694207594','22611636687','22170609324','22295703611',
    '22445778894','22410324107','22835718778','22490148602',
    '22841851753','22555466364','22502902835','22795477746',
    '22825723294','22588787966','22762085420','22556177106',
    '22277778969','22428646990','22327764980','22258652101',
    '22336821667','22278844126','22280329222','22336134677',
    '22232557767','22192482516','22827887020','22548080706',
    '22336946173','22302542754','22363612007'
];

$action = $argv[1] ?? 'check';

// Get existing pool entries
$existingNins = DB::table('global_kyc_pool')->where('kyc_type','nin')->pluck('kyc_number')->toArray();
$existingBvns = DB::table('global_kyc_pool')->where('kyc_type','bvn')->pluck('kyc_number')->toArray();

// Get NINs used in virtual_accounts (company director_nin)
$usedNins = DB::table('companies')->whereNotNull('director_nin')->pluck('director_nin')->toArray();
$usedNins = array_merge($usedNins, DB::table('companies')->whereNotNull('nin')->pluck('nin')->toArray());

// Get BVNs used
$usedBvns = DB::table('companies')->whereNotNull('director_bvn')->pluck('director_bvn')->toArray();
$usedBvns = array_merge($usedBvns, DB::table('companies')->whereNotNull('bvn')->pluck('bvn')->toArray());

echo "=== NIN STATUS ===" . PHP_EOL;
$freshNins = 0; $poolNins = 0; $usedNinsCount = 0;
foreach ($nins as $nin) {
    $inPool = in_array($nin, $existingNins);
    $inUse  = in_array($nin, $usedNins);
    $status = $inPool ? 'IN_POOL' : ($inUse ? 'IN_USE_BY_COMPANY' : 'FRESH');
    if ($status === 'FRESH') $freshNins++;
    if ($status === 'IN_POOL') $poolNins++;
    if ($status === 'IN_USE_BY_COMPANY') $usedNinsCount++;
    echo substr($nin,0,5).'*** | ' . $status . PHP_EOL;
}
echo "Fresh: $freshNins | In Pool: $poolNins | Used by company: $usedNinsCount" . PHP_EOL . PHP_EOL;

echo "=== BVN STATUS ===" . PHP_EOL;
$freshBvns = 0;
foreach ($bvns as $bvn) {
    $inPool = in_array($bvn, $existingBvns);
    $inUse  = in_array($bvn, $usedBvns);
    $status = $inPool ? 'IN_POOL' : ($inUse ? 'IN_USE_BY_COMPANY' : 'FRESH');
    if ($status === 'FRESH') $freshBvns++;
    echo substr($bvn,0,5).'*** | ' . $status . PHP_EOL;
}
echo "Fresh BVNs: $freshBvns" . PHP_EOL . PHP_EOL;

if ($action === 'add') {
    echo "=== ADDING FRESH ENTRIES TO POOL ===" . PHP_EOL;
    $added = 0;
    foreach ($nins as $nin) {
        if (!in_array($nin, $existingNins)) {
            DB::table('global_kyc_pool')->insert([
                'kyc_type'      => 'nin',
                'kyc_number'    => $nin,
                'is_active'     => 1,
                'usage_count'   => 0,
                'success_count' => 0,
                'failure_count' => 0,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            echo "Added NIN: " . substr($nin,0,5) . "***" . PHP_EOL;
            $added++;
        }
    }
    foreach ($bvns as $bvn) {
        if (!in_array($bvn, $existingBvns)) {
            DB::table('global_kyc_pool')->insert([
                'kyc_type'      => 'bvn',
                'kyc_number'    => $bvn,
                'is_active'     => 1,
                'usage_count'   => 0,
                'success_count' => 0,
                'failure_count' => 0,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            echo "Added BVN: " . substr($bvn,0,5) . "***" . PHP_EOL;
            $added++;
        }
    }
    echo PHP_EOL . "Total added: $added" . PHP_EOL;
    echo "New pool total: " . DB::table('global_kyc_pool')->count() . PHP_EOL;
} else {
    echo "Run 'php setup_kyc_pool.php add' to add all fresh entries to the pool" . PHP_EOL;
}
