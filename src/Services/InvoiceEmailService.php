<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AttachmentMailerInterface;
use App\Contracts\InvoicePdfServiceInterface;
use App\Repositories\InvoiceRepository;
use App\Repositories\InvoiceActivityRepository;
use App\Repositories\InvoiceEmailLogRepository;
use Throwable;

/**
 * InvoiceEmailService
 *
 * Dedicated service orchestrating manual and automatic invoice email dispatches.
 * Generates vector PDF in-memory, renders branded HTML emails, sends via SMTP,
 * and maintains complete audit trails.
 */
class InvoiceEmailService
{
    private InvoiceRepository $invoiceRepo;
    private AttachmentMailerInterface $mailer;
    private InvoicePdfServiceInterface $pdfService;
    private ?InvoiceEmailLogRepository $emailLogRepo;
    private ?InvoiceActivityRepository $activityRepo;

    public function __construct(
        InvoiceRepository $invoiceRepo,
        AttachmentMailerInterface $mailer,
        InvoicePdfServiceInterface $pdfService,
        ?InvoiceEmailLogRepository $emailLogRepo = null,
        ?InvoiceActivityRepository $activityRepo = null
    ) {
        $this->invoiceRepo  = $invoiceRepo;
        $this->mailer       = $mailer;
        $this->pdfService   = $pdfService;
        $this->emailLogRepo = $emailLogRepo;
        $this->activityRepo = $activityRepo;
    }

    /**
     * Send an invoice email with attached vector PDF.
     *
     * @param int         $invoiceId     Invoice ID
     * @param string|null $overrideEmail Optional destination email override
     * @param int|null    $actorId       ID of user triggering send (null if system auto)
     * @param string      $triggerType   'manual' or 'automatic'
     * @return array      ['success' => bool, 'message' => string, 'errors' => array]
     */
    public function sendInvoiceEmail(
        int $invoiceId,
        ?string $overrideEmail = null,
        ?int $actorId = null,
        string $triggerType = 'manual'
    ): array {
        if ($invoiceId <= 0) {
            return ['success' => false, 'message' => 'Invalid invoice ID.', 'errors' => ['id' => 'Invalid ID']];
        }

        // 1. Fetch invoice and line items
        $data = $this->invoiceRepo->findById($invoiceId);
        if ($data === null || empty($data['invoice'])) {
            return ['success' => false, 'message' => 'Invoice not found.', 'errors' => ['invoice' => 'Not found']];
        }

        $invoice  = $data['invoice'];
        $items    = $data['items'] ?? [];
        $settings = $this->invoiceRepo->getSettings();

        // 2. Resolve destination email
        $recipientEmail = trim($overrideEmail !== null && $overrideEmail !== '' ? $overrideEmail : ($invoice['student_email'] ?? ''));

        if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Recipient email address is missing or invalid.';
            if ($this->emailLogRepo !== null) {
                $this->emailLogRepo->logAttempt(
                    $invoiceId,
                    $invoice['invoice_number'],
                    $recipientEmail ?: 'N/A',
                    $triggerType,
                    'failed',
                    $actorId,
                    $msg
                );
            }
            return ['success' => false, 'message' => $msg, 'errors' => ['email' => $msg]];
        }

        // 3. Compile Subject
        $subjectTemplate = !empty($settings['invoice_email_subject'])
            ? $settings['invoice_email_subject']
            : 'Invoice #{invoice_number} from {institution_name}';

        $subject = str_replace(
            ['{invoice_number}', '{institution_name}', '{student_name}', '{currency}', '{grand_total}'],
            [
                $invoice['invoice_number'],
                $settings['institution_name'] ?? 'Rahe Nazat Institute',
                $invoice['student_name'] ?? 'Customer',
                $invoice['currency'] ?? 'BDT',
                number_format((float) ($invoice['grand_total'] ?? 0), 2)
            ],
            $subjectTemplate
        );

        // Sanitize Subject against CRLF injection
        $subject = str_replace(["\r", "\n"], '', $subject);

        // 4. Generate in-memory PDF
        try {
            $pdfBinary = $this->pdfService->generatePdf(
                $invoice,
                $items,
                $settings,
                $actorId !== null ? 'Admin #' . $actorId : 'System Generator'
            );
        } catch (Throwable $e) {
            $errorMsg = 'PDF Generation failed: ' . $e->getMessage();
            error_log('[InvoiceEmailService] ' . $errorMsg);

            if ($this->emailLogRepo !== null) {
                $this->emailLogRepo->logAttempt(
                    $invoiceId,
                    $invoice['invoice_number'],
                    $recipientEmail,
                    $triggerType,
                    'failed',
                    $actorId,
                    $errorMsg
                );
            }
            return ['success' => false, 'message' => 'Failed to generate invoice PDF. Please try again.', 'errors' => ['pdf' => $errorMsg]];
        }

        // 5. Compile Email HTML and Plain-Text Body
        $customNotes = $settings['invoice_email_custom_notes'] ?? '';

        ob_start();
        require __DIR__ . '/../../views/emails/invoice_email_html.php';
        $htmlBody = (string) ob_get_clean();

        ob_start();
        require __DIR__ . '/../../views/emails/invoice_email_text.php';
        $textBody = (string) ob_get_clean();

        // 6. Prepare Attachments
        $pdfFileName = 'Invoice-' . preg_replace('/[^A-Za-z0-9_-]/', '', $invoice['invoice_number']) . '.pdf';
        $attachments = [
            [
                'data'     => $pdfBinary,
                'name'     => $pdfFileName,
                'mime'     => 'application/pdf',
                'encoding' => 'base64',
            ]
        ];

        // 7. Resolve BCC
        $bcc = !empty($settings['invoice_email_bcc']) ? trim($settings['invoice_email_bcc']) : null;
        if ($bcc !== null && !filter_var($bcc, FILTER_VALIDATE_EMAIL)) {
            $bcc = null;
        }

        // 8. Dispatch Email
        $sent = $this->mailer->sendWithAttachment(
            $recipientEmail,
            $subject,
            $htmlBody,
            $textBody,
            $invoice['student_name'] ?? null,
            $attachments,
            $bcc
        );

        if ($sent) {
            // Log success in email logs
            if ($this->emailLogRepo !== null) {
                $this->emailLogRepo->logAttempt(
                    $invoiceId,
                    $invoice['invoice_number'],
                    $recipientEmail,
                    $triggerType,
                    'sent',
                    $actorId,
                    null
                );
            }

            // Log activity in invoice_activities
            if ($this->activityRepo !== null) {
                $this->activityRepo->logActivity(
                    $invoiceId,
                    $invoice['invoice_number'],
                    $actorId,
                    'EMAIL_SENT',
                    "Invoice {$invoice['invoice_number']} sent to {$recipientEmail} ({$triggerType})",
                    [
                        'recipient'    => $recipientEmail,
                        'trigger_type' => $triggerType,
                        'bcc'          => $bcc,
                        'pdf_file'     => $pdfFileName,
                    ]
                );
            }

            return [
                'success' => true,
                'message' => "Invoice PDF sent successfully to {$recipientEmail}.",
                'errors'  => []
            ];
        }

        // Delivery failed
        $errMsg = 'SMTP transport failure. Could not deliver message to ' . $recipientEmail;
        if ($this->emailLogRepo !== null) {
            $this->emailLogRepo->logAttempt(
                $invoiceId,
                $invoice['invoice_number'],
                $recipientEmail,
                $triggerType,
                'failed',
                $actorId,
                $errMsg
            );
        }

        return [
            'success' => false,
            'message' => 'Failed to send email. Please verify SMTP settings and recipient address.',
            'errors'  => ['smtp' => $errMsg]
        ];
    }

    /**
     * Trigger automatic email sending if enabled in settings.
     * Wrapped in defensive try-catch so it never disrupts invoice creation.
     */
    public function triggerAutoEmailIfEnabled(int $invoiceId, ?int $adminId = null): void
    {
        try {
            $settings = $this->invoiceRepo->getSettings();
            $autoEnabled = ($settings['invoice_auto_email_enabled'] ?? '0') === '1';

            if (!$autoEnabled) {
                return;
            }

            $this->sendInvoiceEmail($invoiceId, null, $adminId, 'automatic');
        } catch (Throwable $e) {
            error_log('[InvoiceEmailService] Auto-email exception: ' . $e->getMessage());
        }
    }
}

