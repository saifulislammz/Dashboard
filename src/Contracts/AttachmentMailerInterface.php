<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * AttachmentMailerInterface
 *
 * Contract for mailing services that support file/string attachments and BCC.
 * Extends MailerInterface to guarantee 100% backward compatibility for existing callers.
 */
interface AttachmentMailerInterface extends MailerInterface
{
    /**
     * Send an email message with attachments and optional BCC recipient.
     *
     * @param string $to Recipient email address
     * @param string $subject Subject line
     * @param string $htmlBody HTML body content
     * @param string $textBody Plain-text fallback content
     * @param string|null $recipientName Optional recipient display name
     * @param array $attachments Array of attachments:
     *   [
     *     ['type' => 'string', 'data' => string, 'name' => string, 'mime' => 'application/pdf', 'encoding' => 'base64'],
     *     ['type' => 'file',   'path' => string, 'name' => string, 'mime' => 'application/pdf']
     *   ]
     * @param string|null $bcc Optional BCC email address
     * @return bool True if sent successfully, false otherwise
     */
    public function sendWithAttachment(
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody = '',
        ?string $recipientName = null,
        array $attachments = [],
        ?string $bcc = null
    ): bool;
}

