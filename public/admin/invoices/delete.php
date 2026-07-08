<?php
/**
 * Public entry point: Delete Invoice (POST only, soft delete)
 * Accessible only by ROLE_ADMIN
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

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: /admin/invoices/index.php');
    exit;
}

validateCsrfToken($_POST['csrf_token'] ?? '');

$repository = new \App\Repositories\InvoiceRepository($db);
$service    = new \App\Services\InvoiceService($repository);
$controller = new \App\Controllers\Admin\InvoiceController($service);

$controller->delete();

