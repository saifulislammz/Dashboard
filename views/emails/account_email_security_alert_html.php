<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Alert: Account Email Changed</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F3F4F6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1F2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F3F4F6; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 580px; background-color: #FFFFFF; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);" cellspacing="0" cellpadding="0">
                    <!-- Brand Header with Alert Accent -->
                    <tr>
                        <td style="background-color: #1F2937; padding: 32px 30px; text-align: center; border-bottom: 4px solid #F59E0B;">
                            <h1 style="margin: 0; color: #FFFFFF; font-size: 24px; font-weight: 700; letter-spacing: -0.5px;">Rahe Nazat Institute</h1>
                            <p style="margin: 6px 0 0 0; color: #9CA3AF; font-size: 14px; font-weight: 500;">Security Notification Service</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 32px 30px 32px;">
                            <div style="display: inline-block; background-color: #FEF3C7; color: #92400E; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 4px; margin-bottom: 16px;">
                                Security Alert
                            </div>

                            <h2 style="margin: 0 0 16px 0; color: #111827; font-size: 20px; font-weight: 600;">Account Email Changed by Administrator</h2>
                            
                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #4B5563;">
                                Hello<?= !empty($userName) ? ' ' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') : '' ?>,
                            </p>
                            
                            <p style="margin: 0 0 20px 0; font-size: 15px; line-height: 1.6; color: #4B5563;">
                                This notice confirms that an authorized portal administrator has changed the primary email address for your Rahe Nazat Institute account.
                            </p>

                            <!-- Change Summary Box -->
                            <table role="presentation" width="100%" style="background-color: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; margin: 20px 0; padding: 14px 18px;" cellspacing="0" cellpadding="4">
                                <tr>
                                    <td style="font-size: 13px; color: #6B7280; width: 35%;">Previous Email:</td>
                                    <td style="font-size: 13.5px; color: #374151; font-weight: 600; font-family: monospace;"><?= htmlspecialchars($oldEmail ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <td style="font-size: 13px; color: #6B7280;">New Primary Email:</td>
                                    <td style="font-size: 13.5px; color: #047857; font-weight: 600; font-family: monospace;"><?= htmlspecialchars($newEmail ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <td style="font-size: 13px; color: #6B7280;">Changed At:</td>
                                    <td style="font-size: 13px; color: #374151;"><?= date('F j, Y, g:i a e') ?></td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #4B5563;">
                                This address (<strong><?= htmlspecialchars($oldEmail ?? '', ENT_QUOTES, 'UTF-8') ?></strong>) will no longer receive transactional portal emails or be accepted for sign-in.
                            </p>

                            <!-- Alert Box -->
                            <div style="background-color: #FFFBEB; border-left: 4px solid #F59E0B; padding: 14px 16px; border-radius: 6px; margin: 24px 0;">
                                <p style="margin: 0; font-size: 13.5px; line-height: 1.5; color: #92400E;">
                                    <strong>Did not expect this change?</strong><br>
                                    If you did not request this update or believe this was done in error, please contact the institute administration immediately to secure your account.
                                </p>
                            </div>

                            <hr style="border: none; border-top: 1px solid #E5E7EB; margin: 28px 0 20px 0;">

                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #9CA3AF; text-align: center;">
                                This is an automated security advisory. Replies to this address are not monitored.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

