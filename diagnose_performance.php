<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "========================================\n";
echo "PointWave Performance Diagnostics\n";
echo "========================================\n\n";

// 1. Check database connection speed
echo "1. Testing Database Connection...\n";
$start = microtime(true);
try {
    DB::connection()->getPdo();
    $dbTime = round((microtime(true) - $start) * 1000, 2);
    echo "   ✓ Database connected in {$dbTime}ms\n";
    
    if ($dbTime > 100) {
        echo "   ⚠ WARNING: Database connection is slow (>{$dbTime}ms)\n";
    }
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
}

// 2. Test query performance
echo "\n2. Testing Query Performance...\n";
$start = microtime(true);
try {
    $count = DB::table('users')->count();
    $queryTime = round((microtime(true) - $start) * 1000, 2);
    echo "   ✓ Query executed in {$queryTime}ms (found {$count} users)\n";
    
    if ($queryTime > 500) {
        echo "   ⚠ WARNING: Queries are slow (>{$queryTime}ms)\n";
    }
} catch (Exception $e) {
    echo "   ✗ Query failed: " . $e->getMessage() . "\n";
}

// 3. Check cache configuration
echo "\n3. Checking Cache Configuration...\n";
$cacheDriver = config('cache.default');
echo "   Cache Driver: {$cacheDriver}\n";

if ($cacheDriver === 'file') {
    echo "   ⚠ WARNING: Using file cache (slow). Consider Redis or Memcached\n";
}

// 4. Check session configuration
echo "\n4. Checking Session Configuration...\n";
$sessionDriver = config('session.driver');
echo "   Session Driver: {$sessionDriver}\n";

if ($sessionDriver === 'file') {
    echo "   ⚠ WARNING: Using file sessions (slow). Consider database or Redis\n";
}

// 5. Check storage permissions
echo "\n5. Checking Storage Permissions...\n";
$storagePath = storage_path();
$isWritable = is_writable($storagePath);
echo "   Storage writable: " . ($isWritable ? "✓ Yes" : "✗ No") . "\n";

if (!$isWritable) {
    echo "   ✗ ERROR: Storage directory is not writable!\n";
    echo "   Run: chmod -R 775 storage bootstrap/cache\n";
}

// 6. Check for slow queries
echo "\n6. Checking for Slow Queries...\n";
try {
    // Enable query logging
    DB::enableQueryLog();
    
    // Run a complex query
    $start = microtime(true);
    $transactions = DB::table('transactions')
        ->join('companies', 'transactions.company_id', '=', 'companies.id')
        ->select('transactions.*', 'companies.company_name')
        ->limit(10)
        ->get();
    $complexQueryTime = round((microtime(true) - $start) * 1000, 2);
    
    echo "   Complex query time: {$complexQueryTime}ms\n";
    
    if ($complexQueryTime > 1000) {
        echo "   ⚠ WARNING: Complex queries are very slow\n";
        echo "   Consider adding database indexes\n";
    }
    
    $queries = DB::getQueryLog();
    echo "   Queries executed: " . count($queries) . "\n";
    
} catch (Exception $e) {
    echo "   ✗ Query test failed: " . $e->getMessage() . "\n";
}

// 7. Check memory usage
echo "\n7. Checking Memory Usage...\n";
$memoryUsage = round(memory_get_usage() / 1024 / 1024, 2);
$memoryLimit = ini_get('memory_limit');
echo "   Current memory usage: {$memoryUsage}MB\n";
echo "   Memory limit: {$memoryLimit}\n";

// 8. Check OPcache status
echo "\n8. Checking OPcache Status...\n";
if (function_exists('opcache_get_status')) {
    $opcache = opcache_get_status();
    if ($opcache && $opcache['opcache_enabled']) {
        echo "   ✓ OPcache is enabled\n";
        echo "   Hit rate: " . round($opcache['opcache_statistics']['opcache_hit_rate'], 2) . "%\n";
        echo "   Memory usage: " . round($opcache['memory_usage']['used_memory'] / 1024 / 1024, 2) . "MB\n";
    } else {
        echo "   ✗ OPcache is disabled\n";
        echo "   ⚠ WARNING: Enable OPcache for better performance\n";
    }
} else {
    echo "   ✗ OPcache not available\n";
}

// 9. Check for missing indexes
echo "\n9. Checking Database Indexes...\n";
try {
    $tables = ['transactions', 'virtual_accounts', 'companies', 'company_wallets'];
    
    foreach ($tables as $table) {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        echo "   {$table}: " . count($indexes) . " indexes\n";
    }
} catch (Exception $e) {
    echo "   ✗ Index check failed: " . $e->getMessage() . "\n";
}

// 10. Check log file size
echo "\n10. Checking Log File Size...\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logSize = round(filesize($logFile) / 1024 / 1024, 2);
    echo "   Log file size: {$logSize}MB\n";
    
    if ($logSize > 100) {
        echo "   ⚠ WARNING: Log file is very large (>{$logSize}MB)\n";
        echo "   Consider rotating logs or clearing old entries\n";
    }
} else {
    echo "   Log file not found\n";
}

echo "\n========================================\n";
echo "Diagnostics Complete!\n";
echo "========================================\n\n";

echo "RECOMMENDATIONS:\n";
echo "1. Run: bash optimize_production.sh\n";
echo "2. Enable Redis cache if available\n";
echo "3. Add database indexes for frequently queried columns\n";
echo "4. Monitor slow query log\n";
echo "5. Consider upgrading server resources if needed\n";
