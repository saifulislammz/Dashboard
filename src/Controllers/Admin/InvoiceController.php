<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\InvoiceService;

/**
 * InvoiceController (Admin)
 *
 * Thin controller: validates HTTP request → calls InvoiceService → renders view.
 * No business logic or SQL here.
 */
class InvoiceController
{
    private InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    // ===========================================================
    // INDEX — Invoice Dashboard
    // ===========================================================

    /**
     * Render the invoice list/dashboard page.
     * GET /admin/invoices/index.php
     */
    public function index(): void
    {
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'search'    => $_GET['search']    ?? '',
            'status'    => $_GET['status']    ?? '',
            'currency'  => $_GET['currency']  ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to']   ?? '',
        ];

        $listData  = $this->invoiceService->getInvoiceList($filters, $page);
        $stats     = $this->invoiceService->getDashboardStats();
        $currencies = $this->invoiceService->getCurrencies();

        $pageTitle  = 'Invoice Dashboard';
        $activeMenu = 'invoice_dashboard';

        require __DIR__ . '/../../../views/admin/invoices/index.php';
    }

    // ===========================================================
    // CREATE — Invoice Generator Form
    // ===========================================================

    /**
     * Show invoice creation form.
     * Supports duplicating/cloning an existing invoice via ?duplicate_from={id}
     * GET /admin/invoices/create.php
     */
    public function create(): void
    {
        $settings             = $this->invoiceService->getSettings();
        $invoiceNumber        = $this->invoiceService->generateInvoiceNumberPreview($settings);
        $currencies           = $this->invoiceService->getCurrencies();
        $old                  = [];
        $duplicateFromInvoice = null;

        $duplicateFromId = isset($_GET['duplicate_from']) ? (int) $_GET['duplicate_from'] : 0;
        if ($duplicateFromId > 0) {
            $sourceData = $this->invoiceService->getInvoiceById($duplicateFromId);
            if ($sourceData !== null && !empty($sourceData['invoice'])) {
                $duplicateFromInvoice = $sourceData['invoice'];
                $old = [
                    'student_name'    => $sourceData['invoice']['student_name'] ?? '',
                    'student_email'   => $sourceData['invoice']['student_email'] ?? '',
                    'student_phone'   => $sourceData['invoice']['student_phone'] ?? '',
                    'student_country' => $sourceData['invoice']['student_country'] ?? '',
                    'currency'        => $sourceData['invoice']['currency'] ?? 'BDT',
                    'discount'        => $sourceData['invoice']['discount'] ?? 0,
                    'vat_percent'     => $sourceData['invoice']['vat_percent'] ?? 0,
                    'status'          => 'unpaid',
                    'invoice_date'    => date('Y-m-d'),
                    'due_date'        => '',
                    'notes'           => $sourceData['invoice']['notes'] ?? '',
                    'show_signature'  => $sourceData['invoice']['show_signature'] ?? 1,
                    'amount_paid'     => '0.00',
                    'items'           => [],
                ];

                if (!empty($sourceData['items']) && is_array($sourceData['items'])) {
                    foreach ($sourceData['items'] as $item) {
                        $old['items'][] = [
                            'item_name'   => $item['item_name'] ?? '',
                            'description' => $item['description'] ?? '',
                            'quantity'    => $item['quantity'] ?? 1,
                            'unit_price'  => $item['unit_price'] ?? 0,
                        ];
                    }
                }
            }
        }

        $pageTitle  = $duplicateFromInvoice ? 'Duplicate Invoice #' . htmlspecialchars($duplicateFromInvoice['invoice_number'], ENT_QUOTES, 'UTF-8') : 'Generate Invoice';
        $activeMenu = 'invoice_create';

        require __DIR__ . '/../../../views/admin/invoices/create.php';
    }

    /**
     * Process invoice creation form submission.
     * POST /admin/invoices/create.php
     */
    public function store(int $adminId): void
    {
        $result = $this->invoiceService->createInvoice($_POST, $adminId);

        if ($result['success']) {
            // Redirect to view page on success
            header('Location: /admin/invoices/view.php?id=' . $result['invoice_id'] . '&created=1');
            exit;
        }

        // Re-render form with errors
        $errors        = $result['errors'];
        $settings      = $this->invoiceService->getSettings();
        $invoiceNumber = $this->invoiceService->generateInvoiceNumberPreview($settings);
        $currencies    = $this->invoiceService->getCurrencies();
        $old           = $_POST; // repopulate form

        $pageTitle  = 'Generate Invoice';
        $activeMenu = 'invoice_create';

        require __DIR__ . '/../../../views/admin/invoices/create.php';
    }

    // ===========================================================
    // EDIT — Invoice Edit Form
    // ===========================================================

    /**
     * Show invoice edit form.
     * GET /admin/invoices/edit.php?id=
     */
    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->send404('Invalid invoice ID.');
        }

        $data = $this->invoiceService->getInvoiceById($id);
        if ($data === null) {
            $this->send404('Invoice not found.');
        }

        $invoice    = $data['invoice'];
        $items      = $data['items'];
        $settings   = $this->invoiceService->getSettings();
        $currencies = $this->invoiceService->getCurrencies();
        $errors     = [];

        $pageTitle  = 'Edit Invoice #' . htmlspecialchars($invoice['invoice_number'], ENT_QUOTES, 'UTF-8');
        $activeMenu = 'invoice_dashboard';

        require __DIR__ . '/../../../views/admin/invoices/edit.php';
    }

    /**
     * Process invoice update form submission.
     * POST /admin/invoices/edit.php?id=
     */
    public function update(?int $adminId = null): void
    {
        $id = (int) ($_POST['id'] ?? ($_GET['id'] ?? 0));
        if ($id <= 0) {
            $this->send404('Invalid invoice ID.');
        }

        $data = $this->invoiceService->getInvoiceById($id);
        if ($data === null) {
            $this->send404('Invoice not found.');
        }

        $result = $this->invoiceService->updateInvoice($id, $_POST, $adminId);

        if ($result['success']) {
            header('Location: /admin/invoices/view.php?id=' . $id . '&updated=1');
            exit;
        }

        // Re-render edit form with errors and submitted input
        $errors        = $result['errors'];
        $settings      = $this->invoiceService->getSettings();
        $currencies    = $this->invoiceService->getCurrencies();
        
        // Merge submitted data into invoice and items for repopulation
        $invoice = array_merge($data['invoice'], $_POST);
        $items   = isset($_POST['items']) && is_array($_POST['items']) ? $_POST['items'] : $data['items'];

        $pageTitle  = 'Edit Invoice #' . htmlspecialchars($data['invoice']['invoice_number'], ENT_QUOTES, 'UTF-8');
        $activeMenu = 'invoice_dashboard';

        require __DIR__ . '/../../../views/admin/invoices/edit.php';
    }

    // ===========================================================
    // VIEW — Single Invoice Detail
    // ===========================================================

    /**
     * Render a single invoice detail page.
     * GET /admin/invoices/view.php?id=
     */
    public function view(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->send404('Invalid invoice ID.');
        }

        $data = $this->invoiceService->getInvoiceById($id);
        if ($data === null) {
            $this->send404('Invoice not found.');
        }

        $invoice    = $data['invoice'];
        $items      = $data['items'];
        $settings   = $this->invoiceService->getSettings();
        $currencies = $this->invoiceService->getCurrencies();
        $created       = isset($_GET['created']) && $_GET['created'] === '1';
        $statusUpdated = isset($_GET['status_updated']) && $_GET['status_updated'] === '1';
        $updated       = isset($_GET['updated']) && $_GET['updated'] === '1';

        $pageTitle  = 'Invoice #' . htmlspecialchars($invoice['invoice_number'], ENT_QUOTES, 'UTF-8');
        $activeMenu = 'invoice_dashboard';

        require __DIR__ . '/../../../views/admin/invoices/view.php';
    }

    // ===========================================================
    // PRINT — Standalone Printable Invoice
    // ===========================================================

    /**
     * Render the standalone print-ready invoice (no sidebar, auto window.print()).
     * GET /admin/invoices/print.php?id=
     */
    public function printInvoice(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->send404('Invalid invoice ID.');
        }

        $data = $this->invoiceService->getInvoiceById($id);
        if ($data === null) {
            $this->send404('Invoice not found.');
        }

        $invoice    = $data['invoice'];
        $items      = $data['items'];
        $settings   = $this->invoiceService->getSettings();
        $currencies = $this->invoiceService->getCurrencies();

        // No sidebar/header — standalone page
        require __DIR__ . '/../../../views/admin/invoices/print.php';
    }

    // ===========================================================
    // DELETE — Soft Delete
    // ===========================================================

    /**
     * Soft-delete an invoice.
     * POST /admin/invoices/delete.php
     */
    public function delete(?int $adminId = null): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->sendJsonError('Invalid invoice ID.');
        }

        $ok = $this->invoiceService->deleteInvoice($id, $adminId);

        if ($ok) {
            header('Location: /admin/invoices/index.php?deleted=1');
            exit;
        }

        header('Location: /admin/invoices/index.php?error=delete_failed');
        exit;
    }

    // ===========================================================
    // UPDATE STATUS
    // ===========================================================

    /**
     * Update invoice status.
     * POST /admin/invoices/update_status.php
     */
    public function updateStatus(?int $adminId = null): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if ($id <= 0) {
            $this->sendJsonError('Invalid invoice ID.');
        }

        $result = $this->invoiceService->updateStatus($id, $status, $adminId);

        if ($result['success']) {
            header('Location: /admin/invoices/view.php?id=' . $id . '&status_updated=1');
            exit;
        }

        header('Location: /admin/invoices/view.php?id=' . $id . '&error=status_update_failed');
        exit;
    }

    // ===========================================================
    // SETTINGS
    // ===========================================================

    /**
     * Show and process invoice settings.
     * GET/POST /admin/invoices/settings.php
     */
    public function settings(): void
    {
        $settings  = $this->invoiceService->getSettings();
        $success   = false;
        $errors    = [];
        $action    = $_POST['action'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'add_currency') {
                $result = $this->invoiceService->addCurrency($_POST);
                if ($result['success']) {
                    header('Location: /admin/invoices/settings.php?currency_added=1');
                    exit;
                }
                $errors = $result['errors'];
            } elseif ($action === 'delete_currency') {
                $this->invoiceService->deleteCurrency($_POST['code'] ?? '');
                header('Location: /admin/invoices/settings.php?currency_deleted=1');
                exit;
            } else {
                $result = $this->invoiceService->saveSettings(
                    $_POST,
                    $_FILES['institution_logo'] ?? null,
                    $_FILES['institution_signature'] ?? null
                );

                if ($result['success']) {
                    header('Location: /admin/invoices/settings.php?saved=1');
                    exit;
                } else {
                    $errors = $result['errors'];
                    // Merge POST data into settings so user doesn't lose input
                    foreach ($_POST as $k => $v) {
                        $settings[$k] = $v;
                    }
                }
            }
        }

        $currencies = $this->invoiceService->getCurrencies();

        // Live preview of invoice number
        $numberPreview = $this->invoiceService->generateInvoiceNumberPreview($settings);

        $pageTitle  = 'Invoice Settings';
        $activeMenu = 'invoice_settings';

        require __DIR__ . '/../../../views/admin/invoices/settings.php';
    }

    // ===========================================================
    // SEND EMAIL — Manual Invoice PDF Email Dispatch
    // ===========================================================

    /**
     * Send invoice PDF to student/client via email.
     * POST /admin/invoices/send_email.php
     *
     * @param int|null $adminId Logged-in admin ID
     */
    public function sendEmail(?int $adminId = null): void
    {
        $id        = (int) ($_POST['id'] ?? ($_POST['invoice_id'] ?? 0));
        $recipient = !empty($_POST['recipient_email']) ? trim($_POST['recipient_email']) : null;

        if ($id <= 0) {
            $this->sendJsonError('Invalid invoice ID.');
        }

        $emailService = $this->invoiceService->getEmailService();
        $result = $emailService->sendInvoiceEmail($id, $recipient, $adminId, 'manual');

        header('Content-Type: application/json');
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => $result['message'],
            ]);
            exit;
        }

        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $result['message'],
            'errors'  => $result['errors'] ?? [],
        ]);
        exit;
    }

    // ===========================================================
    // PRIVATE HELPERS
    // ===========================================================

    /**
     * Send a 404 response and terminate.
     *
     * @param string $message
     */
    private function send404(string $message): never
    {
        http_response_code(404);
        echo '<h1>404 — ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</h1>';
        exit;
    }

    /**
     * Send JSON error response and terminate.
     *
     * @param string $message
     */
    private function sendJsonError(string $message): never
    {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}
