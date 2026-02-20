<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== GETTING ADMIN USER ID ===\n\n";

try {
    // Get admin user
    $admin = DB::table('users')
        ->where('type', 'ADMIN')
        ->first();
    
    if (!$admin) {
        echo "❌ No admin user found!\n";
        exit(1);
    }
    
    echo "✅ Admin user found:\n";
    echo "  ID: {$admin->id}\n";
    echo "  Email: {$admin->email}\n";
    echo "  Name: {$admin->name}\n";
    echo "  Type: {$admin->type}\n\n";
    
    echo "Your access token (user ID) is: {$admin->id}\n\n";
    
    echo "Now run this command:\n";
    echo "php test_actual_webhook_api.php {$admin->id}\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
