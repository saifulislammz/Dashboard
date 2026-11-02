<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\InvoiceRepository;
use App\Repositories\InvoiceActivityRepository;

/**
 * InvoiceService
 *
 * All invoice business logic lives here.
 * Controller calls Service; Service calls Repository.
 * Never touches DB directly. Validates, calculates, and formats data.
 */
class InvoiceService
{
    private InvoiceRepository $repo;
    private ?InvoiceActivityRepository $activityRepo;
    private ?InvoiceEmailService $emailService;

    private const ALLOWED_STATUSES = ['draft', 'unpaid', 'paid'];
    private const PER_PAGE         = 15;

    public function __construct(
        InvoiceRepository $repo,
        ?InvoiceActivityRepository $activityRepo = null,
        ?InvoiceEmailService $emailService = null
    ) {
        $this->repo = $repo;

        if ($activityRepo === null && isset($GLOBALS['db']) && $GLOBALS['db'] instanceof \PDO) {
            $this->activityRepo = new InvoiceActivityRepository($GLOBALS['db']);
        } else {
            $this->activityRepo = $activityRepo;
        }

        $this->emailService = $emailService;
    }

    /**
     * Get or lazy-instantiate the InvoiceEmailService.
     */
    public function getEmailService(): InvoiceEmailService
    {
        if ($this->emailService === null) {
            $db           = $GLOBALS['db'] ?? null;
            $mailer       = new \App\Services\Mail\PhpMailerService();
            $pdfService   = new \App\Services\InvoicePdfService();
            $emailLogRepo = ($db instanceof \PDO) ? new \App\Repositories\InvoiceEmailLogRepository($db) : null;
            $activityRepo = $this->activityRepo;

            $this->emailService = new InvoiceEmailService(
                $this->repo,
                $mailer,
                $pdfService,
                $emailLogRepo,
                $activityRepo
            );
        }

        return $this->emailService;
    }

    // ===========================================================
    // CURRENCIES
    // ===========================================================

    /**
     * Get all currencies from DB.
     */
    public function getCurrencies(): array
    {
        return $this->repo->getCurrencies();
    }

    // ===========================================================
    // INVOICE CREATION & UPDATE
    // ===========================================================

    /**
     * Validate input, calculate totals, and persist a new invoice.
     *
     * @param array $input  Raw POST data
     * @param int   $adminId  Logged-in admin user ID
     * @return array  ['success' => bool, 'invoice_id' => int|null, 'errors' => array]
     */
    public function createInvoice(array $input, int $adminId): array
    {
        $errors = $this->validateInvoiceInput($input);
        if (!empty($errors)) {
            return ['success' => false, 'invoice_id' => null, 'errors' => $errors];
        }

        $items    = $this->sanitizeItems($input['items'] ?? []);
        $totals   = $this->calculateTotals(
            $items,
            (float)($input['discount']    ?? 0),
            (float)($input['vat_percent'] ?? 0),
            (float)($input['amount_paid'] ?? 0)
        );
        $settings = $this->repo->getSettings();

        // If invoice_number not provided or empty, auto-generate
        $invoiceNumber = trim($input['invoice_number'] ?? '');
        if ($invoiceNumber === '') {
            $invoiceNumber = $this->repo->generateInvoiceNumber($settings);
        }

        $invoiceData = [
            'invoice_number'  => $invoiceNumber,
            'student_name'    => trim($input['student_name']),
            'student_email'   => trim($input['student_email']   ?? ''),
            'student_phone'   => trim($input['student_phone']   ?? ''),
            'student_country' => trim($input['student_country'] ?? ''),
            'currency'        => strtoupper(trim($input['currency'])),
            'subtotal'        => $totals['subtotal'],
            'discount'        => $totals['discount'],
            'vat_percent'     => $totals['vat_percent'],
            'vat_amount'      => $totals['vat_amount'],
            'grand_total'     => $totals['grand_total'],
            'amount_paid'     => $totals['amount_paid'],
            'amount_due'      => $totals['amount_due'],
            'status'          => $this->sanitizeStatus($input['status'] ?? 'unpaid'),
            'invoice_date'    => $input['invoice_date'],
            'due_date'        => !empty($input['due_date']) ? $input['due_date'] : null,
            'notes'           => trim($input['notes'] ?? ''),
            'show_signature'  => !empty($input['show_signature']) ? 1 : 0,
            'created_by'      => $adminId,
        ];

        try {
            $invoiceId = $this->repo->create($invoiceData, $items);

            if ($this->activityRepo !== null) {
                $this->activityRepo->logActivity(
                    $invoiceId,
                    $invoiceData['invoice_number'],
                    $adminId,
                    'CREATED',
                    "Invoice {$invoiceData['invoice_number']} created for {$invoiceData['student_name']}",
                    [
                        'grand_total' => $invoiceData['grand_total'],
                        'amount_paid' => $invoiceData['amount_paid'],
                        'amount_due'  => $invoiceData['amount_due'],
                        'currency'    => $invoiceData['currency'],
                        'status'      => $invoiceData['status'],
                        'items_count' => count($items)
                    ]
                );
            }

            // Safe Decoupled Auto-Email Dispatch (Fail-safe: never interrupts invoice creation)
            try {
                $emailService = $this->getEmailService();
                $emailService->triggerAutoEmailIfEnabled($invoiceId, $adminId);
            } catch (\Throwable $e) {
                error_log('[InvoiceService] Auto email trigger failure: ' . $e->getMessage());
            }

            return ['success' => true, 'invoice_id' => $invoiceId, 'errors' => []];
        } catch (\RuntimeException $e) {
            error_log('[InvoiceService] Create failed: ' . $e->getMessage());
            return ['success' => false, 'invoice_id' => null, 'errors' => ['system' => 'Failed to save invoice. Please try again.']];
        }
    }

    /**
     * Update an existing invoice.
     *
     * @param int      $id      Invoice ID
     * @param array    $input   Raw POST data
     * @param int|null $actorId User ID of the user performing the update
     * @return array  ['success' => bool, 'errors' => array]
     */
    public function updateInvoice(int $id, array $input, ?int $actorId = null): array
    {
        $errors = $this->validateInvoiceInput($input);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $items  = $this->sanitizeItems($input['items'] ?? []);
        $totals = $this->calculateTotals(
            $items,
            (float)($input['discount']    ?? 0),
            (float)($input['vat_percent'] ?? 0),
            (float)($input['amount_paid'] ?? 0)
        );

        $invoiceData = [
            'student_name'    => trim($input['student_name']),
            'student_email'   => trim($input['student_email']   ?? ''),
            'student_phone'   => trim($input['student_phone']   ?? ''),
            'student_country' => trim($input['student_country'] ?? ''),
            'currency'        => strtoupper(trim($input['currency'])),
            'subtotal'        => $totals['subtotal'],
            'discount'        => $totals['discount'],
            'vat_percent'     => $totals['vat_percent'],
            'vat_amount'      => $totals['vat_amount'],
            'grand_total'     => $totals['grand_total'],
            'amount_paid'     => $totals['amount_paid'],
            'amount_due'      => $totals['amount_due'],
            'status'          => $this->sanitizeStatus($input['status'] ?? 'unpaid'),
            'invoice_date'    => $input['invoice_date'],
            'due_date'        => !empty($input['due_date']) ? $input['due_date'] : null,
            'notes'           => trim($input['notes'] ?? ''),
            'show_signature'  => !empty($input['show_signature']) ? 1 : 0,
        ];

        try {
            $existing = $this->repo->findById($id);
            $invNum   = $existing['invoice']['invoice_number'] ?? "ID-{$id}";

            $ok = $this->repo->update($id, $invoiceData, $items);

            if ($ok && $this->activityRepo !== null) {
                if ($actorId === null && isset($GLOBALS['auth']) && $GLOBALS['auth']->isLoggedIn()) {
                    $actorId = (int) $GLOBALS['auth']->getUserId();
                }

                $this->activityRepo->logActivity(
                    $id,
                    $invNum,
                    $actorId,
                    'UPDATED',
                    "Invoice {$invNum} details and line items updated",
                    [
                        'grand_total' => $invoiceData['grand_total'],
                        'amount_paid' => $invoiceData['amount_paid'],
                        'amount_due'  => $invoiceData['amount_due'],
                        'status'      => $invoiceData['status'],
                        'items_count' => count($items)
                    ]
                );
            }

            return ['success' => $ok, 'errors' => $ok ? [] : ['system' => 'Invoice not found or already deleted.']];
        } catch (\RuntimeException $e) {
            error_log('[InvoiceService] Update failed: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['system' => 'Failed to update invoice. Please try again.']];
        }
    }

    /**
     * Update the status of an invoice.
     *
     * @param int      $id
     * @param string   $status
     * @param int|null $actorId
     * @return array ['success' => bool, 'errors' => array]
     */
    public function updateStatus(int $id, string $status, ?int $actorId = null): array
    {
        $status = $this->sanitizeStatus($status);
        
        try {
            $existing = $this->repo->findById($id);
            $invNum   = $existing['invoice']['invoice_number'] ?? "ID-{$id}";
            $oldStatus = $existing['invoice']['status'] ?? 'unknown';

            $ok = $this->repo->updateStatus($id, $status);

            if ($ok && $this->activityRepo !== null) {
                if ($actorId === null && isset($GLOBALS['auth']) && $GLOBALS['auth']->isLoggedIn()) {
                    $actorId = (int) $GLOBALS['auth']->getUserId();
                }

                $this->activityRepo->logActivity(
                    $id,
                    $invNum,
                    $actorId,
                    'STATUS_CHANGED',
                    "Invoice {$invNum} status changed from {$oldStatus} to {$status}",
                    [
                        'old_status' => $oldStatus,
                        'new_status' => $status
                    ]
                );
            }

            return ['success' => $ok, 'errors' => $ok ? [] : ['system' => 'Invoice not found or already deleted.']];
        } catch (\RuntimeException $e) {
            error_log('[InvoiceService] Update status failed: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['system' => 'Failed to update invoice status. Please try again.']];
        }
    }

    /**
     * Soft-delete an invoice.
     *
     * @param int      $id
     * @param int|null $deletedBy
     * @return bool
     */
    public function deleteInvoice(int $id, ?int $deletedBy = null): bool
    {
        if ($deletedBy === null && isset($GLOBALS['auth']) && $GLOBALS['auth']->isLoggedIn()) {
            $deletedBy = (int) $GLOBALS['auth']->getUserId();
        }

        $existing = $this->repo->findById($id);
        $invNum   = $existing['invoice']['invoice_number'] ?? "ID-{$id}";

        $ok = $this->repo->softDelete($id, $deletedBy);

        if ($ok && $this->activityRepo !== null) {
            $this->activityRepo->logActivity(
                $id,
                $invNum,
                $deletedBy,
                'SOFT_DELETED',
                "Invoice {$invNum} soft-deleted from billing registry",
                [
                    'deleted_by' => $deletedBy,
                    'grand_total' => $existing['invoice']['grand_total'] ?? null,
                    'student_name' => $existing['invoice']['student_name'] ?? null
                ]
            );
        }

        return $ok;
    }

    // ===========================================================
    // READ / LIST
    // ===========================================================

    /**
     * Get a single invoice with its items for view/print.
     *
     * @param int $id
     * @return array|null
     */
    public function getInvoiceById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    /**
     * Get paginated invoice list with formatted data for the dashboard view.
     *
     * @param array $filters
     * @param int   $page
     * @return array ['invoices'=>[], 'total'=>int, 'pages'=>int, 'currentPage'=>int, 'perPage'=>int]
     */
    public function getInvoiceList(array $filters, int $page): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;

        $sanitizedFilters = $this->sanitizeFilters($filters);

        $invoices = $this->repo->findAll($sanitizedFilters, self::PER_PAGE, $offset);
        $total    = $this->repo->countAll($sanitizedFilters);
        $pages    = (int) ceil($total / self::PER_PAGE);

        return [
            'invoices'    => $invoices,
            'total'       => $total,
            'pages'       => $pages,
            'currentPage' => $page,
            'perPage'     => self::PER_PAGE,
            'filters'     => $sanitizedFilters,
        ];
    }

    /**
     * Get dashboard statistics (total invoices, per-currency totals).
     *
     * @return array
     */
    public function getDashboardStats(): array
    {
        return $this->repo->getStats();
    }

    // ===========================================================
    // SETTINGS
    // ===========================================================

    /**
     * Get all invoice settings.
     *
     * @return array
     */
    public function getSettings(): array
    {
        return $this->repo->getSettings();
    }

    /**
     * Generate a preview invoice number based on provided format string.
     * Used in settings page live preview.
     *
     * @param array $settings
     * @return string
     */
    public function generateInvoiceNumberPreview(array $settings): string
    {
        return $this->repo->generateInvoiceNumber($settings);
    }

    /**
     * Save invoice settings (validates keys before persisting).
     *
     * @param array      $data          Associative array of setting_key => value
     * @param array|null $logoFile      Uploaded logo file array
     * @param array|null $signatureFile Uploaded signature file array
     * @return array ['success' => bool, 'errors' => array]
     */
    public function saveSettings(array $data, ?array $logoFile = null, ?array $signatureFile = null): array
    {
        $allowedKeys = [
            'invoice_prefix', 'invoice_number_format', 'institution_name', 'institution_tagline',
            'institution_address', 'institution_phone', 'institution_email',
            'institution_logo', 'invoice_footer_note',
            'invoice_signature', 'invoice_signature_enabled',
            'invoice_auto_email_enabled', 'invoice_email_subject', 'invoice_email_bcc', 'invoice_email_custom_notes',
        ];

        $errors = [];

        if (isset($data['invoice_prefix'])) {
            $prefix = trim($data['invoice_prefix']);
            if ($prefix === '' || strlen($prefix) > 10) {
                $errors['invoice_prefix'] = 'Prefix must be 1–10 characters.';
            }
        }

        if (!empty($data['invoice_email_bcc'])) {
            $bcc = trim($data['invoice_email_bcc']);
            if (!filter_var($bcc, FILTER_VALIDATE_EMAIL)) {
                $errors['invoice_email_bcc'] = 'Invalid BCC email address format.';
            }
        }

        // Handle signature removal if requested
        if (!empty($data['remove_signature'])) {
            $existingSettings = $this->repo->getSettings();
            $oldSig = $existingSettings['invoice_signature'] ?? '';
            if ($oldSig && str_starts_with($oldSig, '/uploads/signatures/')) {
                $oldPath = __DIR__ . '/../../public' . $oldSig;
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $data['invoice_signature'] = '';
        }

        // Handle logo upload with strict finfo MIME validation & safe randomization
        $file = $logoFile ?? ($_FILES['institution_logo'] ?? null);
        if ($file && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK && !empty($file['tmp_name'])) {
            // 1. Max size limit (2MB)
            if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
                $errors['institution_logo'] = 'Logo file size must not exceed 2MB.';
            } else {
                // 2. Strict server-side MIME type check using finfo (inspects actual file content)
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);
                
                $allowedMimes = [
                    'image/jpeg' => ['jpg', 'jpeg'],
                    'image/png'  => ['png'],
                    'image/webp' => ['webp'],
                    'image/gif'  => ['gif'],
                ];
                
                $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
                
                if (!array_key_exists($mimeType, $allowedMimes) || !in_array($ext, $allowedMimes[$mimeType], true)) {
                    $errors['institution_logo'] = 'Invalid image file. Only valid JPG, PNG, WEBP, or GIF images are permitted.';
                } else {
                    $uploadDir = __DIR__ . '/../../public/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Unpredictable random filename prevents path traversal and collisions
                    $normalizedExt = ($ext === 'jpeg') ? 'jpg' : $ext;
                    $filename = 'invoice_logo_' . bin2hex(random_bytes(16)) . '.' . $normalizedExt;
                    $targetPath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $data['institution_logo'] = '/uploads/' . $filename;
                    } else {
                        $errors['institution_logo'] = 'Failed to save uploaded logo file.';
                    }
                }
            }
        }

        // Handle signature upload with strict finfo MIME validation & safe randomization (Transparent PNG)
        $sigFile = $signatureFile ?? ($_FILES['institution_signature'] ?? null);
        if ($sigFile && isset($sigFile['error']) && $sigFile['error'] === UPLOAD_ERR_OK && !empty($sigFile['tmp_name'])) {
            // 1. Max size limit (1MB)
            if (($sigFile['size'] ?? 0) > 1024 * 1024) {
                $errors['institution_signature'] = 'Signature file size must not exceed 1MB.';
            } else {
                // 2. Strict server-side MIME type check using finfo
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($sigFile['tmp_name']);
                $ext = strtolower(pathinfo($sigFile['name'] ?? '', PATHINFO_EXTENSION));

                if ($mimeType !== 'image/png' || $ext !== 'png') {
                    $errors['institution_signature'] = 'Invalid file format. Only transparent PNG images are permitted for signatures.';
                } else {
                    // 3. Verify image integrity with getimagesize
                    $imgInfo = @getimagesize($sigFile['tmp_name']);
                    if (!$imgInfo || ($imgInfo[2] ?? 0) !== IMAGETYPE_PNG) {
                        $errors['institution_signature'] = 'Corrupt or invalid PNG image file.';
                    } else {
                        $uploadDir = __DIR__ . '/../../public/uploads/signatures/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $filename = 'sig_' . bin2hex(random_bytes(16)) . '.png';
                        $targetPath = $uploadDir . $filename;

                        // Unlink previous signature file if exists
                        $existingSettings = $this->repo->getSettings();
                        $oldSig = $existingSettings['invoice_signature'] ?? '';
                        if ($oldSig && str_starts_with($oldSig, '/uploads/signatures/')) {
                            $oldPath = __DIR__ . '/../../public' . $oldSig;
                            if (file_exists($oldPath)) {
                                @unlink($oldPath);
                            }
                        }

                        if (move_uploaded_file($sigFile['tmp_name'], $targetPath)) {
                            $data['invoice_signature'] = '/uploads/signatures/' . $filename;
                        } else {
                            $errors['institution_signature'] = 'Failed to save uploaded signature file.';
                        }
                    }
                }
            }
        }

        // Global on/off setting: if the form was posted for institution/invoice settings,
        // explicitly set '1' or '0' based on checkbox presence
        if (isset($data['invoice_settings_submitted']) || isset($data['institution_name']) || isset($data['save_invoice_settings'])) {
            $data['invoice_signature_enabled']  = !empty($data['invoice_signature_enabled']) ? '1' : '0';
            $data['invoice_auto_email_enabled'] = !empty($data['invoice_auto_email_enabled']) ? '1' : '0';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $data)) {
                $this->repo->saveSetting($key, trim((string) $data[$key]));
            }
        }

        return ['success' => true, 'errors' => []];
    }

    // ===========================================================
    // CALCULATION (pure, testable)
    // ===========================================================

    /**
     * Calculate subtotal, VAT amount, grand total, amount paid, and amount due from items.
     * All arithmetic done server-side — never trust client-side totals.
     *
     * @param array $items       Sanitized items array
     * @param float $discount    Flat discount amount
     * @param float $vatPercent  VAT percentage (0–100)
     * @param float $amountPaid  Amount already collected from student (>= 0)
     * @return array ['subtotal', 'discount', 'vat_percent', 'vat_amount', 'grand_total', 'amount_paid', 'amount_due']
     */
    public function calculateTotals(array $items, float $discount, float $vatPercent, float $amountPaid = 0.0): array
    {
        $subtotal = (float) array_sum(array_column($items, 'amount'));

        $discount   = max(0.0, $discount);
        $vatPercent = max(0.0, min(100.0, $vatPercent));
        $afterDiscount = max(0.0, $subtotal - $discount);
        $vatAmount  = round($afterDiscount * ($vatPercent / 100), 2);
        $grandTotal = round($afterDiscount + $vatAmount, 2);

        // Clamp amount_paid to [0, grand_total] — cannot overpay
        $amountPaid = round(max(0.0, min($grandTotal, $amountPaid)), 2);
        $amountDue  = round(max(0.0, $grandTotal - $amountPaid), 2);

        return [
            'subtotal'    => round($subtotal,   2),
            'discount'    => round($discount,   2),
            'vat_percent' => round($vatPercent, 2),
            'vat_amount'  => $vatAmount,
            'grand_total' => $grandTotal,
            'amount_paid' => $amountPaid,
            'amount_due'  => $amountDue,
        ];
    }

    /**
     * Get currency info for a given currency code.
     *
     * @param string $code
     * @return array|null
     */
    public function getCurrencyInfo(string $code): ?array
    {
        $currencies = $this->getCurrencies();
        return $currencies[strtoupper($code)] ?? null;
    }

    // ===========================================================
    // PRIVATE HELPERS
    // ===========================================================

    /**
     * Validate invoice POST input.
     *
     * @param array $input
     * @return array  Errors keyed by field name
     */
    private function validateInvoiceInput(array $input): array
    {
        $errors = [];

        if (empty(trim($input['student_name'] ?? ''))) {
            $errors['student_name'] = 'Student name is required.';
        } elseif (strlen(trim($input['student_name'])) > 150) {
            $errors['student_name'] = 'Student name must not exceed 150 characters.';
        }

        if (!empty($input['student_email']) && !filter_var(trim($input['student_email']), FILTER_VALIDATE_EMAIL)) {
            $errors['student_email'] = 'Please enter a valid email address.';
        }

        $currencies = $this->getCurrencies();
        if (empty($input['currency']) || !array_key_exists(strtoupper(trim($input['currency'])), $currencies)) {
            $errors['currency'] = 'Please select a valid currency.';
        }

        if (empty($input['invoice_date'])) {
            $errors['invoice_date'] = 'Invoice date is required.';
        } elseif (!$this->isValidDate($input['invoice_date'])) {
            $errors['invoice_date'] = 'Invoice date format is invalid.';
        }

        if (!empty($input['due_date']) && !$this->isValidDate($input['due_date'])) {
            $errors['due_date'] = 'Due date format is invalid.';
        }

        // amount_paid must be a non-negative number (optional — defaults to 0)
        if (isset($input['amount_paid']) && $input['amount_paid'] !== '') {
            if (!is_numeric($input['amount_paid']) || (float) $input['amount_paid'] < 0) {
                $errors['amount_paid'] = 'Amount paid must be 0 or greater.';
            }
        }

        if (empty($input['items']) || !is_array($input['items'])) {
            $errors['items'] = 'At least one item is required.';
        } else {
            foreach ($input['items'] as $i => $item) {
                if (empty(trim($item['item_name'] ?? ''))) {
                    $errors["items_{$i}_name"] = "Item #" . ($i + 1) . " name is required.";
                }
                if (isset($item['description']) && strlen(trim((string) $item['description'])) > 1000) {
                    $errors["items_{$i}_desc"] = "Item #" . ($i + 1) . " description must not exceed 1000 characters.";
                }
                if (!is_numeric($item['quantity'] ?? '') || (float) $item['quantity'] <= 0) {
                    $errors["items_{$i}_qty"] = "Item #" . ($i + 1) . " quantity must be greater than 0.";
                }
                if (!is_numeric($item['unit_price'] ?? '') || (float) $item['unit_price'] < 0) {
                    $errors["items_{$i}_price"] = "Item #" . ($i + 1) . " unit price must be 0 or greater.";
                }
            }
        }

        return $errors;
    }

    /**
     * Sanitize and normalize items array from POST.
     *
     * @param array $rawItems
     * @return array
     */
    private function sanitizeItems(array $rawItems): array
    {
        $items = [];
        foreach ($rawItems as $i => $item) {
            $qty   = max(0, (float) ($item['quantity']   ?? 1));
            $price = max(0, (float) ($item['unit_price'] ?? 0));
            $items[] = [
                'item_name'   => substr(trim($item['item_name']   ?? ''), 0, 255),
                'description' => substr(trim($item['description'] ?? ''), 0, 1000),
                'quantity'    => $qty,
                'unit_price'  => $price,
                'amount'      => round($qty * $price, 2),
                'sort_order'  => $i,
            ];
        }
        return $items;
    }

    /**
     * Sanitize filter array from GET.
     *
     * @param array $raw
     * @return array
     */
    private function sanitizeFilters(array $raw): array
    {
        $currencies = $this->getCurrencies();
        $allowed_currencies = array_keys($currencies);

        $status   = trim($raw['status']   ?? '');
        $currency = strtoupper(trim($raw['currency'] ?? ''));
        $dateFrom = trim($raw['date_from'] ?? '');
        $dateTo   = trim($raw['date_to']   ?? '');
        $search   = trim($raw['search']    ?? '');

        return [
            'status'    => in_array($status, self::ALLOWED_STATUSES, true)   ? $status   : '',
            'currency'  => in_array($currency, $allowed_currencies, true)    ? $currency : '',
            'date_from' => $this->isValidDate($dateFrom) ? $dateFrom : '',
            'date_to'   => $this->isValidDate($dateTo)   ? $dateTo   : '',
            'search'    => substr($search, 0, 100),
        ];
    }

    /**
     * Sanitize status to allowed values.
     *
     * @param string $status
     * @return string
     */
    private function sanitizeStatus(string $status): string
    {
        return in_array($status, self::ALLOWED_STATUSES, true) ? $status : 'unpaid';
    }

    /**
     * Validate a date string as Y-m-d format.
     *
     * @param string $date
     * @return bool
     */
    private function isValidDate(string $date): bool
    {
        if (empty($date)) {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    // ===========================================================
    // CURRENCY MANAGEMENT (DELEGATES TO REPOSITORY)
    // ===========================================================

    /**
     * Add a new currency.
     */
    public function addCurrency(array $data): array
    {
        $errors = [];
        if (empty(trim($data['code'] ?? ''))) {
            $errors['code'] = 'Currency code is required.';
        }
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Currency name is required.';
        }
        if (empty(trim($data['symbol'] ?? ''))) {
            $errors['symbol'] = 'Currency symbol is required.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $ok = $this->repo->addCurrency($data);
        return ['success' => $ok, 'errors' => $ok ? [] : ['system' => 'Failed to save currency.']];
    }

    /**
     * Delete a currency.
     */
    public function deleteCurrency(string $code): bool
    {
        return $this->repo->deleteCurrency($code);
    }
}
