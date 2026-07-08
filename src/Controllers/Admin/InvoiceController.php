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

        $pageTitle  = 'Invoice Dashboard';
        $activeMenu = 'invoice_dashboard';

        require __DIR__ . '/../../../views/admin/invoices/index.php';
    }

    // ===========================================================
    // CREATE — Invoice Generator Form
    // ===========================================================

    /**
     * Show invoice creation form.
     * GET /admin/invoices/create.php
     */
    public function create(): void
    {
        $settings     = $this->invoiceService->getSettings();
        $invoiceNumber = $this->invoiceService->generateInvoiceNumberPreview($settings);

        $pageTitle  = 'Generate Invoice';
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
        $old           = $_POST; // repopulate form

        $pageTitle  = 'Generate Invoice';
        $activeMenu = 'invoice_create';

        require __DIR__ . '/../../../views/admin/invoices/create.php';
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
        $created    = isset($_GET['created']) && $_GET['created'] === '1';

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
    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->sendJsonError('Invalid invoice ID.');
        }

        $ok = $this->invoiceService->deleteInvoice($id);

        if ($ok) {
            header('Location: /admin/invoices/index.php?deleted=1');
            exit;
        }

        header('Location: /admin/invoices/index.php?error=delete_failed');
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->invoiceService->saveSettings($_POST);

            if ($result['success']) {
                $settings = $this->invoiceService->getSettings(); // reload fresh
                $success  = true;
            } else {
                $errors = $result['errors'];
            }
        }

        // Live preview of invoice number
        $numberPreview = $this->invoiceService->generateInvoiceNumberPreview($settings);

        $pageTitle  = 'Invoice Settings';
        $activeMenu = 'invoice_settings';

        require __DIR__ . '/../../../views/admin/invoices/settings.php';
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
