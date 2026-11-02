<?php
/**
 * Public entry point: Bulk Generation Summary Report
 * Accessible by ROLE_ADMIN with 'invoices.create' permission
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/PermissionMiddleware.php';

requireRole(ROLE_ADMIN);
requirePermission('invoices.create');

require_once __DIR__ . '/../../../src/Repositories/InvoiceRepository.php';
require_once __DIR__ . '/../../../src/Repositories/InvoiceBulkRepository.php';
require_once __DIR__ . '/../../../src/Repositories/InvoiceActivityRepository.php';
require_once __DIR__ . '/../../../src/Services/InvoiceService.php';
require_once __DIR__ . '/../../../src/Services/InvoiceBulkService.php';
require_once __DIR__ . '/../../../src/Controllers/Admin/InvoiceBulkController.php';

$invoiceRepo = new \App\Repositories\InvoiceRepository($db);
$bulkRepo    = new \App\Repositories\InvoiceBulkRepository($db);
$actRepo     = new \App\Repositories\InvoiceActivityRepository($db);
$invService  = new \App\Services\InvoiceService($invoiceRepo, $actRepo);
$bulkService = new \App\Services\InvoiceBulkService($bulkRepo, $invoiceRepo, $invService, $actRepo);
$controller  = new \App\Controllers\Admin\InvoiceBulkController($bulkService);

$controller->summary();

