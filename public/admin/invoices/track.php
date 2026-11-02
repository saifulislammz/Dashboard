<?php
/**
 * Public entry point: Super Admin Invoice Tracking & Audit Dashboard
 * Accessible exclusively by Super Administrators (isSuperAdmin())
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/PermissionMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../../src/Repositories/InvoiceRepository.php';
require_once __DIR__ . '/../../../src/Repositories/InvoiceActivityRepository.php';
require_once __DIR__ . '/../../../src/Services/InvoiceTrackService.php';
require_once __DIR__ . '/../../../src/Controllers/Admin/InvoiceTrackController.php';

requireRole(ROLE_ADMIN);
requireSuperAdmin();

$invoiceRepo  = new \App\Repositories\InvoiceRepository($db);
$activityRepo = new \App\Repositories\InvoiceActivityRepository($db);
$trackService = new \App\Services\InvoiceTrackService($invoiceRepo, $activityRepo);
$controller   = new \App\Controllers\Admin\InvoiceTrackController($trackService, $auth);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    validateCsrfToken($_POST['csrf_token'] ?? '');

    if ($action === 'restore') {
        $controller->restore();
    } else {
        http_response_code(400);
        header('Location: /admin/invoices/track.php?error=invalid_action');
        exit;
    }
} else {
    $controller->index();
}

