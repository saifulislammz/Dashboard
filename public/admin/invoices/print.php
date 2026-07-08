<?php
/**
 * Public entry point: Print/Download Invoice (standalone, no sidebar)
 * Accessible only by ROLE_ADMIN — protected even though it appears in a new tab.
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

$controller->printInvoice();

