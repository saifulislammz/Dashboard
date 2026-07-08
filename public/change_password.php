<?php
require_once __DIR__ . '/../src/config/security.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';

requireLogin();

$authService = new \App\Services\AuthService($auth);
$controller  = new \App\Controllers\AuthController($authService);

$controller->changePassword();
