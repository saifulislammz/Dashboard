<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Email Updated</title>
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
                            <h2 style="margin: 0 0 16px 0; color: #111827; font-size: 20px; font-weight: 600;">Your Account Email Has Been Updated</h2>
                            
                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #4B5563;">
                                Hello<?= !empty($userName) ? ' ' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') : '' ?>,
                            </p>
                            
                            <p style="margin: 0 0 20px 0; font-size: 15px; line-height: 1.6; color: #4B5563;">
                                An administrator has successfully updated your primary account and login email address to:
                            </p>

                            <!-- Highlight Box -->
                            <div style="background-color: #F0FDF4; border: 1px solid #A7F3D0; padding: 16px 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
                                <span style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #065F46; font-weight: 600; display: block; margin-bottom: 4px;">New Login Email</span>
                                <strong style="font-size: 17px; color: #047857; font-family: monospace;"><?= htmlspecialchars($newEmail ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>

                            <p style="margin: 0 0 20px 0; font-size: 15px; line-height: 1.6; color: #4B5563;">
                                Moving forward, please use this new email address along with your existing password to sign in. Your previous email address can no longer be used to access your account.
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin: 28px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="<?= htmlspecialchars($loginUrl ?? '#', ENT_QUOTES, 'UTF-8') ?>" target="_blank" style="display: inline-block; background-color: #059669; color: #FFFFFF; font-size: 16px; font-weight: 600; text-decoration: none; padding: 14px 32px; border-radius: 8px; box-shadow: 0 2px 4px rgba(5, 150, 105, 0.3);">
                                            Sign In to Your Account
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Security Notice -->
                            <div style="background-color: #F9FAFB; border-left: 4px solid #6B7280; padding: 14px 16px; border-radius: 6px; margin: 24px 0;">
                                <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #4B5563;">
                                    <strong>Security Notice:</strong> All past password reset links and remembered sessions across your devices have been refreshed for your protection.
                                </p>
                            </div>

                            <hr style="border: none; border-top: 1px solid #E5E7EB; margin: 28px 0 20px 0;">

                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #9CA3AF; text-align: center;">
                                This is an automated message sent from Rahe Nazat Institute. If you have questions, please reach out to the campus administration.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

