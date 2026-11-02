<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\InvoiceTrackService;
use Delight\Auth\Auth;

/**
 * InvoiceTrackController (Super Admin)
 *
 * Dedicated controller for Super Admin invoice tracking and audit inspection.
 * Enforces strict access control and handles search, lifecycle views, and restoration.
 */
class InvoiceTrackController
{
    private InvoiceTrackService $trackService;
    private Auth $auth;

    public function __construct(InvoiceTrackService $trackService, Auth $auth)
    {
        $this->trackService = $trackService;
        $this->auth = $auth;
    }

    /**
     * Render the Invoice Track dashboard or individual invoice tracking view.
     * GET /admin/invoices/track.php
     */
    public function index(): void
    {
        $searchQuery = trim($_GET['search'] ?? '');
        $page        = max(1, (int) ($_GET['page'] ?? 1));

        $trackingData = null;
        $searchPerformed = false;

        if ($searchQuery !== '') {
            $searchPerformed = true;
            $trackingData = $this->trackService->getTrackingData($searchQuery);
        }

        $filters = [
            'search'          => $searchQuery,
            'deletion_status' => $_GET['deletion_status'] ?? 'all',
            'status'          => $_GET['status'] ?? '',
            'date_from'       => $_GET['date_from'] ?? '',
            'date_to'         => $_GET['date_to'] ?? '',
        ];

        $listData = $this->trackService->getTrackDashboardData($filters, $page);

        $pageTitle  = 'Invoice Tracking & Audit';
        $activeMenu = 'invoice_track';

        require __DIR__ . '/../../../views/admin/invoices/track.php';
    }

    /**
     * Restore a soft-deleted invoice back to active status.
     * POST /admin/invoices/track.php (action=restore)
     */
    public function restore(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $searchParam = urlencode(trim($_POST['search_query'] ?? ''));

        if ($id <= 0) {
            header('Location: /admin/invoices/track.php?error=invalid_id');
            exit;
        }

        $actorId = (int) $this->auth->getUserId();
        $result = $this->trackService->restoreInvoice($id, $actorId);

        if ($result['success']) {
            $redirectUrl = '/admin/invoices/track.php?restored=1' . ($searchParam !== '' ? ('&search=' . $searchParam) : '');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $redirectUrl = '/admin/invoices/track.php?error=' . urlencode($result['message']) . ($searchParam !== '' ? ('&search=' . $searchParam) : '');
        header('Location: ' . $redirectUrl);
        exit;
    }
}

