<?php

echo "=== Basic PHP Test ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Current directory: " . getcwd() . "\n\n";

echo "=== Testing Laravel Bootstrap ===\n";

try {
    require __DIR__.'/vendor/autoload.php';
    echo "✅ Autoload successful\n";
    
    $app = require_once __DIR__.'/bootstrap/app.php';
    echo "✅ App bootstrap successful\n";
    
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    echo "✅ Kernel bootstrap successful\n";
    
} catch (\Exception $e) {
    echo "❌ Bootstrap failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Testing Database Connection ===\n";

try {
    use Illuminate\Support\Facades\DB;
    
    $result = DB::select('SELECT 1 as test');
    echo "✅ Database connection successful\n";
    
} catch (\Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== Testing Company Model ===\n";

try {
    use App\Models\Company;
    
    $company = Company::find(10);
    
    if ($company) {
        echo "✅ Found Amtpay company\n";
        echo "ID: {$company->id}\n";
        echo "Name: {$company->name}\n";
        echo "Webhook URL: {$company->webhook_url}\n";
    } else {
        echo "❌ Amtpay company not found\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Company query failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Done ===\n";
