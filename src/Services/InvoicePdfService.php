<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\InvoicePdfServiceInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

/**
 * InvoicePdfService
 *
 * Enterprise in-memory vector PDF generator using Dompdf.
 * Renders invoices cleanly without writing temporary files to disk.
 */
class InvoicePdfService implements InvoicePdfServiceInterface
{
    private string $publicDir;

    public function __construct(?string $publicDir = null)
    {
        $this->publicDir = $publicDir ?? realpath(__DIR__ . '/../../public') ?: (__DIR__ . '/../../public');
    }

    /**
     * Generate in-memory PDF binary string for a given invoice.
     *
     * @param array $invoice Master invoice record from DB
     * @param array $items Line items for the invoice
     * @param array $settings Institution / invoice settings
     * @param string|null $creatorName Name of creator/admin for audit bar
     * @return string Raw PDF binary string
     * @throws \RuntimeException
     */
    public function generatePdf(array $invoice, array $items, array $settings, ?string $creatorName = null): string
    {
        // 1. Resolve images to safe Base64 Data URIs (avoids remote HTTP fetch and disk leak)
        $logoBase64      = $this->resolveImageAsBase64($settings['institution_logo'] ?? null);
        $signatureBase64 = $this->resolveImageAsBase64($settings['invoice_signature'] ?? null);
        $watermarkBase64 = $logoBase64; // Use institution logo as watermark if present

        // 2. Render Template to HTML
        ob_start();
        try {
            require __DIR__ . '/../../views/admin/invoices/pdf_template.php';
            $html = ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            error_log('[InvoicePdfService] Template error: ' . $e->getMessage());
            throw new \RuntimeException('Failed to render invoice PDF template: ' . $e->getMessage(), 0, $e);
        }

        // 3. Configure Dompdf with strict security & performance options
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);          // Disable PHP execution inside templates
        $options->set('isRemoteEnabled', false);       // Prevent SSRF; all assets embedded as Base64
        $options->set('defaultFont', 'Helvetica');
        $options->set('dpi', 150);

        try {
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $output = $dompdf->output();
            if ($output === null || $output === '') {
                throw new \RuntimeException('Dompdf produced empty output.');
            }

            return $output;
        } catch (Throwable $e) {
            error_log('[InvoicePdfService] Dompdf render error: ' . $e->getMessage());
            throw new \RuntimeException('Failed to generate PDF document: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Convert a relative public image path to a base64 Data URI.
     *
     * @param string|null $relativePath e.g. '/uploads/logo.png'
     * @return string|null Data URI 'data:image/png;base64,...' or null if file absent
     */
    private function resolveImageAsBase64(?string $relativePath): ?string
    {
        if (empty($relativePath)) {
            return null;
        }

        // Normalize leading slash
        $trimmed = ltrim($relativePath, '/\\');
        $filePath = $this->publicDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $trimmed);

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        // Check file size (ignore files larger than 4MB)
        if (filesize($filePath) > 4 * 1024 * 1024) {
            return null;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            'gif'         => 'image/gif',
            'svg'         => 'image/svg+xml',
            default       => null,
        };

        if ($mime === null) {
            return null;
        }

        $data = @file_get_contents($filePath);
        if ($data === false || $data === '') {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}

