<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\InvoiceBulkService;

/**
 * InvoiceBulkController (Admin)
 *
 * Dedicated controller for handling bulk invoice template download,
 * CSV upload, row validation preview, atomic batch generation, and summary reporting.
 */
class InvoiceBulkController
{
    private InvoiceBulkService $bulkService;

    public function __construct(InvoiceBulkService $bulkService)
    {
        $this->bulkService = $bulkService;
    }

    /**
     * Download the standard CSV template.
     * GET /admin/invoices/bulk_template.php
     */
    public function downloadTemplate(): void
    {
        $this->bulkService->downloadTemplate();
    }

    /**
     * Show bulk upload page with instructions and file dropzone.
     * GET /admin/invoices/bulk_upload.php
     *
     * @param string|null $error
     */
    public function showUploadForm(?string $error = null): void
    {
        $pageTitle  = 'Bulk Invoice Upload';
        $activeMenu = 'invoice_bulk';
        $errorMsg   = $error;

        require __DIR__ . '/../../../views/admin/invoices/bulk_upload.php';
    }

    /**
     * Handle CSV file upload and staging.
     * POST /admin/invoices/bulk_upload.php
     *
     * @param int $adminId
     */
    public function upload(int $adminId): void
    {
        $file = $_FILES['csv_file'] ?? [];
        $result = $this->bulkService->processUpload($file, $adminId);

        if ($result['success']) {
            header('Location: /admin/invoices/bulk_preview.php?batch=' . urlencode($result['batch_uuid']));
            exit;
        }

        $this->showUploadForm($result['error'] ?? 'Upload failed.');
    }

    /**
     * Display validation preview table for an uploaded batch.
     * GET /admin/invoices/bulk_preview.php?batch={uuid}&filter={all|valid|invalid}&page={int}
     */
    public function preview(): void
    {
        $batchUuid = trim($_GET['batch'] ?? '');
        if ($batchUuid === '') {
            $this->send404('Batch token is required.');
        }

        $filter = $_GET['filter'] ?? 'all';
        $filterValid = match ($filter) {
            'valid'   => true,
            'invalid' => false,
            default   => null,
        };

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $previewData = $this->bulkService->getBatchPreview($batchUuid, $filterValid, $page, 50);

        if ($previewData === null) {
            $this->send404('Batch not found or expired.');
        }

        $batch       = $previewData['batch'];
        $rows        = $previewData['rows'];
        $totalStaged = $previewData['total_staged'];
        $pages       = $previewData['pages'];
        $currentPage = $previewData['current_page'];
        $activeFilter = $filter;

        $pageTitle  = 'Bulk Invoices Preview — ' . htmlspecialchars($batch['original_name'], ENT_QUOTES, 'UTF-8');
        $activeMenu = 'invoice_bulk';

        require __DIR__ . '/../../../views/admin/invoices/bulk_preview.php';
    }

    /**
     * Trigger invoice generation for all valid records in a batch.
     * POST /admin/invoices/bulk_generate.php
     *
     * @param int $adminId
     */
    public function generate(int $adminId): void
    {
        $batchUuid = trim($_POST['batch_uuid'] ?? '');
        if ($batchUuid === '') {
            $this->send404('Invalid batch token.');
        }

        $result = $this->bulkService->executeBatchGeneration($batchUuid, $adminId);

        if ($result['success']) {
            header('Location: /admin/invoices/bulk_summary.php?batch=' . urlencode($batchUuid));
            exit;
        }

        // On failure redirect back to preview with error
        header('Location: /admin/invoices/bulk_preview.php?batch=' . urlencode($batchUuid) . '&error=' . urlencode($result['message']));
        exit;
    }

    /**
     * Show post-generation summary report.
     * GET /admin/invoices/bulk_summary.php?batch={uuid}
     */
    public function summary(): void
    {
        $batchUuid = trim($_GET['batch'] ?? '');
        if ($batchUuid === '') {
            $this->send404('Batch token is required.');
        }

        $summaryData = $this->bulkService->getBatchSummary($batchUuid);
        if ($summaryData === null) {
            $this->send404('Batch not found.');
        }

        $batch             = $summaryData['batch'];
        $generatedInvoices = $summaryData['generated_invoices'];
        $errorRows         = $summaryData['error_rows'];

        $pageTitle  = 'Bulk Generation Summary Report';
        $activeMenu = 'invoice_bulk';

        require __DIR__ . '/../../../views/admin/invoices/bulk_summary.php';
    }

    /**
     * Download failed records with their error reasons as CSV.
     * GET /admin/invoices/bulk_errors.php?batch={uuid}
     */
    public function downloadErrors(): void
    {
        $batchUuid = trim($_GET['batch'] ?? '');
        if ($batchUuid === '') {
            $this->send404('Batch token is required.');
        }

        $this->bulkService->exportErrorsCsv($batchUuid);
    }

    // ===========================================================
    // PRIVATE HELPERS
    // ===========================================================

    private function send404(string $message): never
    {
        http_response_code(404);
        echo '<h1>404 — ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</h1>';
        exit;
    }
}

