<?php
/**
 * OPcache Clear Script
 * Access via: https://app.pointwave.ng/clear-opcache.php
 * DELETE THIS FILE AFTER USE FOR SECURITY
 */

// Security: Only allow from localhost or specific IP
$allowed_ips = ['127.0.0.1', '::1'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!in_array($client_ip, $allowed_ips) && $client_ip !== 'unknown') {
    // For security, require a secret key
    $secret = $_GET['secret'] ?? '';
    $expected_secret = md5('pointwave_opcache_clear_' . date('Y-m-d'));
    
    if ($secret !== $expected_secret) {
        http_response_code(403);
        die('Access denied. Secret: ' . $expected_secret);
    }
}

echo "<h1>OPcache Clear Utility</h1>";
echo "<p>Client IP: {$client_ip}</p>";
echo "<hr>";

// Check if OPcache is enabled
if (!function_exists('opcache_reset')) {
    echo "<p style='color: red;'>❌ OPcache extension not available or opcache_reset() is disabled</p>";
    echo "<p>OPcache status: " . (extension_loaded('opcache') ? 'Loaded' : 'Not loaded') . "</p>";
    
    if (extension_loaded('opcache')) {
        echo "<p>opcache_reset() function: " . (function_exists('opcache_reset') ? 'Available' : 'Disabled in php.ini') . "</p>";
        echo "<p>Check php.ini for: opcache.restrict_api</p>";
    }
    
    exit;
}

// Get OPcache status before clearing
$status_before = opcache_get_status(false);
echo "<h2>Before Clear:</h2>";
echo "<ul>";
echo "<li>OPcache Enabled: " . ($status_before['opcache_enabled'] ? 'Yes' : 'No') . "</li>";
echo "<li>Cache Full: " . ($status_before['cache_full'] ? 'Yes' : 'No') . "</li>";
echo "<li>Cached Scripts: " . ($status_before['opcache_statistics']['num_cached_scripts'] ?? 0) . "</li>";
echo "<li>Hits: " . ($status_before['opcache_statistics']['hits'] ?? 0) . "</li>";
echo "<li>Misses: " . ($status_before['opcache_statistics']['misses'] ?? 0) . "</li>";
echo "</ul>";

// Clear OPcache
$result = opcache_reset();

echo "<hr>";
echo "<h2>Clear Result:</h2>";
if ($result) {
    echo "<p style='color: green; font-size: 20px;'>✅ OPcache cleared successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to clear OPcache</p>";
}

// Get status after clearing
$status_after = opcache_get_status(false);
echo "<h2>After Clear:</h2>";
echo "<ul>";
echo "<li>Cached Scripts: " . ($status_after['opcache_statistics']['num_cached_scripts'] ?? 0) . "</li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Test the transfer endpoint again</li>";
echo "<li>Check logs for the new endpoint: /api/v2/payment/merchant/payout/transfer</li>";
echo "<li>DELETE THIS FILE for security: rm public/clear-opcache.php</li>";
echo "</ol>";

echo "<hr>";
echo "<p style='color: orange;'>⚠️ <strong>SECURITY WARNING:</strong> Delete this file immediately after use!</p>";
echo "<p>Run: <code>rm public/clear-opcache.php</code></p>";
