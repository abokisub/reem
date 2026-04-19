<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Cleanup Orphaned Company Users ===\n\n";

// Find company_users with NULL user_id for Kobopoint
$orphanedUsers = DB::table('company_users')
    ->where('company_id', 4)
    ->whereNull('user_id')
    ->get();

echo "Found {$orphanedUsers->count()} orphaned company_users records\n\n";

if ($orphanedUsers->count() === 0) {
    echo "✓ No orphaned records found\n";
    exit(0);
}

echo "Orphaned Records:\n";
echo "─────────────────────────────────────\n";
foreach ($orphanedUsers as $user) {
    echo "ID: {$user->id}\n";
    echo "  Created: {$user->created_at}\n";
    echo "  User ID: " . ($user->user_id ?? 'NULL') . "\n\n";
}

echo "These records have no associated user and cannot have virtual accounts created.\n";
echo "They should be deleted.\n\n";

echo "Do you want to delete these orphaned records? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$response = trim($line);
fclose($handle);

if (strtolower($response) === 'yes' || strtolower($response) === 'y') {
    $deleted = DB::table('company_users')
        ->where('company_id', 4)
        ->whereNull('user_id')
        ->delete();
    
    echo "\n✓ Deleted {$deleted} orphaned records\n";
} else {
    echo "\nNo records deleted\n";
}

echo "\n";
