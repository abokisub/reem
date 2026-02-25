<?php
// Simplest possible test - no Laravel, no dependencies
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Hello from PHP\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Current directory: " . getcwd() . "\n";
echo "Script: " . __FILE__ . "\n";

// Force output
flush();
ob_flush();
