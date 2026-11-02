<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\InvoiceBulkRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\InvoiceActivityRepository;
use RuntimeException;
use Throwable;

/**
 * InvoiceBulkService
 *
 * Encapsulates all business logic for bulk invoice upload, template generation,
 * memory-safe CSV streaming, multi-stage row validation, atomic batch generation,
 * and error report compilation.
 */
class InvoiceBulkService
{
    private InvoiceBulkRepository $bulkRepo;
    private InvoiceRepository $invoiceRepo;
    private InvoiceService $invoiceService;
    private ?InvoiceActivityRepository $activityRepo;

    public function __construct(
        InvoiceBulkRepository $bulkRepo,
        InvoiceRepository $invoiceRepo,
        InvoiceService $invoiceService,
        ?InvoiceActivityRepository $activityRepo = null
    ) {
        $this->bulkRepo       = $bulkRepo;
        $this->invoiceRepo     = $invoiceRepo;
        $this->invoiceService  = $invoiceService;
        $this->activityRepo    = $activityRepo;
    }

    // ===========================================================
    // TEMPLATE GENERATION
    // ===========================================================

    /**
     * Stream standard CSV template with UTF-8 BOM for Microsoft Excel compatibility.
     * Includes sample header and instructive dummy records.
     */
    public function downloadTemplate(): void
    {
        $filename = 'invoice_bulk_template_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // UTF-8 BOM for Excel unicode rendering
        fwrite($out, "\xEF\xBB\xBF");

        $headers = [
            'student_name',
            'student_email',
            'student_phone',
            'student_country',
            'currency',
            'invoice_date',
            'due_date',
            'item_name',
            'item_description',
            'quantity',
            'unit_price',
            'discount',
            'vat_percent',
            'amount_paid',
            'notes',
            'invoice_group_key',
        ];
        fputcsv($out, $headers);

        // Instructive sample row 1: Single item invoice
        fputcsv($out, [
            'Abdullah Al Mamun',
            'student1@example.com',
            '+8801712345678',
            'Bangladesh',
            'BDT',
            date('Y-m-d'),
            date('Y-m-d', strtotime('+15 days')),
            'Monthly Quran Class Fee',
            'Regular Session Fee for the current month',
            '1',
            '1500.00',
            '0.00',
            '0.00',
            '0.00',
            'Payable before the due date',
            '',
        ]);

        // Instructive sample row 2: Multi-item invoice demonstration (item A)
        fputcsv($out, [
            'Fatima Begum',
            'fatima@example.com',
            '+8801812345679',
            'Bangladesh',
            'BDT',
            date('Y-m-d'),
            date('Y-m-d', strtotime('+10 days')),
            'Course Registration Fee',
            'Arabic Fluency Mastery',
            '1',
            '2000.00',
            '100.00',
            '5.00',
            '500.00',
            'Initial partial payment received',
            'GRP-002',
        ]);

        // Instructive sample row 3: Multi-item invoice demonstration (item B, same group key)
        fputcsv($out, [
            'Fatima Begum',
            'fatima@example.com',
            '+8801812345679',
            'Bangladesh',
            'BDT',
            date('Y-m-d'),
            date('Y-m-d', strtotime('+10 days')),
            'Study Materials & Books',
            'Physical delivery package',
            '1',
            '500.00',
            '0.00',
            '5.00',
            '0.00',
            '',
            'GRP-002',
        ]);

        fclose($out);
        exit;
    }

    // ===========================================================
    // UPLOAD & STREAMING VALIDATION
    // ===========================================================

    /**
     * Process an uploaded CSV file, validate all rows, and stage the batch.
     *
     * @param array $file    $_FILES['csv_file']
     * @param int   $adminId
     * @return array ['success' => bool, 'batch_uuid' => string|null, 'error' => string|null]
     */
    public function processUpload(array $file, int $adminId): array
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            return ['success' => false, 'error' => 'No file uploaded or upload error occurred.'];
        }

        // Limit file size to 5MB
        $maxSizeBytes = 5 * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxSizeBytes) {
            return ['success' => false, 'error' => 'File size exceeds 5MB limit.'];
        }

        // Validate extension
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            return ['success' => false, 'error' => 'Invalid file format. Please upload a standard .csv file.'];
        }

        // MIME validation
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $allowedMimes = [
            'text/plain',
            'text/csv',
            'text/x-csv',
            'application/vnd.ms-excel',
            'application/csv',
            'text/comma-separated-values',
        ];

        if (!in_array($mime, $allowedMimes, true)) {
            // Some Windows browsers upload CSV as text/plain or octet-stream
            if ($mime !== 'application/octet-stream') {
                return ['success' => false, 'error' => 'Security check failed: File is not a valid CSV text document.'];
            }
        }

        // Open and stream CSV line-by-line
        $handle = @fopen($file['tmp_name'], 'r');
        if (!$handle) {
            return ['success' => false, 'error' => 'Unable to read uploaded file.'];
        }

        // Read header row and handle UTF-8 BOM
        $rawHeaders = fgetcsv($handle, 4096, ',');
        if (!$rawHeaders || empty($rawHeaders)) {
            fclose($handle);
            return ['success' => false, 'error' => 'Uploaded CSV file is empty.'];
        }

        // Strip UTF-8 BOM if present on the first header
        if (isset($rawHeaders[0])) {
            $rawHeaders[0] = preg_replace('/^\xEF\xBB\xBF/', '', $rawHeaders[0]);
        }

        $headers = array_map(fn($h) => strtolower(trim((string)$h)), $rawHeaders);

        // Required headers checklist
        $requiredHeaders = ['student_name', 'item_name', 'unit_price'];
        foreach ($requiredHeaders as $req) {
            if (!in_array($req, $headers, true)) {
                fclose($handle);
                return [
                    'success' => false,
                    'error'   => "Required column header '{$req}' is missing. Please use the official template.",
                ];
            }
        }

        $currencies = $this->invoiceService->getCurrencies();
        $allowedCurrencies = array_keys($currencies);

        $stagedRows  = [];
        $validCount  = 0;
        $invalidCount = 0;
        $rowNumber   = 1; // 1 was header

        while (($row = fgetcsv($handle, 4096, ',')) !== false) {
            $rowNumber++;

            // Skip completely empty lines
            if (empty(array_filter($row, fn($v) => trim((string)$v) !== ''))) {
                continue;
            }

            // Combine headers with row values
            $assoc = [];
            foreach ($headers as $index => $headerKey) {
                $assoc[$headerKey] = isset($row[$index]) ? trim((string)$row[$index]) : '';
            }

            // Perform row-level validation
            $validation = $this->validateRow($assoc, $allowedCurrencies);

            if ($validation['is_valid']) {
                $validCount++;
            } else {
                $invalidCount++;
            }

            $stagedRows[] = [
                'row_number'       => $rowNumber,
                'raw_data'         => $assoc,
                'parsed_data'      => $validation['parsed_data'],
                'is_valid'         => $validation['is_valid'] ? 1 : 0,
                'validation_error' => $validation['error_message'],
            ];

            // Hard safety limit: 1000 rows max per batch
            if ($rowNumber > 1005) {
                break;
            }
        }

        fclose($handle);

        $totalRows = count($stagedRows);
        if ($totalRows === 0) {
            return ['success' => false, 'error' => 'The uploaded CSV file contains no data rows.'];
        }

        // Store batch in database via repository
        try {
            $batchData = [
                'admin_id'      => $adminId,
                'original_name' => $file['name'] ?? 'upload.csv',
                'total_rows'    => $totalRows,
                'valid_rows'    => $validCount,
                'invalid_rows'  => $invalidCount,
            ];

            $batchUuid = $this->bulkRepo->createBatch($batchData, $stagedRows);

            return [
                'success'      => true,
                'batch_uuid'   => $batchUuid,
                'total_rows'   => $totalRows,
                'valid_rows'   => $validCount,
                'invalid_rows' => $invalidCount,
                'error'        => null,
            ];
        } catch (\Throwable $e) {
            error_log('[InvoiceBulkService::processUpload] Staging failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Database error staging bulk file: ' . $e->getMessage()];
        }
    }

    // ===========================================================
    // PREVIEW & METRICS
    // ===========================================================

    /**
     * Get preview data for a staged batch.
     *
     * @param string    $batchUuid
     * @param bool|null $filterValid (null = all, true = valid, false = invalid)
     * @param int       $page
     * @param int       $perPage
     * @return array|null
     */
    public function getBatchPreview(string $batchUuid, ?bool $filterValid = null, int $page = 1, int $perPage = 50): ?array
    {
        $batch = $this->bulkRepo->findBatchByUuid($batchUuid);
        if ($batch === null) {
            return null;
        }

        $batchId = (int) $batch['id'];
        $totalStaged = $this->bulkRepo->countStagedRows($batchId, $filterValid);
        $offset = max(0, ($page - 1) * $perPage);

        $rows = $this->bulkRepo->getStagedRows($batchId, $filterValid, $perPage, $offset);

        // Decode JSON fields for view convenience
        $decodedRows = [];
        foreach ($rows as $row) {
            $row['raw']    = json_decode($row['raw_data_json'] ?? '{}', true) ?: [];
            $row['parsed'] = json_decode($row['parsed_data_json'] ?? '{}', true) ?: [];
            $decodedRows[] = $row;
        }

        return [
            'batch'        => $batch,
            'rows'         => $decodedRows,
            'total_staged' => $totalStaged,
            'current_page' => $page,
            'per_page'     => $perPage,
            'pages'        => max(1, (int) ceil($totalStaged / $perPage)),
            'filter_valid' => $filterValid,
        ];
    }

    // ===========================================================
    // ATOMIC BATCH GENERATION
    // ===========================================================

    /**
     * Execute bulk generation for all valid records in a batch.
     * Groups by invoice_group_key (or treats single rows as separate invoices).
     * Employs per-invoice atomic transactions with collision retry protection.
     *
     * @param string $batchUuid
     * @param int    $adminId
     * @return array ['success' => bool, 'message' => string, 'generated' => int, 'failed' => int]
     */
    public function executeBatchGeneration(string $batchUuid, int $adminId): array
    {
        $batch = $this->bulkRepo->findBatchByUuid($batchUuid);
        if ($batch === null) {
            return ['success' => false, 'message' => 'Batch not found.'];
        }

        if ($batch['status'] === 'generating') {
            return ['success' => false, 'message' => 'This batch is currently being processed by another request.'];
        }

        if ($batch['status'] === 'completed' || $batch['status'] === 'completed_with_errors') {
            return ['success' => false, 'message' => 'This batch has already been generated.'];
        }

        $batchId = (int) $batch['id'];
        $this->bulkRepo->updateBatchStatus($batchId, 'generating');

        $validRows = $this->bulkRepo->getValidRowsForGeneration($batchId);
        if (empty($validRows)) {
            $this->bulkRepo->updateBatchStatus($batchId, 'staged');
            return ['success' => false, 'message' => 'No valid pending rows found in this batch.'];
        }

        // Group rows: items with the same non-empty invoice_group_key form one multi-item invoice.
        // If invoice_group_key is empty, each row forms its own independent invoice.
        $invoiceGroups = [];
        foreach ($validRows as $rowRecord) {
            $parsed = json_decode($rowRecord['parsed_data_json'] ?? '{}', true) ?: [];
            $groupKey = trim((string)($parsed['invoice_group_key'] ?? ''));

            if ($groupKey !== '') {
                $groupHash = 'GRP_' . md5($groupKey . '_' . ($parsed['student_name'] ?? ''));
            } else {
                $groupHash = 'ROW_' . $rowRecord['id'];
            }

            if (!isset($invoiceGroups[$groupHash])) {
                $invoiceGroups[$groupHash] = [
                    'group_key'    => $groupKey,
                    'master'       => $parsed,
                    'items'        => [],
                    'row_ids'      => [],
                ];
            }

            $invoiceGroups[$groupHash]['items'][] = [
                'item_name'   => $parsed['item_name'],
                'description' => $parsed['item_description'] ?? '',
                'quantity'    => $parsed['quantity'],
                'unit_price'  => $parsed['unit_price'],
                'amount'      => round($parsed['quantity'] * $parsed['unit_price'], 2),
                'sort_order'  => count($invoiceGroups[$groupHash]['items']),
            ];

            $invoiceGroups[$groupHash]['row_ids'][] = (int) $rowRecord['id'];
        }

        $settings = $this->invoiceRepo->getSettings();
        $successCount = 0;
        $failedCount  = 0;
        $seqOffset    = 0;

        foreach ($invoiceGroups as $group) {
            $master = $group['master'];
            $items  = $group['items'];
            $rowIds = $group['row_ids'];

            // Calculate totals using central calculation engine
            $totals = $this->invoiceService->calculateTotals(
                $items,
                (float) ($master['discount'] ?? 0),
                (float) ($master['vat_percent'] ?? 0),
                (float) ($master['amount_paid'] ?? 0)
            );

            // Determine status based on amounts
            $status = 'unpaid';
            if ($totals['amount_due'] <= 0 && $totals['grand_total'] > 0) {
                $status = 'paid';
            }

            // Generate unique invoice number with safe offset progression
            $invoiceNumber = $this->invoiceRepo->generateInvoiceNumber($settings, $seqOffset++);

            $invoiceData = [
                'invoice_number'  => $invoiceNumber,
                'student_name'    => $master['student_name'],
                'student_email'   => $master['student_email']   ?? null,
                'student_phone'   => $master['student_phone']   ?? null,
                'student_country' => $master['student_country'] ?? null,
                'currency'        => $master['currency'],
                'subtotal'        => $totals['subtotal'],
                'discount'        => $totals['discount'],
                'vat_percent'     => $totals['vat_percent'],
                'vat_amount'      => $totals['vat_amount'],
                'grand_total'     => $totals['grand_total'],
                'amount_paid'     => $totals['amount_paid'],
                'amount_due'      => $totals['amount_due'],
                'status'          => $status,
                'invoice_date'    => $master['invoice_date'],
                'due_date'        => !empty($master['due_date']) ? $master['due_date'] : null,
                'notes'           => $master['notes'] ?? '',
                'show_signature'  => 1,
                'created_by'      => $adminId,
            ];

            try {
                // Call atomic invoice create
                $invoiceId = $this->invoiceRepo->create($invoiceData, $items);

                // Log audit trail for Super Admin tracking
                if ($this->activityRepo !== null) {
                    $this->activityRepo->logActivity(
                        $invoiceId,
                        $invoiceNumber,
                        $adminId,
                        'CREATED',
                        "Bulk generated invoice {$invoiceNumber} for {$invoiceData['student_name']} (Batch #{$batchUuid})",
                        [
                            'source'      => 'bulk_upload',
                            'batch_uuid'  => $batchUuid,
                            'grand_total' => $invoiceData['grand_total'],
                            'items_count' => count($items),
                        ]
                    );
                }

                // Update staging rows
                foreach ($rowIds as $rId) {
                    $this->bulkRepo->updateRowGenerationResult($rId, 'generated', $invoiceId, $invoiceNumber);
                }

                $successCount += count($rowIds);
            } catch (\Throwable $e) {
                error_log('[InvoiceBulkService] Generation error for group: ' . $e->getMessage());

                foreach ($rowIds as $rId) {
                    $this->bulkRepo->updateRowGenerationResult($rId, 'failed', null, null, $e->getMessage());
                }

                $failedCount += count($rowIds);
            }
        }

        $this->bulkRepo->completeBatch($batchId, $successCount, $failedCount);

        return [
            'success'   => true,
            'message'   => "Batch generation completed. {$successCount} records generated, {$failedCount} failed.",
            'generated' => $successCount,
            'failed'    => $failedCount,
        ];
    }

    // ===========================================================
    // SUMMARY & REPORTING
    // ===========================================================

    /**
     * Get summary metrics and generated invoice listings for a completed batch.
     *
     * @param string $batchUuid
     * @return array|null
     */
    public function getBatchSummary(string $batchUuid): ?array
    {
        $batch = $this->bulkRepo->findBatchByUuid($batchUuid);
        if ($batch === null) {
            return null;
        }

        $batchId = (int) $batch['id'];
        $generatedInvoices = $this->bulkRepo->getGeneratedInvoices($batchId);
        $errorRows         = $this->bulkRepo->getErrorRows($batchId);

        return [
            'batch'              => $batch,
            'generated_invoices' => $generatedInvoices,
            'error_rows'         => $errorRows,
        ];
    }

    /**
     * Stream CSV file containing all failed rows with error descriptions.
     *
     * @param string $batchUuid
     */
    public function exportErrorsCsv(string $batchUuid): void
    {
        $batch = $this->bulkRepo->findBatchByUuid($batchUuid);
        if ($batch === null) {
            http_response_code(404);
            echo 'Batch not found.';
            exit;
        }

        $batchId   = (int) $batch['id'];
        $errorRows = $this->bulkRepo->getErrorRows($batchId);

        $filename = 'invoice_bulk_errors_' . substr($batchUuid, 0, 8) . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM

        $headers = [
            'row_number',
            'student_name',
            'student_email',
            'student_phone',
            'currency',
            'invoice_date',
            'due_date',
            'item_name',
            'quantity',
            'unit_price',
            'discount',
            'vat_percent',
            'amount_paid',
            'error_reason',
        ];
        fputcsv($out, $headers);

        foreach ($errorRows as $errRow) {
            $raw = json_decode($errRow['raw_data_json'] ?? '{}', true) ?: [];
            $reason = !empty($errRow['validation_error']) ? $errRow['validation_error'] : ($errRow['generation_error'] ?? 'Unknown error');

            fputcsv($out, [
                $errRow['row_number'],
                $raw['student_name']  ?? '',
                $raw['student_email'] ?? '',
                $raw['student_phone'] ?? '',
                $raw['currency']      ?? '',
                $raw['invoice_date']  ?? '',
                $raw['due_date']      ?? '',
                $raw['item_name']     ?? '',
                $raw['quantity']      ?? '',
                $raw['unit_price']    ?? '',
                $raw['discount']      ?? '',
                $raw['vat_percent']   ?? '',
                $raw['amount_paid']   ?? '',
                $reason,
            ]);
        }

        fclose($out);
        exit;
    }

    // ===========================================================
    // PRIVATE VALIDATION HELPERS
    // ===========================================================

    /**
     * Validate a single CSV row against business and data-integrity rules.
     *
     * @param array $row
     * @param array $allowedCurrencies
     * @return array ['is_valid' => bool, 'error_message' => string|null, 'parsed_data' => array]
     */
    private function validateRow(array $row, array $allowedCurrencies): array
    {
        $errors = [];

        // 1. Student Name
        $studentName = trim($row['student_name'] ?? '');
        if ($studentName === '') {
            $errors[] = 'Student name is required.';
        } elseif (mb_strlen($studentName) > 150) {
            $errors[] = 'Student name must not exceed 150 characters.';
        }

        // 2. Email
        $studentEmail = trim($row['student_email'] ?? '');
        if ($studentEmail !== '' && !filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address format.';
        }

        // 3. Phone & Country
        $studentPhone   = substr(trim($row['student_phone'] ?? ''), 0, 30);
        $studentCountry = substr(trim($row['student_country'] ?? ''), 0, 100);

        // 4. Currency
        $currency = strtoupper(trim($row['currency'] ?? ''));
        if ($currency === '') {
            $currency = 'BDT'; // Default fallback
        } elseif (!in_array($currency, $allowedCurrencies, true)) {
            $errors[] = "Unsupported currency '{$currency}'.";
        }

        // 5. Invoice Date
        $invoiceDate = trim($row['invoice_date'] ?? '');
        if ($invoiceDate === '') {
            $invoiceDate = date('Y-m-d');
        } elseif (!$this->isValidDate($invoiceDate)) {
            $errors[] = "Invalid invoice date format '{$invoiceDate}' (must be YYYY-MM-DD).";
        }

        // 6. Due Date
        $dueDate = trim($row['due_date'] ?? '');
        if ($dueDate !== '') {
            if (!$this->isValidDate($dueDate)) {
                $errors[] = "Invalid due date format '{$dueDate}' (must be YYYY-MM-DD).";
            } elseif ($dueDate < $invoiceDate) {
                $errors[] = 'Due date cannot be earlier than invoice date.';
            }
        }

        // 7. Item Name
        $itemName = trim($row['item_name'] ?? '');
        if ($itemName === '') {
            $errors[] = 'Item description/name is required.';
        } elseif (mb_strlen($itemName) > 255) {
            $errors[] = 'Item name must not exceed 255 characters.';
        }

        // 8. Item Description
        $itemDesc = substr(trim($row['item_description'] ?? ''), 0, 1000);

        // 9. Quantity
        $rawQty = trim($row['quantity'] ?? '');
        $qty = 1.0;
        if ($rawQty !== '') {
            if (!is_numeric($rawQty) || (float) $rawQty <= 0) {
                $errors[] = 'Quantity must be a positive number greater than 0.';
            } else {
                $qty = (float) $rawQty;
            }
        }

        // 10. Unit Price
        $rawPrice = trim($row['unit_price'] ?? '');
        $unitPrice = 0.0;
        if ($rawPrice === '') {
            $errors[] = 'Unit price is required.';
        } elseif (!is_numeric($rawPrice) || (float) $rawPrice < 0) {
            $errors[] = 'Unit price must be a non-negative number (>= 0).';
        } else {
            $unitPrice = (float) $rawPrice;
        }

        // 11. Discount
        $rawDiscount = trim($row['discount'] ?? '');
        $discount = 0.0;
        if ($rawDiscount !== '') {
            if (!is_numeric($rawDiscount) || (float) $rawDiscount < 0) {
                $errors[] = 'Discount must be 0 or a positive number.';
            } else {
                $discount = (float) $rawDiscount;
            }
        }

        // 12. VAT Percent
        $rawVat = trim($row['vat_percent'] ?? '');
        $vatPercent = 0.0;
        if ($rawVat !== '') {
            if (!is_numeric($rawVat) || (float) $rawVat < 0 || (float) $rawVat > 100) {
                $errors[] = 'VAT percent must be between 0 and 100.';
            } else {
                $vatPercent = (float) $rawVat;
            }
        }

        // 13. Amount Paid
        $rawPaid = trim($row['amount_paid'] ?? '');
        $amountPaid = 0.0;
        if ($rawPaid !== '') {
            if (!is_numeric($rawPaid) || (float) $rawPaid < 0) {
                $errors[] = 'Amount paid must be 0 or a positive number.';
            } else {
                $amountPaid = (float) $rawPaid;
            }
        }

        // 14. Notes & Group Key
        $notes    = substr(trim($row['notes'] ?? ''), 0, 1000);
        $groupKey = substr(trim($row['invoice_group_key'] ?? ''), 0, 50);

        $parsedData = [
            'student_name'      => $studentName,
            'student_email'     => $studentEmail !== '' ? $studentEmail : null,
            'student_phone'     => $studentPhone !== '' ? $studentPhone : null,
            'student_country'   => $studentCountry !== '' ? $studentCountry : null,
            'currency'          => $currency,
            'invoice_date'      => $invoiceDate,
            'due_date'          => $dueDate !== '' ? $dueDate : null,
            'item_name'         => $itemName,
            'item_description'  => $itemDesc,
            'quantity'          => $qty,
            'unit_price'        => $unitPrice,
            'discount'          => $discount,
            'vat_percent'       => $vatPercent,
            'amount_paid'       => $amountPaid,
            'notes'             => $notes,
            'invoice_group_key' => $groupKey,
        ];

        return [
            'is_valid'      => empty($errors),
            'error_message' => !empty($errors) ? implode('; ', $errors) : null,
            'parsed_data'   => $parsedData,
        ];
    }

    /**
     * Check valid Y-m-d date string.
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}

