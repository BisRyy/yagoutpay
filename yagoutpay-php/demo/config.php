<?php

// Load .env file if it exists
function loadEnvFile($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) {
            continue; // Skip comments
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// Load .env file from parent directory
loadEnvFile(__DIR__ . '/../.env');

// YagoutPay Configuration (read from environment)
define('MERCHANT_ID', getenv('MERCHANT_ID') ?: '');
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: '');
define('YAGOUT_ENVIRONMENT', getenv('ENVIRONMENT') ?: 'test');

// Base URL for callbacks (adjust this based on your domain)
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost:8000');
