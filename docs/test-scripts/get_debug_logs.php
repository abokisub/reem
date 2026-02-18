<?php
/**
 * GET DEBUG LOGS
 * Extract recent logs for debugging
 */

$lines = isset($argv[1]) ? (int)$argv[1] : 100;

echo "\n=== RECENT LARAVEL LOGS (Last {$lines} lines) ===\n\n";

$logFile = __DIR__ . '/../../storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "❌ Log file not found: {$logFile}\n";
    exit(1);
}

// Get last N lines
$command = "tail -n {$lines} " . escapeshellarg($logFile);
$output = shell_exec($command);

if ($output) {
    echo $output;
} else {
    echo "❌ Could not read log file\n";
    exit(1);
}

echo "\n\n=== LOG FILE INFO ===\n";
echo "File: {$logFile}\n";
echo "Size: " . round(filesize($logFile) / 1024, 2) . " KB\n";
echo "Last Modified: " . date('Y-m-d H:i:s', filemtime($logFile)) . "\n";

echo "\n\n=== HOW TO USE ===\n";
echo "1. Copy all output above\n";
echo "2. Send to developer for debugging\n";
echo "3. Or save to file: php get_debug_logs.php > debug.txt\n";
echo "4. Get more lines: php get_debug_logs.php 500\n\n";
