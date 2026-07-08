<?php
/**
 * Public entry point: Create / Generate Invoice
 * Accessible only by ROLE_ADMIN
 * Handles both GET (show form) and POST (process form).
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../../src/Repositories/InvoiceRepository.php';
require_once __DIR__ . '/../../../src/Services/InvoiceService.php';
require_once __DIR__ . '/../../../src/Controllers/Admin/InvoiceController.php';

requireRole(ROLE_ADMIN);

$repository = new \App\Repositories\InvoiceRepository($db);
$service    = new \App\Services\InvoiceService($repository);
$controller = new \App\Controllers\Admin\InvoiceController($service);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');
    $adminId = (int) $auth->getUserId();
    $controller->store($adminId);
} else {
    $controller->create();
}

