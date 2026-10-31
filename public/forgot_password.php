<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/config/security.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/middleware/CsrfMiddleware.php';

// Redirect to dashboard if already logged in
redirectIfLoggedIn();

$mailer     = new \App\Services\Mail\PhpMailerService();
$repository = new \App\Repositories\PasswordResetRepository($db);
$service    = new \App\Services\PasswordResetService($auth, $repository, $mailer);
$controller = new \App\Controllers\Auth\ForgotPasswordController($service);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->handleRequest();
} else {
    $controller->showForm();
}

