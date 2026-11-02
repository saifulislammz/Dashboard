<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\UserProfileService;
use App\Contracts\UserProfilePdfServiceInterface;
use App\Repositories\InvoiceRepository;
use Delight\Auth\Auth;
use Throwable;

/**
 * AdminUserProfileController — Thin Controller
 * Handles Super Admin 360° Profile Hub and One-Click PDF Report Generation.
 * Adheres strictly to MVC: No raw SQL in this controller.
 */
class AdminUserProfileController
{
    private UserProfileService             $profileService;
    private UserProfilePdfServiceInterface $pdfService;
    private InvoiceRepository              $invoiceRepository;
    private Auth                           $auth;

    public function __construct(
        UserProfileService             $profileService,
        UserProfilePdfServiceInterface $pdfService,
        InvoiceRepository              $invoiceRepository,
        Auth                           $auth
    ) {
        $this->profileService    = $profileService;
        $this->pdfService        = $pdfService;
        $this->invoiceRepository = $invoiceRepository;
        $this->auth              = $auth;
    }

    /**
     * Render the 360° Profile Dashboard Hub for a specific user.
     *
     * @param int $userId
     * @return void
     */
    public function view(int $userId): void
    {
        if ($userId <= 0) {
            $_SESSION['error_message'] = 'Invalid user ID specified.';
            header("Location: /admin/dashboard.php");
            exit;
        }

        try {
            $data = $this->profileService->getCompleteUserProfile($userId);
            $auth = $this->auth;

            require __DIR__ . '/../../../views/admin/users/view.php';
        } catch (Throwable $e) {
            $_SESSION['error_message'] = 'User profile not found: ' . $e->getMessage();
            header("Location: /admin/dashboard.php");
            exit;
        }
    }

    /**
     * Generate and stream a one-click vector PDF profile report.
     *
     * @param int $userId
     * @return void
     */
    public function downloadPdf(int $userId): void
    {
        if ($userId <= 0) {
            $_SESSION['error_message'] = 'Invalid user ID specified.';
            header("Location: /admin/dashboard.php");
            exit;
        }

        try {
            $profileData = $this->profileService->getCompleteUserProfile($userId);
            $settings    = $this->invoiceRepository->getSettings();
            $adminName   = $this->auth->getUsername() ?: 'Administrator';

            $pdfBinary = $this->pdfService->generateProfilePdf($profileData, $settings, $adminName);

            $cleanUsername = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($profileData['user']['username'] ?? 'User'));
            $filename = sprintf('Profile_Report_%s_%d.pdf', $cleanUsername, $userId);

            // Stream response
            if (ob_get_level()) {
                ob_end_clean();
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . (string) strlen($pdfBinary));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');

            echo $pdfBinary;
            exit;
        } catch (Throwable $e) {
            error_log('[AdminUserProfileController] PDF export failure: ' . $e->getMessage());
            $_SESSION['error_message'] = 'Failed to generate PDF report: ' . $e->getMessage();
            header("Location: /admin/users/view.php?id=" . $userId);
            exit;
        }
    }
}

