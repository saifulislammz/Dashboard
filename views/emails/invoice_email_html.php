<?php
/**
 * Invoice Delivery HTML Email Template
 *
 * Variables provided:
 * - $invoice (array)
 * - $items (array)
 * - $settings (array)
 * - $customNotes (string|null)
 */

$invNo      = htmlspecialchars($invoice['invoice_number'], ENT_QUOTES, 'UTF-8');
$sName      = htmlspecialchars($invoice['student_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8');
$curr       = htmlspecialchars($invoice['currency'] ?? 'BDT', ENT_QUOTES, 'UTF-8');
$status     = strtolower($invoice['status'] ?? 'unpaid');
$invDate    = !empty($invoice['invoice_date']) ? date('M d, Y', strtotime($invoice['invoice_date'])) : date('M d, Y');
$dueDate    = !empty($invoice['due_date']) ? date('M d, Y', strtotime($invoice['due_date'])) : '—';

$instName   = htmlspecialchars($settings['institution_name'] ?? 'Rahe Nazat Institute', ENT_QUOTES, 'UTF-8');
$instEmail  = htmlspecialchars($settings['institution_email'] ?? '', ENT_QUOTES, 'UTF-8');
$instPhone  = htmlspecialchars($settings['institution_phone'] ?? '', ENT_QUOTES, 'UTF-8');
$footerNote = htmlspecialchars($settings['invoice_footer_note'] ?? '', ENT_QUOTES, 'UTF-8');

$grandTotal = (float) ($invoice['grand_total'] ?? 0);
$amountPaid = (float) ($invoice['amount_paid'] ?? 0);
$amountDue  = (float) ($invoice['amount_due'] ?? ($grandTotal - $amountPaid));

$statusColor = match($status) {
    'paid'   => '#047857',
    'draft'  => '#4b5563',
    default  => '#b91c1c',
};
$statusBg = match($status) {
    'paid'   => '#d1fae5',
    'draft'  => '#f3f4f6',
    default  => '#fee2e2',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= $invNo ?></title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 32px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);" cellspacing="0" cellpadding="0">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #0F766E; padding: 28px 30px; text-align: left;">
                            <table width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: -0.3px;"><?= $instName ?></h1>
                                        <p style="margin: 4px 0 0 0; color: #ccfbf1; font-size: 13px; font-weight: 500;">Official Invoice Notification</p>
                                    </td>
                                    <td align="right" style="vertical-align: middle;">
                                        <span style="display: inline-block; background-color: rgba(255, 255, 255, 0.2); color: #ffffff; font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 6px; font-family: monospace;">
                                            #<?= $invNo ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 30px;">
                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #334155;">
                                Dear <strong><?= $sName ?></strong>,
                            </p>
                            
                            <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                Please find your invoice summary below. A print-ready official PDF copy has been attached to this email for your accounting and payment records.
                            </p>

                            <!-- Invoice Overview Card -->
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 24px;">
                                <table width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="padding-bottom: 12px; width: 50%;">
                                            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; display: block;">Invoice Date</span>
                                            <strong style="font-size: 14px; color: #0f172a;"><?= $invDate ?></strong>
                                        </td>
                                        <td style="padding-bottom: 12px; width: 50%; text-align: right;">
                                            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; display: block;">Due Date</span>
                                            <strong style="font-size: 14px; color: #0f172a;"><?= $dueDate ?></strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-top: 1px dashed #cbd5e1; padding-top: 12px;">
                                            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; display: block;">Payment Status</span>
                                            <span style="display: inline-block; background-color: <?= $statusBg ?>; color: <?= $statusColor ?>; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; margin-top: 3px;">
                                                <?= strtoupper($status) ?>
                                            </span>
                                        </td>
                                        <td style="border-top: 1px dashed #cbd5e1; padding-top: 12px; text-align: right;">
                                            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; display: block;">
                                                <?= $amountDue > 0 ? 'Balance Due' : 'Grand Total' ?>
                                            </span>
                                            <strong style="font-size: 18px; color: <?= $amountDue > 0 ? '#b91c1c' : '#0F766E' ?>;">
                                                <?= $curr ?> <?= number_format($amountDue > 0 ? $amountDue : $grandTotal, 2) ?>
                                            </strong>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Line Items Breakdown -->
                            <?php if (!empty($items)): ?>
                            <div style="margin-bottom: 24px;">
                                <h3 style="margin: 0 0 10px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #0F766E; font-weight: 700;">
                                    Invoice Breakdown
                                </h3>
                                <table width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; border-collapse: collapse;">
                                    <thead>
                                        <tr style="background-color: #f1f5f9; border-bottom: 1px solid #cbd5e1;">
                                            <th style="padding: 10px 14px; text-align: left; font-size: 11px; color: #475569; text-transform: uppercase; font-weight: 700;">Item</th>
                                            <th style="padding: 10px 14px; text-align: center; font-size: 11px; color: #475569; text-transform: uppercase; font-weight: 700; width: 60px;">Qty</th>
                                            <th style="padding: 10px 14px; text-align: right; font-size: 11px; color: #475569; text-transform: uppercase; font-weight: 700; width: 100px;">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $idx => $item): ?>
                                        <tr style="<?= $idx > 0 ? 'border-top: 1px solid #f1f5f9;' : '' ?>">
                                            <td style="padding: 10px 14px; font-size: 13px; color: #1e293b;">
                                                <?= htmlspecialchars($item['item_name'] ?? 'Item', ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td style="padding: 10px 14px; font-size: 13px; color: #64748b; text-align: center;">
                                                <?= number_format((float) ($item['quantity'] ?? 1), 0) ?>
                                            </td>
                                            <td style="padding: 10px 14px; font-size: 13px; color: #0f172a; font-weight: 600; text-align: right;">
                                                <?= $curr ?> <?= number_format((float) ($item['amount'] ?? 0), 2) ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>

                            <!-- Attachment Callout Box -->
                            <div style="background-color: #f0fdf4; border-left: 4px solid #0F766E; padding: 14px 16px; border-radius: 6px; margin-bottom: 24px;">
                                <table width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="font-size: 13px; line-height: 1.5; color: #065f46;">
                                            <strong>📎 PDF Invoice Attached:</strong><br>
                                            Please check the attachments of this email to download your official PDF invoice (<strong>Invoice-<?= $invNo ?>.pdf</strong>).
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <?php if (!empty($customNotes)): ?>
                            <p style="margin: 0 0 16px 0; font-size: 13px; line-height: 1.5; color: #475569; font-style: italic;">
                                <?= nl2br(htmlspecialchars($customNotes, ENT_QUOTES, 'UTF-8')) ?>
                            </p>
                            <?php endif; ?>

                            <?php if ($footerNote): ?>
                            <p style="margin: 0 0 20px 0; font-size: 12px; line-height: 1.5; color: #64748b;">
                                <?= $footerNote ?>
                            </p>
                            <?php endif; ?>

                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">

                            <!-- Contact Info -->
                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #64748b;">
                                If you have any questions regarding this invoice, please reach out to us at
                                <?php if ($instEmail): ?>
                                    <a href="mailto:<?= $instEmail ?>" style="color: #0F766E; text-decoration: none;"><?= $instEmail ?></a>
                                <?php endif; ?>
                                <?php if ($instPhone): ?>
                                    or call <?= $instPhone ?>
                                <?php endif; ?>.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 30px; text-align: center;">
                            <p style="margin: 0; color: #94a3b8; font-size: 12px;">
                                &copy; <?= date('Y') ?> <?= $instName ?>. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>

