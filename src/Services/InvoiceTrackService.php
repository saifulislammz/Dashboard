<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\InvoiceRepository;
use App\Repositories\InvoiceActivityRepository;

/**
 * InvoiceTrackService
 *
 * Dedicated business logic layer for Super Admin invoice tracking,
 * lifecycle inspection, account information aggregation, and restoration.
 */
class InvoiceTrackService
{
    private InvoiceRepository $invoiceRepo;
    private InvoiceActivityRepository $activityRepo;

    private const PER_PAGE = 15;

    public function __construct(InvoiceRepository $invoiceRepo, InvoiceActivityRepository $activityRepo)
    {
        $this->invoiceRepo = $invoiceRepo;
        $this->activityRepo = $activityRepo;
    }

    /**
     * Retrieve complete tracking and audit dossier for an invoice by its invoice number.
     * Looks up both active and soft-deleted invoices.
     *
     * @param string $invoiceNumber
     * @return array|null
     */
    public function getTrackingData(string $invoiceNumber): ?array
    {
        $invoiceNumber = trim($invoiceNumber);
        if ($invoiceNumber === '') {
            return null;
        }

        $data = $this->invoiceRepo->findByInvoiceNumberWithAudit($invoiceNumber);
        if ($data === null) {
            return null;
        }

        $activities = $this->activityRepo->getActivitiesByInvoiceNumber($invoiceNumber);

        return [
            'invoice'    => $data['invoice'],
            'items'      => $data['items'],
            'activities' => $activities,
            'is_deleted' => !empty($data['invoice']['deleted_at']),
        ];
    }

    /**
     * Get paginated invoice list for the Super Admin tracking dashboard.
     * Includes both active and soft-deleted records with creator and deleter metadata.
     *
     * @param array $filters
     * @param int   $page
     * @return array
     */
    public function getTrackDashboardData(array $filters, int $page = 1): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;

        $sanitizedFilters = [
            'search'          => trim($filters['search'] ?? ''),
            'deletion_status' => in_array($filters['deletion_status'] ?? '', ['all', 'active', 'deleted'], true)
                                    ? $filters['deletion_status']
                                    : 'all',
            'status'          => trim($filters['status'] ?? ''),
            'date_from'       => trim($filters['date_from'] ?? ''),
            'date_to'         => trim($filters['date_to'] ?? ''),
        ];

        $invoices = $this->invoiceRepo->findAllIncludingDeleted($sanitizedFilters, self::PER_PAGE, $offset);
        $total    = $this->invoiceRepo->countAllIncludingDeleted($sanitizedFilters);
        $pages    = (int) ceil($total / self::PER_PAGE);

        $recentActivities = $this->activityRepo->getRecentActivities(8);

        return [
            'invoices'          => $invoices,
            'total'             => $total,
            'pages'             => $pages,
            'currentPage'       => $page,
            'perPage'           => self::PER_PAGE,
            'filters'           => $sanitizedFilters,
            'recentActivities'  => $recentActivities,
        ];
    }

    /**
     * Restore a soft-deleted invoice back to active status.
     *
     * @param int $id
     * @param int $actorId User ID of the Super Admin performing restoration
     * @return array ['success' => bool, 'message' => string]
     */
    public function restoreInvoice(int $id, int $actorId): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid invoice ID.'];
        }

        $stmt = $this->invoiceRepo->findByInvoiceNumberWithAudit('');
        // To find the invoice number, find by ID using raw query or retrieve via findByInvoiceNumberWithAudit
        $db = $GLOBALS['db'] ?? null;
        if (!$db instanceof \PDO) {
            return ['success' => false, 'message' => 'Database connection unavailable.'];
        }

        $invStmt = $db->prepare("SELECT id, invoice_number, deleted_at FROM invoices WHERE id = :id LIMIT 1");
        $invStmt->execute(['id' => $id]);
        $row = $invStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return ['success' => false, 'message' => 'Invoice not found.'];
        }

        if (empty($row['deleted_at'])) {
            return ['success' => false, 'message' => 'Invoice is already active and not deleted.'];
        }

        $ok = $this->invoiceRepo->restore($id);
        if (!$ok) {
            return ['success' => false, 'message' => 'Failed to restore invoice.'];
        }

        $this->activityRepo->logActivity(
            $id,
            $row['invoice_number'],
            $actorId,
            'RESTORED',
            "Invoice {$row['invoice_number']} restored to active status by Super Admin",
            ['restored_by_user_id' => $actorId]
        );

        return ['success' => true, 'message' => "Invoice {$row['invoice_number']} has been successfully restored."];
    }
}

