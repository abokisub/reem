<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\CompanyLogsController;
use Illuminate\Http\Request;

echo "=== TESTING WEBHOOK API ENDPOINT ===\n\n";

// Get admin user
$admin = DB::table('users')->where('type', 'ADMIN')->first();

if (!$admin) {
    echo "❌ No admin user found\n";
    exit(1);
}

echo "✅ Found admin user: {$admin->email} (ID: {$admin->id})\n\n";

// Create a mock request
$request = new Request();
$request->merge(['id' => $admin->id]);

// Call the controller method
$controller = new CompanyLogsController();
$response = $controller->getWebhooks($request);

echo "=== API RESPONSE ===\n";
echo json_encode($response->getData(), JSON_PRETTY_PRINT) . "\n";
