<?php
/**
 * Public entry point: Send Invoice PDF via Email (POST only)
 * Accessible by ROLE_ADMIN with 'invoices.send_email' or 'invoices.view' permission
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
require_once __DIR__ . '/../../../src/Repositories/InvoiceEmailLogRepository.php';
require_once __DIR__ . '/../../../src/Contracts/MailerInterface.php';
require_once __DIR__ . '/../../../src/Contracts/AttachmentMailerInterface.php';
require_once __DIR__ . '/../../../src/Contracts/InvoicePdfServiceInterface.php';
require_once __DIR__ . '/../../../src/Services/Mail/PhpMailerService.php';
require_once __DIR__ . '/../../../src/Services/InvoicePdfService.php';
require_once __DIR__ . '/../../../src/Services/InvoiceEmailService.php';
require_once __DIR__ . '/../../../src/Services/InvoiceService.php';
require_once __DIR__ . '/../../../src/Controllers/Admin/InvoiceController.php';

requireRole(ROLE_ADMIN);

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// CSRF validation
$csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!validateCsrfToken($csrfToken)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid or expired CSRF token. Please refresh the page.']);
    exit;
}

// Permission check
if (!isSuperAdmin() && !hasPermission('invoices.send_email') && !hasPermission('invoices.view')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access Denied: You do not have permission to send invoice emails.']);
    exit;
}

$adminId    = (int) $auth->getUserId();
$repository = new \App\Repositories\InvoiceRepository($db);
$service    = new \App\Services\InvoiceService($repository);
$controller = new \App\Controllers\Admin\InvoiceController($service);

$controller->sendEmail($adminId);

