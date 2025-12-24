<?php

require_once __DIR__ . '/../src/config/bootstrap.php';

$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

$path = parse_url($request_uri, PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

if ($path === '/') {
    echo "Camagru - Welcome!";
} else {
    http_response_code(404);
    echo "404 - Page not found";
}
