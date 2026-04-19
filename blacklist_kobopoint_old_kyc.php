<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use Illuminate\Support\Facades\Cache;

echo "=== Blacklisting Kobopoint's Old KYC ===\n\n";

$kobopoint = Company::find(4);

if (!$kobopoint) {
    echo "❌ Kobopoint not found\n";
    exit(1);
}

echo "Company: {$kobopoint->name}\n";
echo "Old Director NIN: " . substr($kobopoint->director_nin, 0, 5) . "***\n";
echo "New Backup Director 2 NIN: " . substr($kobopoint->backup_director_2_nin ?? 'Not set', 0, 5) . "***\n\n";

// Blacklist the old director_nin
$blacklistKey = "kyc_blacklist_company_{$kobopoint->id}";
$blacklist = Cache::get($blacklistKey, []);

$blacklist['director_nin'] = [
    'blacklisted_at' => now()->toISOString(),
    'reason' => 'Hit PalmPay license number limit',
    'expires_at' => now()->addDays(30)->toISOString()
];

Cache::put($blacklistKey, $blacklist, now()->addDays(30));

echo "✓ Blacklisted director_nin for 30 days\n";
echo "✓ System will now use backup_director_2_nin\n\n";

echo "Now run:\n";
echo "php artisan emergency:kyc-fix --company=4\n";
