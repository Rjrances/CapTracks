<?php
// Simple test script to check if PHP is working
echo "PHP is working!\n";
echo "Current directory: " . getcwd() . "\n";
echo "PHP version: " . phpversion() . "\n";

// Check if Laravel files exist
if (file_exists('artisan')) {
    echo "Laravel artisan file exists\n";
} else {
    echo "Laravel artisan file NOT found\n";
}

if (file_exists('vendor/autoload.php')) {
    echo "Composer autoload exists\n";
} else {
    echo "Composer autoload NOT found\n";
}

// Try to run a simple artisan command
echo "Testing artisan command...\n";
$output = shell_exec('php artisan --version 2>&1');
echo "Artisan output: " . $output . "\n";
