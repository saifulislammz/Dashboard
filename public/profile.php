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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload_picture') {
        $controller->updatePicture();
    } elseif ($action === 'remove_picture') {
        $controller->removePicture();
    } else {
        header("Location: profile.php");
        exit();
    }
}

$controller->index();
