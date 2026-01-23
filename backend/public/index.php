<?php

require_once __DIR__ . '/../src/config/bootstrap.php';
require_once __DIR__ . '/../src/config/router.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/ImageController.php';
require_once __DIR__ . '/../src/controllers/GalleryController.php';
require_once __DIR__ . '/../src/controllers/LikeController.php';
require_once __DIR__ . '/../src/controllers/CommentController.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';

$router = new Router();

$router->get('/', [GalleryController::class, 'index']);

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

$router->get('/edit', [ImageController::class, 'showEdit'], AuthMiddleware::class);
$router->post('/edit/capture', [ImageController::class, 'capture'], AuthMiddleware::class);
$router->post('/edit/upload', [ImageController::class, 'upload'], AuthMiddleware::class);

$router->delete('/image/{id}', [ImageController::class, 'delete'], AuthMiddleware::class);

$router->post('/like/{imageId}', [LikeController::class, 'toggle'], AuthMiddleware::class);

$router->post('/comment/{imageId}', [CommentController::class, 'create'], AuthMiddleware::class);
$router->get('/comment/{imageId}', [CommentController::class, 'getByImage']);

$router->dispatch();
