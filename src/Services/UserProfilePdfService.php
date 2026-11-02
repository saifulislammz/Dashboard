<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\UserProfilePdfServiceInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;
use Throwable;

/**
 * UserProfilePdfService
 *
 * Enterprise in-memory vector PDF generator for User Profile Reports.
 * Renders user credentials, contact details, assigned classrooms, and invoice histories cleanly
 * without writing temporary files to disk, enforcing strict SSRF and memory protections.
 */
class UserProfilePdfService implements UserProfilePdfServiceInterface
{
    private string $publicDir;

    public function __construct(?string $publicDir = null)
    {
        $this->publicDir = $publicDir ?? realpath(__DIR__ . '/../../public') ?: (__DIR__ . '/../../public');
    }

    /**
     * Generate in-memory PDF binary string for a comprehensive user profile report.
     *
     * @param array $profileData Complete 360° aggregated profile array
     * @param array $settings Optional institution settings (logo, name, etc.)
     * @param string|null $adminName Name of the administrator generating the report
     * @return string Raw PDF binary string
     * @throws RuntimeException
     */
    public function generateProfilePdf(array $profileData, array $settings = [], ?string $adminName = null): string
    {
        // 1. Resolve avatar and logo images to safe Base64 Data URIs (prevents SSRF and disk leaks)
        $avatarRelativePath = !empty($profileData['user']['profile_picture'])
            ? '/uploads/avatars/' . basename($profileData['user']['profile_picture'])
            : null;
        $avatarBase64 = $this->resolveImageAsBase64($avatarRelativePath);

        $logoRelativePath = $settings['institution_logo'] ?? '/images/logo.png';
        $logoBase64 = $this->resolveImageAsBase64($logoRelativePath);

        $generatedAt = date('d M Y, h:i A');

        // 2. Render Template to HTML buffer
        ob_start();
        try {
            $user           = $profileData['user'] ?? [];
            $profile        = $profileData['profile'] ?? [];
            $roleLabel      = $profileData['role_label'] ?? 'User';
            $statusLabel    = $profileData['status_label'] ?? 'Active';
            $classrooms     = $profileData['classrooms'] ?? [];
            $invoices       = $profileData['invoices'] ?? [];
            $invoiceSummary = $profileData['invoice_summary'] ?? [];

            require __DIR__ . '/../../views/admin/users/pdf_profile_template.php';
            $html = ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            error_log('[UserProfilePdfService] Template rendering error: ' . $e->getMessage());
            throw new RuntimeException('Failed to render user profile PDF template: ' . $e->getMessage(), 0, $e);
        }

        // 3. Configure Dompdf with strict security & performance options
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);          // Disable PHP execution inside templates
        $options->set('isRemoteEnabled', false);       // Prevent SSRF; all assets embedded as Base64 Data URIs
        $options->set('defaultFont', 'Helvetica');
        $options->set('dpi', 150);

        try {
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $output = $dompdf->output();
            if ($output === null || $output === '') {
                throw new RuntimeException('Dompdf produced empty output.');
            }

            return $output;
        } catch (Throwable $e) {
            error_log('[UserProfilePdfService] Dompdf render error: ' . $e->getMessage());
            throw new RuntimeException('Failed to generate PDF document: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Convert a relative public image path to a base64 Data URI.
     *
     * @param string|null $relativePath e.g. '/uploads/avatars/avatar_1.png'
     * @return string|null Data URI 'data:image/...;base64,...' or null if file absent
     */
    private function resolveImageAsBase64(?string $relativePath): ?string
    {
        if (empty($relativePath)) {
            return null;
        }

        $trimmed = ltrim($relativePath, '/\\');
        $filePath = $this->publicDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $trimmed);

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeMap = [
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
        ];
        $mimeType = $mimeMap[$extension] ?? 'image/png';

        $data = @file_get_contents($filePath);
        if ($data === false) {
            return null;
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($data);
    }
}

