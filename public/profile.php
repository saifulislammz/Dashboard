<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/config/security.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/config/roles.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Repositories/UserProfileRepository.php';
require_once __DIR__ . '/../src/Repositories/ClassroomRepository.php';
require_once __DIR__ . '/../src/Repositories/InvoiceRepository.php';
require_once __DIR__ . '/../src/Services/UserService.php';
require_once __DIR__ . '/../src/Services/UserProfileService.php';
require_once __DIR__ . '/../src/Controllers/ProfileController.php';

requireLogin();

$userRepo      = new \App\Repositories\UserRepository($db);
$profileRepo   = new \App\Repositories\UserProfileRepository($db);
$classroomRepo = new \App\Repositories\ClassroomRepository($db);
$invoiceRepo   = new \App\Repositories\InvoiceRepository($db);

$userService    = new \App\Services\UserService($userRepo, $auth);
$profileService = new \App\Services\UserProfileService($userRepo, $profileRepo, $classroomRepo, $invoiceRepo, $auth);

$controller = new \App\Controllers\ProfileController($userService, $profileService, $auth);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $controller->updateProfile();
    } elseif ($action === 'upload_picture') {
        $controller->updatePicture();
    } elseif ($action === 'remove_picture') {
        $controller->removePicture();
    } else {
        header("Location: profile.php");
        exit();
    }
}

$controller->index();
