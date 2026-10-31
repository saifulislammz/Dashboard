<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F3F4F6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1F2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F3F4F6; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 580px; background-color: #FFFFFF; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);" cellspacing="0" cellpadding="0">
                    <!-- Brand Header -->
                    <tr>
                        <td style="background-color: #059669; padding: 32px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #FFFFFF; font-size: 24px; font-weight: 700; letter-spacing: -0.5px;">Rahe Nazat Institute</h1>
                            <p style="margin: 6px 0 0 0; color: #D1FAE5; font-size: 14px; font-weight: 500;">Learning & Management Portal</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 32px 30px 32px;">
                            <h2 style="margin: 0 0 16px 0; color: #111827; font-size: 20px; font-weight: 600;">Password Reset Request</h2>
                            
                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #4B5563;">
                                Hello<?= !empty($userName) ? ' ' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') : '' ?>,
                            </p>
                            
                            <p style="margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; color: #4B5563;">
                                We received a request to reset the password for your account associated with <strong><?= htmlspecialchars($userEmail ?? '', ENT_QUOTES, 'UTF-8') ?></strong>.
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin: 28px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="<?= htmlspecialchars($resetUrl ?? '#', ENT_QUOTES, 'UTF-8') ?>" target="_blank" style="display: inline-block; background-color: #059669; color: #FFFFFF; font-size: 16px; font-weight: 600; text-decoration: none; padding: 14px 32px; border-radius: 8px; box-shadow: 0 2px 4px rgba(5, 150, 105, 0.3);">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Expiry Info Box -->
                            <div style="background-color: #F0FDF4; border-left: 4px solid #10B981; padding: 14px 16px; border-radius: 6px; margin: 24px 0;">
                                <p style="margin: 0; font-size: 13.5px; line-height: 1.5; color: #065F46;">
                                    <strong>Important:</strong> This password reset link is valid for <strong><?= (int) ($expiresMinutes ?? 30) ?> minutes</strong> only. For security reasons, it can be used only once.
                                </p>
                            </div>

                            <p style="margin: 20px 0 8px 0; font-size: 13px; line-height: 1.5; color: #6B7280;">
                                If you are having trouble clicking the button above, copy and paste this link into your browser:
                            </p>
                            <p style="margin: 0 0 24px 0; word-break: break-all; font-size: 12px; color: #059669; line-height: 1.4;">
                                <a href="<?= htmlspecialchars($resetUrl ?? '#', ENT_QUOTES, 'UTF-8') ?>" style="color: #059669; text-decoration: underline;"><?= htmlspecialchars($resetUrl ?? '', ENT_QUOTES, 'UTF-8') ?></a>
                            </p>

                            <!-- Security Warning -->
                            <hr style="border: none; border-top: 1px solid #E5E7EB; margin: 28px 0 20px 0;">
                            <p style="margin: 0; font-size: 12.5px; line-height: 1.5; color: #9CA3AF;">
                                If you did not request a password reset, please ignore this email or contact support if you suspect unauthorized activity. Your current password will remain completely secure.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #F9FAFB; padding: 20px 32px; text-align: center; border-top: 1px solid #F3F4F6;">
                            <p style="margin: 0; font-size: 12px; color: #9CA3AF;">
                                &copy; <?= date('Y') ?> Rahe Nazat Institute. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

