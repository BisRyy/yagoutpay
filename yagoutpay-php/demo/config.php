<?php

// YagoutPay Configuration (read from environment)
define('MERCHANT_ID', getenv('MERCHANT_ID') ?: '');
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: '');
define('YAGOUT_ENVIRONMENT', getenv('ENVIRONMENT') ?: 'test');

// Base URL for callbacks (adjust this based on your domain)
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost:8000');
