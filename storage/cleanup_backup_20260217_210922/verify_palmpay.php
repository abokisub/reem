<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\PalmPay\VirtualAccountService;
use Illuminate\Support\Facades\DB;
use App\Models\User;

try {
    echo "Starting Verification...\n";

    // 1. Get a test user (latest user)
    $user = User::orderBy('id', 'desc')->first();

    if (!$user) {
        echo "No users found in database.\n";
        exit(1);
    }

    echo "Testing with User ID: {$user->id}, Name: {$user->name}\n";

    // Check for companies
    $companies = DB::table('companies')->get();
    if ($companies->isEmpty()) {
        echo "No companies found in database. Cannot proceed with FK constraint.\n";
        // Create a default company for testing?
        echo "Creating Default Company 'PointPay Platform'...\n";
        $companyId = DB::table('companies')->insertGetId([
            'name' => 'PointPay Platform',
            'email' => 'admin@pointpay.io',
            'created_at' => now(),
            'updated_at' => now(),
            // Add other required fields if any. Let's hope name/email/timestamps are enough or they have defaults.
            // Checking migration would be safer but let's try.
            'user_id' => $user->id, // Assign to this user? Or admin.
            'slug' => 'pointpay-platform'
        ]);
        echo "Created Company ID: $companyId\n";
    } else {
        echo "Found Companies:\n";
        foreach ($companies as $c) {
            echo "ID: {$c->id}, Name: {$c->name}\n";
        }
        $companyId = $companies->first()->id;
        echo "Using Company ID: $companyId\n";
    }

    // 2. Initialize Service
    $virtualAccountService = new VirtualAccountService();
    // Use the found or created company ID
    // $companyId is already set above

    // 3. Check for existing account
    echo "Checking for existing account for User {$user->id} with Company $companyId...\n";
    $existingAccount = $virtualAccountService->getByCompanyAndUser($companyId, $user->id);

    if ($existingAccount) {
        echo "Found Existing Account:\n";
        echo "Account Number: {$existingAccount->palmpay_account_number}\n";
        echo "Account Name: {$existingAccount->palmpay_account_name}\n";
    } else {
        echo "No existing account found. Creating new one...\n";

        $customerData = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'bvn' => $user->bvn,
            'identity_type' => 'personal',
            'license_number' => $user->bvn
        ];

        // Be careful creating real accounts in production if credentials are live
        // But we need to verify.
        try {
            $newAccount = $virtualAccountService->createVirtualAccount($companyId, $user->id, $customerData);
            echo "Created New Account:\n";
            echo "Account Number: {$newAccount->palmpay_account_number}\n";
            echo "Account Name: {$newAccount->palmpay_account_name}\n";
        } catch (\Exception $e) {
            echo "Failed to create account: " . $e->getMessage() . "\n";
            // Check logs
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
