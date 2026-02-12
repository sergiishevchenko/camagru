<?php

require_once __DIR__ . '/env.php';

if (env('APP_ENV') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

date_default_timezone_set('UTC');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

require_once __DIR__ . '/setup.php';

require_once __DIR__ . '/../utils/functions.php';
