<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Contracts\MailerInterface;
use App\Contracts\AttachmentMailerInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Throwable;

/**
 * PhpMailerService
 *
 * Enterprise-grade SMTP mailing implementation powered by PHPMailer.
 * Configured via .env variables with secure defaults and robust error logging.
 */
class PhpMailerService implements AttachmentMailerInterface
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private ?string $encryption;
    private string $fromAddress;
    private string $fromName;
    private string $driver;

    public function __construct(
        ?string $host = null,
        ?int $port = null,
        ?string $username = null,
        ?string $password = null,
        ?string $encryption = null,
        ?string $fromAddress = null,
        ?string $fromName = null,
        ?string $driver = null
    ) {
        $this->driver      = $driver ?? ($_ENV['MAIL_DRIVER'] ?? getenv('MAIL_DRIVER') ?: 'smtp');
        $this->host        = $host ?? ($_ENV['MAIL_HOST'] ?? getenv('MAIL_HOST') ?: '127.0.0.1');
        $this->port        = $port ?? (int) ($_ENV['MAIL_PORT'] ?? getenv('MAIL_PORT') ?: 1025);
        $this->username    = $username ?? ($_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME') ?: '');
        $this->password    = $password ?? ($_ENV['MAIL_PASSWORD'] ?? getenv('MAIL_PASSWORD') ?: '');
        $this->encryption  = $encryption ?? ($_ENV['MAIL_ENCRYPTION'] ?? getenv('MAIL_ENCRYPTION') ?: null);
        $this->fromAddress = $fromAddress ?? ($_ENV['MAIL_FROM_ADDRESS'] ?? getenv('MAIL_FROM_ADDRESS') ?: 'noreply@rahenazat.org');
        $this->fromName    = $fromName ?? ($_ENV['MAIL_FROM_NAME'] ?? getenv('MAIL_FROM_NAME') ?: 'Rahe Nazat Institute');
    }

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
    ): bool {
        // Fallback to PHP native mail() if driver is explicitly set to 'mail'
        if (strtolower($this->driver) === 'mail') {
            return $this->sendNativeMail($to, $subject, $htmlBody, $textBody);
        }

        $mail = new PHPMailer(true);

        try {
            // Server configuration
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->Port       = $this->port;
            $mail->CharSet    = PHPMailer::CHARSET_UTF8;
            $mail->SMTPAutoTLS = true;

            // Authentication
            if (!empty($this->username)) {
                $mail->SMTPAuth = true;
                $mail->Username = $this->username;
                $mail->Password = $this->password;
            } else {
                $mail->SMTPAuth = false;
            }

            // Encryption (tls, ssl, or none)
            if (!empty($this->encryption)) {
                $enc = strtolower($this->encryption);
                if ($enc === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } elseif ($enc === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                }
            }

            // Timeout prevention
            $mail->Timeout = 10;

            // Recipients
            $mail->setFrom($this->fromAddress, $this->fromName);
            $mail->addAddress($to, $recipientName ?? '');

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = !empty($textBody) ? $textBody : strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log(sprintf('[PhpMailerService] PHPMailer Error to %s: %s', $to, $e->getMessage()));
            return false;
        } catch (Throwable $e) {
            error_log(sprintf('[PhpMailerService] General Error to %s: %s', $to, $e->getMessage()));
            return false;
        }
    }

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
     *     ['data' => string, 'name' => string, 'mime' => 'application/pdf', 'encoding' => 'base64'],
     *     ['path' => string, 'name' => string, 'mime' => 'application/pdf']
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
    ): bool {
        if (strtolower($this->driver) === 'mail') {
            return $this->sendNativeMail($to, $subject, $htmlBody, $textBody);
        }

        $mail = new PHPMailer(true);

        try {
            // Server configuration
            $mail->isSMTP();
            $mail->Host        = $this->host;
            $mail->Port        = $this->port;
            $mail->CharSet     = PHPMailer::CHARSET_UTF8;
            $mail->SMTPAutoTLS = true;

            // Authentication
            if (!empty($this->username)) {
                $mail->SMTPAuth = true;
                $mail->Username = $this->username;
                $mail->Password = $this->password;
            } else {
                $mail->SMTPAuth = false;
            }

            // Encryption (tls, ssl, or none)
            if (!empty($this->encryption)) {
                $enc = strtolower($this->encryption);
                if ($enc === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } elseif ($enc === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                }
            }

            // Timeout prevention
            $mail->Timeout = 10;

            // Recipients
            $mail->setFrom($this->fromAddress, $this->fromName);
            $mail->addAddress($to, $recipientName ?? '');

            // Optional BCC
            if (!empty($bcc) && filter_var($bcc, FILTER_VALIDATE_EMAIL)) {
                $mail->addBCC($bcc);
            }

            // Attachments
            foreach ($attachments as $att) {
                if (!empty($att['data'])) {
                    $name     = $att['name'] ?? 'attachment.pdf';
                    $encoding = $att['encoding'] ?? PHPMailer::ENCODING_BASE64;
                    $mime     = $att['mime'] ?? 'application/pdf';
                    $mail->addStringAttachment((string) $att['data'], $name, $encoding, $mime);
                } elseif (!empty($att['path']) && file_exists($att['path'])) {
                    $name     = $att['name'] ?? basename($att['path']);
                    $encoding = $att['encoding'] ?? PHPMailer::ENCODING_BASE64;
                    $mime     = $att['mime'] ?? 'application/pdf';
                    $mail->addAttachment($att['path'], $name, $encoding, $mime);
                }
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = !empty($textBody) ? $textBody : strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log(sprintf('[PhpMailerService] Attachment PHPMailer Error to %s: %s', $to, $e->getMessage()));
            return false;
        } catch (Throwable $e) {
            error_log(sprintf('[PhpMailerService] Attachment General Error to %s: %s', $to, $e->getMessage()));
            return false;
        }
    }

    /**
     * Native PHP mail() fallback
     */
    private function sendNativeMail(string $to, string $subject, string $htmlBody, string $textBody): bool
    {
        try {
            $boundary = md5((string) time());
            $headers  = "From: {$this->fromName} <{$this->fromAddress}>\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

            $plain = !empty($textBody) ? $textBody : strip_tags($htmlBody);

            $message  = "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $plain . "\r\n\r\n";

            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $htmlBody . "\r\n\r\n";
            $message .= "--{$boundary}--";

            return mail($to, $subject, $message, $headers);
        } catch (Throwable $e) {
            error_log('[PhpMailerService] Native mail failed: ' . $e->getMessage());
            return false;
        }
    }
}

