<?php
/**
 * Public entry point: Invoice Settings
 * Accessible only by ROLE_ADMIN
 * Handles both GET (show form) and POST (save settings).
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');
}

$repository = new \App\Repositories\InvoiceRepository($db);
$service    = new \App\Services\InvoiceService($repository);
$controller = new \App\Controllers\Admin\InvoiceController($service);

$controller->settings();
