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
    private static ?bool $hasDeletedByColumn = null;

    // Allowed status values — used to whitelist ORDER BY / filter columns
    private const ALLOWED_STATUSES = ['draft', 'unpaid', 'paid'];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Check if the deleted_by column exists in the invoices table.
     * Automatically attempts schema self-healing if missing.
     *
     * @return bool
     */
    private function hasDeletedByColumn(): bool
    {
        if (self::$hasDeletedByColumn !== null) {
            return self::$hasDeletedByColumn;
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM `invoices` LIKE 'deleted_by'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($col !== false && !empty($col)) {
                self::$hasDeletedByColumn = true;
                return true;
            }

            // Attempt self-healing schema migration
            try {
                $this->db->exec("
                    ALTER TABLE `invoices` 
                    ADD COLUMN `deleted_by` INT UNSIGNED NULL DEFAULT NULL COMMENT 'references users.id' AFTER `deleted_at`,
                    ADD INDEX `idx_invoices_deleted_by` (`deleted_by`)
                ");
                self::$hasDeletedByColumn = true;
                return true;
            } catch (\Throwable $alterError) {
                error_log('[InvoiceRepository] Auto-migration of deleted_by column failed: ' . $alterError->getMessage());
                self::$hasDeletedByColumn = false;
                return false;
            }
        } catch (\Throwable $e) {
            self::$hasDeletedByColumn = false;
            return false;
        }
    }

    // ===========================================================
    // WRITE OPERATIONS
    // ===========================================================

    /**
     * Create a new invoice with its line items inside a single transaction.
     * Implements collision retry mechanism to resolve concurrent race conditions on invoice numbers.
     *
     * @param array $invoiceData  Associative array of invoice fields
     * @param array $items        Array of item arrays (item_name, description, quantity, unit_price, amount, sort_order)
     * @return int                The new invoice ID
     * @throws \RuntimeException  On DB failure (transaction rolled back)
     */
    public function create(array $invoiceData, array $items): int
    {
        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;
            $this->db->beginTransaction();
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO invoices
                        (invoice_number, student_name, student_email, student_phone, student_country,
                         currency, subtotal, discount, vat_percent, vat_amount, grand_total,
                         amount_paid, amount_due,
                         status, invoice_date, due_date, notes, show_signature, created_by)
                    VALUES
                        (:invoice_number, :student_name, :student_email, :student_phone, :student_country,
                         :currency, :subtotal, :discount, :vat_percent, :vat_amount, :grand_total,
                         :amount_paid, :amount_due,
                         :status, :invoice_date, :due_date, :notes, :show_signature, :created_by)
                ");
                $stmt->execute($invoiceData);
                $invoiceId = (int) $this->db->lastInsertId();

                $this->insertItems($invoiceId, $items);

                $this->db->commit();
                return $invoiceId;
            } catch (\PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                // Check if this was a duplicate invoice_number collision (Race Condition mitigation)
                $isDuplicate = ($e->getCode() == 23000 || str_contains($e->getMessage(), 'uq_invoice_number') || str_contains($e->getMessage(), 'Duplicate entry'));
                if ($isDuplicate && $attempt < $maxRetries) {
                    $settings = $this->getSettings();
                    $invoiceData['invoice_number'] = $this->generateInvoiceNumber($settings, $attempt);
                    continue;
                }
                throw new \RuntimeException('Invoice creation failed: ' . $e->getMessage(), 0, $e);
            }
        }

        throw new \RuntimeException('Failed to generate unique invoice number after multiple retries.');
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
            // Check existence and lock record to prevent concurrent race conditions
            $lockStmt = $this->db->prepare("
                SELECT id 
                FROM invoices 
                WHERE id = :id AND deleted_at IS NULL 
                FOR UPDATE
            ");
            $lockStmt->execute(['id' => $id]);
            if (!$lockStmt->fetchColumn()) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return false;
            }

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
                    amount_paid     = :amount_paid,
                    amount_due      = :amount_due,
                    status          = :status,
                    invoice_date    = :invoice_date,
                    due_date        = :due_date,
                    notes           = :notes,
                    show_signature  = :show_signature,
                    updated_at      = NOW()
                WHERE id = :id AND deleted_at IS NULL
            ");
            $invoiceData['id'] = $id;
            $stmt->execute($invoiceData);

            // Replace items atomically: delete all existing items, then re-insert updated items
            $delStmt = $this->db->prepare("DELETE FROM invoice_items WHERE invoice_id = :invoice_id");
            $delStmt->execute(['invoice_id' => $id]);

            $this->insertItems($id, $items);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new \RuntimeException('Invoice update failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update the status of an existing invoice.
     *
     * @param int    $id     Invoice ID
     * @param string $status New status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE invoices
            SET status = :status
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    /**
     * Update amount_paid and recalculate amount_due for an existing invoice.
     *
     * Called when a partial or full payment is recorded without regenerating the invoice.
     *
     * @param int   $id          Invoice ID
     * @param float $amountPaid  New total amount paid
     * @param float $amountDue   Remaining balance (grand_total - amountPaid)
     * @return bool
     */
    public function updateAmountPaid(int $id, float $amountPaid, float $amountDue): bool
    {
        $stmt = $this->db->prepare("
            UPDATE invoices
            SET amount_paid = :amount_paid,
                amount_due  = :amount_due
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute([
            'id'          => $id,
            'amount_paid' => $amountPaid,
            'amount_due'  => $amountDue,
        ]);
    }

    /**
     * Soft-delete an invoice (sets deleted_at timestamp and records deleted_by).
     *
     * @param int      $id        Invoice ID
     * @param int|null $deletedBy User ID of the admin who requested deletion
     * @return bool
     */
    public function softDelete(int $id, ?int $deletedBy = null): bool
    {
        $hasDeletedBy = $this->hasDeletedByColumn();

        if ($hasDeletedBy) {
            $stmt = $this->db->prepare("
                UPDATE invoices
                SET deleted_at = NOW(),
                    deleted_by = :deleted_by
                WHERE id = :id AND deleted_at IS NULL
            ");
            return $stmt->execute([
                'id'         => $id,
                'deleted_by' => $deletedBy,
            ]);
        }

        $stmt = $this->db->prepare("
            UPDATE invoices
            SET deleted_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Restore a previously soft-deleted invoice.
     *
     * @param int $id Invoice ID
     * @return bool
     */
    public function restore(int $id): bool
    {
        $hasDeletedBy = $this->hasDeletedByColumn();

        if ($hasDeletedBy) {
            $stmt = $this->db->prepare("
                UPDATE invoices
                SET deleted_at = NULL,
                    deleted_by = NULL
                WHERE id = :id AND deleted_at IS NOT NULL
            ");
            return $stmt->execute(['id' => $id]);
        }

        $stmt = $this->db->prepare("
            UPDATE invoices
            SET deleted_at = NULL
            WHERE id = :id AND deleted_at IS NOT NULL
        ");
        return $stmt->execute(['id' => $id]);
    }

    // ===========================================================
    // READ OPERATIONS
    // ===========================================================

    /**
     * Find a single active invoice with all its items and creator metadata.
     *
     * @param int $id Invoice ID
     * @return array|null  ['invoice' => [...], 'items' => [...]]
     */
    public function findById(int $id): ?array
    {
        $hasDeletedBy = $this->hasDeletedByColumn();
        $deletedByCol = $hasDeletedBy ? 'i.deleted_by' : 'NULL AS deleted_by';

        $stmt = $this->db->prepare("
            SELECT
                i.id, i.invoice_number, i.student_name, i.student_email, i.student_phone, i.student_country,
                i.currency, i.subtotal, i.discount, i.vat_percent, i.vat_amount, i.grand_total,
                i.amount_paid, i.amount_due,
                i.status, i.invoice_date, i.due_date, i.notes, i.show_signature,
                i.created_by, i.created_at, i.updated_at, i.deleted_at,
                {$deletedByCol},
                u.username AS creator_name, u.email AS creator_email
            FROM invoices i
            LEFT JOIN users u ON i.created_by = u.id
            WHERE i.id = :id AND i.deleted_at IS NULL
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
     * Find an invoice by invoice number including soft-deleted records for tracking purposes.
     * Joins both creator and deleter account details.
     *
     * @param string $invoiceNumber
     * @return array|null ['invoice' => [...], 'items' => [...]]
     */
    public function findByInvoiceNumberWithAudit(string $invoiceNumber): ?array
    {
        $hasDeletedBy = $this->hasDeletedByColumn();
        $deletedByCol = $hasDeletedBy ? 'i.deleted_by' : 'NULL AS deleted_by';
        $deleterJoin  = $hasDeletedBy ? 'LEFT JOIN users ud ON i.deleted_by = ud.id' : 'LEFT JOIN users ud ON 1=0';

        $stmt = $this->db->prepare("
            SELECT
                i.id, i.invoice_number, i.student_name, i.student_email, i.student_phone, i.student_country,
                i.currency, i.subtotal, i.discount, i.vat_percent, i.vat_amount, i.grand_total,
                i.amount_paid, i.amount_due,
                i.status, i.invoice_date, i.due_date, i.notes, i.show_signature,
                i.created_by, i.created_at, i.updated_at, i.deleted_at,
                {$deletedByCol},
                uc.username AS creator_name, uc.email AS creator_email,
                ud.username AS deleter_name, ud.email AS deleter_email
            FROM invoices i
            LEFT JOIN users uc ON i.created_by = uc.id
            {$deleterJoin}
            WHERE i.invoice_number = :invoice_number
            LIMIT 1
        ");
        $stmt->execute(['invoice_number' => $invoiceNumber]);
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
        $itemStmt->execute(['invoice_id' => (int) $invoice['id']]);
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
     * Paginated list of all invoices including soft-deleted ones for the Super Admin tracking view.
     * Joins creator and deleter user info.
     *
     * @param array $filters
     * @param int   $limit
     * @param int   $offset
     * @return array
     */
    public function findAllIncludingDeleted(array $filters, int $limit, int $offset): array
    {
        [$whereClause, $params] = $this->buildTrackWhereClause($filters);
        $hasDeletedBy = $this->hasDeletedByColumn();

        $deletedByCol = $hasDeletedBy ? 'i.deleted_by' : 'NULL AS deleted_by';
        $deleterJoin  = $hasDeletedBy ? 'LEFT JOIN users ud ON i.deleted_by = ud.id' : 'LEFT JOIN users ud ON 1=0';

        $stmt = $this->db->prepare("
            SELECT
                i.id, i.invoice_number, i.student_name, i.student_email,
                i.currency, i.grand_total, i.amount_paid, i.amount_due,
                i.status, i.invoice_date, i.created_at, i.deleted_at, i.created_by,
                {$deletedByCol},
                uc.username AS creator_name, uc.email AS creator_email,
                ud.username AS deleter_name, ud.email AS deleter_email
            FROM invoices i
            LEFT JOIN users uc ON i.created_by = uc.id
            {$deleterJoin}
            {$whereClause}
            ORDER BY i.created_at DESC
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
     * Count total invoices for tracking view matching filters.
     *
     * @param array $filters
     * @return int
     */
    public function countAllIncludingDeleted(array $filters): int
    {
        [$whereClause, $params] = $this->buildTrackWhereClause($filters);

        $stmt = $this->db->prepare("
            SELECT COUNT(i.id) FROM invoices i {$whereClause}
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
     * Looks up highest sequence in the current period to prevent sequence gaps or collisions.
     * Format tokens: {PREFIX} {YEAR} {MONTH} {DAY} {SEQ4} {SEQ6}
     *
     * @param array $settings Result of getSettings()
     * @param int   $offset   Optional offset to advance sequence (e.g. for collision retries)
     * @return string e.g. INV2506040001
     */
    public function generateInvoiceNumber(array $settings, int $offset = 0): string
    {
        $prefix  = $settings['invoice_prefix']        ?? 'INV';
        $format  = $settings['invoice_number_format'] ?? '{PREFIX}{YEAR}{MONTH}{SEQ4}';
        $year    = date('Y');
        $month   = date('m');
        $day     = date('d');

        // Extract prefix before sequence token
        $seqTokenPos = strpos($format, '{SEQ');
        $patternPrefix = ($seqTokenPos !== false) ? substr($format, 0, $seqTokenPos) : '{PREFIX}{YEAR}{MONTH}';
        $resolvedPrefix = str_replace(
            ['{PREFIX}', '{YEAR}', '{MONTH}', '{DAY}'],
            [$prefix,    $year,    $month,    $day],
            $patternPrefix
        );

        $stmt = $this->db->prepare("
            SELECT invoice_number 
            FROM invoices 
            WHERE invoice_number LIKE :prefixPattern 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute(['prefixPattern' => $resolvedPrefix . '%']);
        $lastInvoice = $stmt->fetchColumn();

        $seq = 1;
        if ($lastInvoice && preg_match('/(\d+)$/', $lastInvoice, $matches)) {
            $seq = ((int) $matches[1]) + 1;
        } else {
            $seqStmt = $this->db->prepare("
                SELECT COUNT(id) + 1 AS next_seq
                FROM invoices
                WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month
            ");
            $seqStmt->execute(['year' => (int) $year, 'month' => (int) $month]);
            $seq = (int) $seqStmt->fetchColumn();
        }

        $seq += $offset;

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

    /**
     * Build WHERE clause for the Super Admin tracking view.
     * Supports filtering by deletion status: 'all', 'active', 'deleted'.
     *
     * @param array $filters
     * @return array [string $whereClause, array $params]
     */
    private function buildTrackWhereClause(array $filters): array
    {
        $conditions = [];
        $params     = [];

        $deletionStatus = $filters['deletion_status'] ?? 'all';
        if ($deletionStatus === 'active') {
            $conditions[] = 'i.deleted_at IS NULL';
        } elseif ($deletionStatus === 'deleted') {
            $conditions[] = 'i.deleted_at IS NOT NULL';
        }

        if (!empty($filters['status']) && in_array($filters['status'], self::ALLOWED_STATUSES, true)) {
            $conditions[] = 'i.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = 'i.invoice_date >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'i.invoice_date <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(i.student_name LIKE :search OR i.invoice_number LIKE :search_exact)';
            $params[':search']       = '%' . $filters['search'] . '%';
            $params[':search_exact'] = $filters['search'] . '%';
        }

        $whereClause = !empty($conditions) ? ('WHERE ' . implode(' AND ', $conditions)) : '';
        return [$whereClause, $params];
    }

    // ===========================================================
    // CURRENCY MANAGEMENT
    // ===========================================================

    /**
     * Get all currencies from the database.
     * Returned format matches the old static array:
     * [ 'CODE' => ['name' => '...', 'symbol' => '...', 'flag' => '...'] ]
     *
     * @return array
     */
    public function getCurrencies(): array
    {
        try {
            $stmt = $this->db->query("SELECT code, name, symbol, flag FROM invoice_currencies ORDER BY code ASC");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $currencies = [];
            foreach ($results as $row) {
                $currencies[$row['code']] = [
                    'name'   => $row['name'],
                    'symbol' => $row['symbol'],
                    'flag'   => $row['flag']
                ];
            }
            
            return $currencies;
        } catch (PDOException $e) {
            error_log('[InvoiceRepository] getCurrencies failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Add or update a currency in the database.
     *
     * @param array $data ['code', 'name', 'symbol', 'flag']
     * @return bool
     */
    public function addCurrency(array $data): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO invoice_currencies (code, name, symbol, flag) 
                VALUES (:code, :name, :symbol, :flag)
                ON DUPLICATE KEY UPDATE 
                name = VALUES(name), symbol = VALUES(symbol), flag = VALUES(flag)
            ");
            
            return $stmt->execute([
                ':code'   => strtoupper(trim($data['code'])),
                ':name'   => trim($data['name']),
                ':symbol' => trim($data['symbol']),
                ':flag'   => trim($data['flag'])
            ]);
        } catch (PDOException $e) {
            error_log('[InvoiceRepository] addCurrency failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a currency from the database.
     *
     * @param string $code
     * @return bool
     */
    public function deleteCurrency(string $code): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM invoice_currencies WHERE code = :code");
            return $stmt->execute([':code' => strtoupper(trim($code))]);
        } catch (PDOException $e) {
            error_log('[InvoiceRepository] deleteCurrency failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve invoice history for a specific student by email.
     *
     * @param string $studentEmail
     * @param int $limit
     * @return array
     */
    public function findByStudentEmail(string $studentEmail, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT id, invoice_number, student_name, student_email, student_phone, student_country,
                   currency, subtotal, discount, vat_percent, vat_amount, grand_total,
                   amount_paid, amount_due, status, invoice_date, due_date, created_at
            FROM invoices
            WHERE student_email = :student_email
              AND deleted_at IS NULL
            ORDER BY invoice_date DESC, id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':student_email', trim($studentEmail));
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve lifetime invoice financial summary for a student by email.
     *
     * @param string $studentEmail
     * @return array{total_invoices: int, total_billed: float, total_paid: float, total_due: float}
     */
    public function getSummaryByStudentEmail(string $studentEmail): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(id) AS total_invoices,
                COALESCE(SUM(grand_total), 0) AS total_billed,
                COALESCE(SUM(amount_paid), 0) AS total_paid,
                COALESCE(SUM(amount_due), 0) AS total_due
            FROM invoices
            WHERE student_email = :student_email
              AND deleted_at IS NULL
        ");
        $stmt->bindValue(':student_email', trim($studentEmail));
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_invoices' => (int) ($row['total_invoices'] ?? 0),
            'total_billed'   => (float) ($row['total_billed'] ?? 0),
            'total_paid'     => (float) ($row['total_paid'] ?? 0),
            'total_due'      => (float) ($row['total_due'] ?? 0),
        ];
    }
}
