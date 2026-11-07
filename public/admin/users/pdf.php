<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/PermissionMiddleware.php';
require_once __DIR__ . '/../../../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../../../src/Repositories/UserProfileRepository.php';
require_once __DIR__ . '/../../../src/Repositories/ClassroomRepository.php';
require_once __DIR__ . '/../../../src/Repositories/InvoiceRepository.php';
require_once __DIR__ . '/../../../src/Services/UserProfileService.php';
require_once __DIR__ . '/../../../src/Services/UserProfilePdfService.php';
require_once __DIR__ . '/../../../src/Controllers/Admin/AdminUserProfileController.php';

requireRole(ROLE_ADMIN);
requirePermission('users.view');

$userRepo      = new \App\Repositories\UserRepository($db);
$profileRepo   = new \App\Repositories\UserProfileRepository($db);
$classroomRepo = new \App\Repositories\ClassroomRepository($db);
$invoiceRepo   = new \App\Repositories\InvoiceRepository($db);

$profileService = new \App\Services\UserProfileService($userRepo, $profileRepo, $classroomRepo, $invoiceRepo, $auth);
$pdfService     = new \App\Services\UserProfilePdfService();

$controller = new \App\Controllers\Admin\AdminUserProfileController(
    $profileService,
    $pdfService,
    $invoiceRepo,
    $auth
);

$userId = (int) ($_GET['id'] ?? 0);
$controller->downloadPdf($userId);

