<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * MailerInterface
 *
 * Contract for mailing services across the application.
 * Allows switching between PHPMailer, Symfony Mailer, or Mock mailer for tests.
 */
interface MailerInterface
{
    /**
     * Send an email message.
     *
     * @param string $to Recipient email address
     * @param string $subject Subject line
     * @param string $htmlBody HTML body content
     * @param string $textBody Plain-text fallback content
     * @param string|null $recipientName Optional recipient display name
     * @return bool True if sent successfully, false otherwise
     */
    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody = '',
        ?string $recipientName = null
    ): bool;
}

