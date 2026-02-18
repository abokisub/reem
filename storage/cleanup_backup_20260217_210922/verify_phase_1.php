<?php

use App\Models\User;
use App\Services\PhaseGate;
use App\Services\CustomerIdGenerator;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Testing Phase 1 ---\n";

// Test 1: Customer ID Generation Service
echo "1. Generating Customer ID: ";
$id = CustomerIdGenerator::generate();
echo $id . " [OK]\n";

// Test 2: Phase Gate
echo "2. Checking Phase 1 Access: ";
try {
    PhaseGate::enforce(PhaseGate::PHASE_1_VIRTUAL_ACCOUNTS);
    echo "Allowed [OK]\n";
} catch (\Exception $e) {
    echo "Blocked: " . $e->getMessage() . " [FAIL]\n";
}

// Test 3: User Model Event
echo "3. Testing User Model Creation: ";
try {
    $user = new User();
    $user->first_name = 'Phase1';
    $user->last_name = 'Test';
    $user->name = 'Phase1 Test';
    $user->username = 'phase1test_' . time();
    $user->email = 'phase1_' . time() . '@test.com';
    $user->phone = '080' . rand(10000000, 99999999);
    $user->password = bcrypt('password');
    $user->save();

    $user = User::find($user->id);
    if ($user->customer_id && str_starts_with($user->customer_id, 'CUST-')) {
        echo "Created with ID: " . $user->customer_id . " [OK]\n";
        // Clean up
        $user->delete();
    } else {
        echo "Failed to generate ID [FAIL]\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "--- Phase 1 Verification Complete ---\n";
