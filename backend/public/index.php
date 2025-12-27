<?php

require_once __DIR__ . '/../src/config/bootstrap.php';
require_once __DIR__ . '/../src/config/router.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';

$router = new Router();

$router->get('/', function() {
    $view = 'index';
    $title = 'Camagru - Gallery';
    $csrfToken = generateCSRFToken();
    require_once __DIR__ . '/../src/views/layout.php';
});

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);

$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);

$router->get('/verify/{token}', [AuthController::class, 'verify']);

$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);

$router->get('/reset-password/{token}', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);

$router->get('/logout', [AuthController::class, 'logout']);

$router->dispatch();
