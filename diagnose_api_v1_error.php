<?php

/**
 * Diagnose API V1 Error
 * 
 * Run this on the server to check what's causing the 500 error
 */

echo "========================================\n";
echo "DIAGNOSING API V1 ERROR\n";
echo "========================================\n\n";

// Check 1: Controller file exists
echo "1. Checking Controller File...\n";
$controllerPath = __DIR__ . '/app/Http/Controllers/API/V1/MerchantApiController.php';
if (file_exists($controllerPath)) {
    echo "   ✅ Controller exists: $controllerPath\n";
    echo "   File size: " . filesize($controllerPath) . " bytes\n";
    echo "   Last modified: " . date('Y-m-d H:i:s', filemtime($controllerPath)) . "\n";
} else {
    echo "   ❌ Controller NOT FOUND: $controllerPath\n";
}
echo "\n";

// Check 2: Middleware file exists
echo "2. Checking Middleware File...\n";
$middlewarePath = __DIR__ . '/app/Http/Middleware/V1/MerchantAuth.php';
if (file_exists($middlewarePath)) {
    echo "   ✅ Middleware exists: $middlewarePath\n";
    echo "   File size: " . filesize($middlewarePath) . " bytes\n";
    echo "   Last modified: " . date('Y-m-d H:i:s', filemtime($middlewarePath)) . "\n";
} else {
    echo "   ❌ Middleware NOT FOUND: $middlewarePath\n";
}
echo "\n";

// Check 3: Routes file
echo "3. Checking Routes File...\n";
$routesPath = __DIR__ . '/routes/api.php';
if (file_exists($routesPath)) {
    echo "   ✅ Routes file exists\n";
    
    // Check if V1 routes are defined
    $routesContent = file_get_contents($routesPath);
    if (strpos($routesContent, "Route::prefix('v1')") !== false) {
        echo "   ✅ V1 routes prefix found\n";
    } else {
        echo "   ❌ V1 routes prefix NOT FOUND\n";
    }
    
    if (strpos($routesContent, 'MerchantApiController') !== false) {
        echo "   ✅ MerchantApiController referenced in routes\n";
    } else {
        echo "   ❌ MerchantApiController NOT referenced in routes\n";
    }
} else {
    echo "   ❌ Routes file NOT FOUND\n";
}
echo "\n";

// Check 4: Laravel log file
echo "4. Checking Laravel Log (Last 30 Lines)...\n";
$logPath = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logPath)) {
    echo "   ✅ Log file exists\n";
    echo "   File size: " . filesize($logPath) . " bytes\n\n";
    echo "   Last 30 lines:\n";
    echo "   " . str_repeat("-", 70) . "\n";
    
    $lines = file($logPath);
    $lastLines = array_slice($lines, -30);
    foreach ($lastLines as $line) {
        echo "   " . rtrim($line) . "\n";
    }
    echo "   " . str_repeat("-", 70) . "\n";
} else {
    echo "   ❌ Log file NOT FOUND\n";
}
echo "\n";

// Check 5: .env file configuration
echo "5. Checking .env Configuration...\n";
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "   ✅ .env file exists\n";
    
    $envContent = file_get_contents($envPath);
    
    // Check APP_DEBUG
    if (preg_match('/APP_DEBUG=(.+)/', $envContent, $matches)) {
        $debug = trim($matches[1]);
        echo "   APP_DEBUG: $debug\n";
        if ($debug === 'true') {
            echo "   ⚠️  Debug is ON (good for troubleshooting)\n";
        }
    }
    
    // Check APP_ENV
    if (preg_match('/APP_ENV=(.+)/', $envContent, $matches)) {
        $env = trim($matches[1]);
        echo "   APP_ENV: $env\n";
    }
} else {
    echo "   ❌ .env file NOT FOUND\n";
}
echo "\n";

// Check 6: Composer autoload
echo "6. Checking Composer Autoload...\n";
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "   ✅ Composer autoload exists\n";
    
    // Try to load it
    try {
        require_once $autoloadPath;
        echo "   ✅ Autoload loaded successfully\n";
        
        // Check if controller class exists
        if (class_exists('App\\Http\\Controllers\\API\\V1\\MerchantApiController')) {
            echo "   ✅ MerchantApiController class can be loaded\n";
        } else {
            echo "   ❌ MerchantApiController class NOT FOUND\n";
            echo "   ⚠️  Run: composer dump-autoload\n";
        }
        
        // Check if middleware class exists
        if (class_exists('App\\Http\\Middleware\\V1\\MerchantAuth')) {
            echo "   ✅ MerchantAuth middleware class can be loaded\n";
        } else {
            echo "   ❌ MerchantAuth middleware class NOT FOUND\n";
            echo "   ⚠️  Run: composer dump-autoload\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error loading autoload: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ Composer autoload NOT FOUND\n";
    echo "   ⚠️  Run: composer install\n";
}
echo "\n";

// Check 7: Cache files
echo "7. Checking Cache Files...\n";
$routeCachePath = __DIR__ . '/bootstrap/cache/routes-v7.php';
$configCachePath = __DIR__ . '/bootstrap/cache/config.php';

if (file_exists($routeCachePath)) {
    echo "   ⚠️  Route cache exists (might be stale)\n";
    echo "   Last modified: " . date('Y-m-d H:i:s', filemtime($routeCachePath)) . "\n";
    echo "   ⚠️  Run: php artisan route:clear\n";
} else {
    echo "   ✅ No route cache (good)\n";
}

if (file_exists($configCachePath)) {
    echo "   ⚠️  Config cache exists (might be stale)\n";
    echo "   Last modified: " . date('Y-m-d H:i:s', filemtime($configCachePath)) . "\n";
    echo "   ⚠️  Run: php artisan config:clear\n";
} else {
    echo "   ✅ No config cache (good)\n";
}
echo "\n";

echo "========================================\n";
echo "DIAGNOSIS COMPLETE\n";
echo "========================================\n\n";

echo "RECOMMENDED ACTIONS:\n";
echo "1. Run: composer dump-autoload\n";
echo "2. Run: php artisan route:clear\n";
echo "3. Run: php artisan config:clear\n";
echo "4. Run: php artisan cache:clear\n";
echo "5. Check the Laravel log above for specific errors\n\n";
