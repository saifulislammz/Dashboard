<?php
require_once __DIR__ . '/../src/config/security.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/config/roles.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Services/UserService.php';
require_once __DIR__ . '/../src/Controllers/ProfileController.php';

requireLogin();

$repository = new \App\Repositories\UserRepository($db);
$service    = new \App\Services\UserService($repository, $auth);
$controller = new \App\Controllers\ProfileController($service, $auth);

$controller->index();
