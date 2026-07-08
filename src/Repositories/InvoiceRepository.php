<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

/**
 * InvoiceRepository
 *
 * Pure data-access layer for the invoice system.
 * No business logic — only SELECT/INSERT/UPDATE/DELETE.
 * Uses named column selects (no SELECT *), prepared statements, and bound parameters.
 */
class InvoiceRepository
{
    private PDO $db;

    // Allowed status values — used to whitelist ORDER BY / filter columns
    private const ALLOWED_STATUSES = ['draft', 'unpaid', 'paid'];



    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ===========================================================
    // WRITE OPERATIONS
    // ===========================================================

    /**
     * Create a new invoice with its line items inside a single transaction.
     *
     * @param array $invoiceData  Associative array of invoice fields
     * @param array $items        Array of item arrays (item_name, description, quantity, unit_price, amount, sort_order)
     * @return int                The new invoice ID
     * @throws \RuntimeException  On DB failure (transaction rolled back)
     */
    public function create(array $invoiceData, array $items): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO invoices
                    (invoice_number, student_name, student_email, student_phone, student_country,
                     currency, subtotal, discount, vat_percent, vat_amount, grand_total,
                     status, invoice_date, due_date, notes, created_by)
                VALUES
                    (:invoice_number, :student_name, :student_email, :student_phone, :student_country,
                     :currency, :subtotal, :discount, :vat_percent, :vat_amount, :grand_total,
                     :status, :invoice_date, :due_date, :notes, :created_by)
            ");
            $stmt->execute($invoiceData);
            $invoiceId = (int) $this->db->lastInsertId();

            $this->insertItems($invoiceId, $items);

            $this->db->commit();
            return $invoiceId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new \RuntimeException('Invoice creation failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update an existing invoice and replace all its items atomically.
     *
     * @param int   $id           Invoice ID to update
     * @param array $invoiceData  Fields to update
     * @param array $items        Replacement items list
     * @return bool
     * @throws \RuntimeException  On DB failure
     */
    public function update(int $id, array $invoiceData, array $items): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE invoices
                SET
                    student_name    = :student_name,
                    student_email   = :student_email,
                    student_phone   = :student_phone,
                    student_country = :student_country,
                    currency        = :currency,
                    subtotal        = :subtotal,
                    discount        = :discount,
                    vat_percent     = :vat_percent,
                    vat_amount      = :vat_amount,
                    grand_total     = :grand_total,
                    status          = :status,
                    invoice_date    = :invoice_date,
                    due_date        = :due_date,
                    notes           = :notes
                WHERE id = :id AND deleted_at IS NULL
            ");
            $invoiceData['id'] = $id;
            $stmt->execute($invoiceData);

            // Replace items: delete all then re-insert
            $delStmt = $this->db->prepare("DELETE FROM invoice_items WHERE invoice_id = :invoice_id");
            $delStmt->execute(['invoice_id' => $id]);

            $this->insertItems($id, $items);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new \RuntimeException('Invoice update failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Soft-delete an invoice (sets deleted_at timestamp).
     *
     * @param int $id Invoice ID
     * @return bool
     */
    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE invoices
            SET deleted_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute(['id' => $id]);
    }

    // ===========================================================
    // READ OPERATIONS
    // ===========================================================

    /**
     * Find a single invoice with all its items.
     *
     * @param int $id Invoice ID
     * @return array|null  ['invoice' => [...], 'items' => [...]]
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                id, invoice_number, student_name, student_email, student_phone, student_country,
                currency, subtotal, discount, vat_percent, vat_amount, grand_total,
                status, invoice_date, due_date, notes, created_by, created_at, updated_at
            FROM invoices
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute(['id' => $id]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            return null;
        }

        $itemStmt = $this->db->prepare("
            SELECT id, item_name, description, quantity, unit_price, amount, sort_order
            FROM invoice_items
            WHERE invoice_id = :invoice_id
            ORDER BY sort_order ASC, id ASC
        ");
        $itemStmt->execute(['invoice_id' => $id]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        return ['invoice' => $invoice, 'items' => $items];
    }

    /**
     * Paginated list of invoices with optional filters.
     *
     * @param array $filters  ['search'=>string, 'status'=>string, 'currency'=>string,
     *                         'date_from'=>string, 'date_to'=>string]
     * @param int   $limit
     * @param int   $offset
     * @return array
     */
    public function findAll(array $filters, int $limit, int $offset): array
    {
        [$whereClause, $params] = $this->buildWhereClause($filters);

        $stmt = $this->db->prepare("
            SELECT
                id, invoice_number, student_name, student_email,
                currency, grand_total, status, invoice_date, due_date, created_at
            FROM invoices
            {$whereClause}
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total invoices matching filters (for pagination).
     *
     * @param array $filters
     * @return int
     */
    public function countAll(array $filters): int
    {
        [$whereClause, $params] = $this->buildWhereClause($filters);

        $stmt = $this->db->prepare("
            SELECT COUNT(id) FROM invoices {$whereClause}
        ");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Dashboard statistics: total count + per-currency grand totals.
     * Only non-deleted invoices counted.
     *
     * @return array ['total_count' => int, 'currency_totals' => [['currency'=>, 'total'=>], ...]]
     */
    public function getStats(): array
    {
        $countStmt = $this->db->query("
            SELECT COUNT(id) FROM invoices WHERE deleted_at IS NULL
        ");
        $totalCount = (int) $countStmt->fetchColumn();

        $totalsStmt = $this->db->query("
            SELECT currency, SUM(grand_total) AS total
            FROM invoices
            WHERE deleted_at IS NULL
            GROUP BY currency
            ORDER BY currency ASC
        ");
        $currencyTotals = $totalsStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_count'      => $totalCount,
            'currency_totals'  => $currencyTotals,
        ];
    }

    // ===========================================================
    // INVOICE NUMBER GENERATION
    // ===========================================================

    /**
     * Generate a unique invoice number based on settings format.
     * Format tokens: {PREFIX} {YEAR} {MONTH} {DAY} {SEQ4} {SEQ6}
     *
     * @param array $settings  Result of getSettings()
     * @return string          e.g. INV2506040001
     */
    public function generateInvoiceNumber(array $settings): string
    {
        $prefix  = $settings['invoice_prefix']        ?? 'INV';
        $format  = $settings['invoice_number_format'] ?? '{PREFIX}{YEAR}{MONTH}{SEQ4}';
        $year    = date('Y');
        $month   = date('m');
        $day     = date('d');

        // Get current month's sequence number
        $seqStmt = $this->db->prepare("
            SELECT COUNT(id) + 1 AS next_seq
            FROM invoices
            WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month
        ");
        $seqStmt->execute(['year' => (int) $year, 'month' => (int) $month]);
        $seq = (int) $seqStmt->fetchColumn();

        $number = str_replace(
            ['{PREFIX}', '{YEAR}', '{MONTH}', '{DAY}', '{SEQ4}', '{SEQ6}'],
            [$prefix,    $year,    $month,    $day,    str_pad((string)$seq, 4, '0', STR_PAD_LEFT),
             str_pad((string)$seq, 6, '0', STR_PAD_LEFT)],
            $format
        );

        return $number;
    }

    // ===========================================================
    // SETTINGS
    // ===========================================================

    /**
     * Fetch all invoice settings as a flat key=>value array.
     *
     * @return array
     */
    public function getSettings(): array
    {
        return $this->db->query("SELECT setting_key, setting_value FROM invoice_settings")
                        ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Upsert a single setting value.
     *
     * @param string $key
     * @param string $value
     */
    public function saveSetting(string $key, string $value): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO invoice_settings (setting_key, setting_value)
            VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute(['key' => $key, 'value' => $value]);
    }

    // ===========================================================
    // PRIVATE HELPERS
    // ===========================================================

    /**
     * Insert invoice line items.
     *
     * @param int   $invoiceId
     * @param array $items
     */
    private function insertItems(int $invoiceId, array $items): void
    {
        if (empty($items)) {
            return;
        }
        $stmt = $this->db->prepare("
            INSERT INTO invoice_items (invoice_id, item_name, description, quantity, unit_price, amount, sort_order)
            VALUES (:invoice_id, :item_name, :description, :quantity, :unit_price, :amount, :sort_order)
        ");
        foreach ($items as $index => $item) {
            $stmt->execute([
                'invoice_id'  => $invoiceId,
                'item_name'   => $item['item_name'],
                'description' => $item['description'] ?? null,
                'quantity'    => (float) $item['quantity'],
                'unit_price'  => (float) $item['unit_price'],
                'amount'      => (float) $item['amount'],
                'sort_order'  => $index,
            ]);
        }
    }

    /**
     * Build WHERE clause and bound params from filter array.
     * Protects against SQL injection via parameterized values only.
     *
     * @param array $filters
     * @return array [string $whereClause, array $params]
     */
    private function buildWhereClause(array $filters): array
    {
        $conditions = ['deleted_at IS NULL'];
        $params     = [];

        // Status filter — whitelist validated
        if (!empty($filters['status']) && in_array($filters['status'], self::ALLOWED_STATUSES, true)) {
            $conditions[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        // Currency filter — validated in service layer
        if (!empty($filters['currency'])) {
            $conditions[] = 'currency = :currency';
            $params[':currency'] = strtoupper($filters['currency']);
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $conditions[] = 'invoice_date >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'invoice_date <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        // Text search — against student_name and invoice_number using LIKE (with index on invoice_number, LIKE prefix-only for name)
        if (!empty($filters['search'])) {
            $conditions[] = '(student_name LIKE :search OR invoice_number LIKE :search_exact)';
            $params[':search']       = '%' . $filters['search'] . '%';
            $params[':search_exact'] = $filters['search'] . '%';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        return [$whereClause, $params];
    }
}
