<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\InvoiceRepository;

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

    private const ALLOWED_STATUSES = ['draft', 'unpaid', 'paid'];
    private const PER_PAGE         = 15;

    public function __construct(InvoiceRepository $repo)
    {
        $this->repo = $repo;
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
        $totals   = $this->calculateTotals($items, (float)($input['discount'] ?? 0), (float)($input['vat_percent'] ?? 0));
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
            'status'          => $this->sanitizeStatus($input['status'] ?? 'unpaid'),
            'invoice_date'    => $input['invoice_date'],
            'due_date'        => !empty($input['due_date']) ? $input['due_date'] : null,
            'notes'           => trim($input['notes'] ?? ''),
            'created_by'      => $adminId,
        ];

        try {
            $invoiceId = $this->repo->create($invoiceData, $items);
            return ['success' => true, 'invoice_id' => $invoiceId, 'errors' => []];
        } catch (\RuntimeException $e) {
            error_log('[InvoiceService] Create failed: ' . $e->getMessage());
            return ['success' => false, 'invoice_id' => null, 'errors' => ['system' => 'Failed to save invoice. Please try again.']];
        }
    }

    /**
     * Update an existing invoice.
     *
     * @param int   $id     Invoice ID
     * @param array $input  Raw POST data
     * @return array  ['success' => bool, 'errors' => array]
     */
    public function updateInvoice(int $id, array $input): array
    {
        $errors = $this->validateInvoiceInput($input);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $items  = $this->sanitizeItems($input['items'] ?? []);
        $totals = $this->calculateTotals($items, (float)($input['discount'] ?? 0), (float)($input['vat_percent'] ?? 0));

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
            'status'          => $this->sanitizeStatus($input['status'] ?? 'unpaid'),
            'invoice_date'    => $input['invoice_date'],
            'due_date'        => !empty($input['due_date']) ? $input['due_date'] : null,
            'notes'           => trim($input['notes'] ?? ''),
        ];

        try {
            $ok = $this->repo->update($id, $invoiceData, $items);
            return ['success' => $ok, 'errors' => $ok ? [] : ['system' => 'Invoice not found or already deleted.']];
        } catch (\RuntimeException $e) {
            error_log('[InvoiceService] Update failed: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['system' => 'Failed to update invoice. Please try again.']];
        }
    }

    /**
     * Soft-delete an invoice.
     *
     * @param int $id
     * @return bool
     */
    public function deleteInvoice(int $id): bool
    {
        return $this->repo->softDelete($id);
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
     * @param array $data  Associative array of setting_key => value
     * @return array ['success' => bool, 'errors' => array]
     */
    public function saveSettings(array $data): array
    {
        $allowedKeys = [
            'invoice_prefix', 'invoice_number_format', 'institution_name', 'institution_tagline',
            'institution_address', 'institution_phone', 'institution_email',
            'institution_logo', 'invoice_footer_note',
        ];

        $errors = [];

        if (isset($data['invoice_prefix'])) {
            $prefix = trim($data['invoice_prefix']);
            if ($prefix === '' || strlen($prefix) > 10) {
                $errors['invoice_prefix'] = 'Prefix must be 1–10 characters.';
            }
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
     * Calculate subtotal, VAT amount, and grand total from items + discount + VAT%.
     * All arithmetic done server-side — never trust client-side totals.
     *
     * @param array $items       Sanitized items array
     * @param float $discount    Flat discount amount
     * @param float $vatPercent  VAT percentage (0–100)
     * @return array ['subtotal', 'discount', 'vat_percent', 'vat_amount', 'grand_total']
     */
    public function calculateTotals(array $items, float $discount, float $vatPercent): array
    {
        $subtotal = (float) array_sum(array_column($items, 'amount'));

        $discount   = max(0.0, $discount);
        $vatPercent = max(0.0, min(100.0, $vatPercent));
        $afterDiscount = max(0.0, $subtotal - $discount);
        $vatAmount  = round($afterDiscount * ($vatPercent / 100), 2);
        $grandTotal = round($afterDiscount + $vatAmount, 2);

        return [
            'subtotal'    => round($subtotal,   2),
            'discount'    => round($discount,   2),
            'vat_percent' => round($vatPercent, 2),
            'vat_amount'  => $vatAmount,
            'grand_total' => $grandTotal,
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

        if (empty($input['items']) || !is_array($input['items'])) {
            $errors['items'] = 'At least one item is required.';
        } else {
            foreach ($input['items'] as $i => $item) {
                if (empty(trim($item['item_name'] ?? ''))) {
                    $errors["items_{$i}_name"] = "Item #" . ($i + 1) . " name is required.";
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
