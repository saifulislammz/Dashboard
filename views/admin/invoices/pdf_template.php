<?php
/**
 * Invoice PDF Template
 *
 * Rendered by App\Services\InvoicePdfService using Dompdf.
 * Uses robust table-based layouts for 100% pixel-perfect vector PDF generation.
 *
 * Variables provided:
 * - $invoice (array)
 * - $items (array)
 * - $settings (array)
 * - $creatorName (string)
 * - $logoBase64 (string|null)
 * - $signatureBase64 (string|null)
 * - $watermarkBase64 (string|null)
 */

$invNo    = htmlspecialchars($invoice['invoice_number'], ENT_QUOTES, 'UTF-8');
$sName    = htmlspecialchars($invoice['student_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8');
$sEmail   = htmlspecialchars($invoice['student_email'] ?? '', ENT_QUOTES, 'UTF-8');
$sPhone   = htmlspecialchars($invoice['student_phone'] ?? '', ENT_QUOTES, 'UTF-8');
$sCountry = htmlspecialchars($invoice['student_country'] ?? '', ENT_QUOTES, 'UTF-8');
$curr     = htmlspecialchars($invoice['currency'] ?? 'BDT', ENT_QUOTES, 'UTF-8');
$status   = strtolower($invoice['status'] ?? 'unpaid');
$invDate  = !empty($invoice['invoice_date']) ? date('M d, Y', strtotime($invoice['invoice_date'])) : date('M d, Y');
$dueDate  = !empty($invoice['due_date']) ? date('M d, Y', strtotime($invoice['due_date'])) : '—';
$notes    = trim($invoice['notes'] ?? '');

$instName    = htmlspecialchars($settings['institution_name'] ?? 'Rahe Nazat Institute', ENT_QUOTES, 'UTF-8');
$instTagline = htmlspecialchars($settings['institution_tagline'] ?? '', ENT_QUOTES, 'UTF-8');
$instAddr    = htmlspecialchars($settings['institution_address'] ?? '', ENT_QUOTES, 'UTF-8');
$instPhone   = htmlspecialchars($settings['institution_phone'] ?? '', ENT_QUOTES, 'UTF-8');
$instEmail   = htmlspecialchars($settings['institution_email'] ?? '', ENT_QUOTES, 'UTF-8');
$footerNote  = htmlspecialchars($settings['invoice_footer_note'] ?? 'Thank you for your business.', ENT_QUOTES, 'UTF-8');

$subtotal   = (float) ($invoice['subtotal'] ?? 0);
$discount   = (float) ($invoice['discount'] ?? 0);
$vatPercent = (float) ($invoice['vat_percent'] ?? 0);
$vatAmount  = (float) ($invoice['vat_amount'] ?? 0);
$grandTotal = (float) ($invoice['grand_total'] ?? 0);
$amountPaid = (float) ($invoice['amount_paid'] ?? 0);
$amountDue  = (float) ($invoice['amount_due'] ?? ($grandTotal - $amountPaid));

$statusColor = match($status) {
    'paid'   => '#15803d',
    'draft'  => '#475569',
    default  => '#b91c1c',
};
$statusBg = match($status) {
    'paid'   => '#dcfce7',
    'draft'  => '#f1f5f9',
    default  => '#fee2e2',
};
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Invoice <?= $invNo ?></title>
<style>
    @page {
        margin: 24px 28px 30px 28px;
        size: A4 portrait;
    }
    *, *::before, *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        font-size: 11px;
        color: #1e293b;
        background: #ffffff;
        line-height: 1.45;
    }
    .wrapper {
        position: relative;
        width: 100%;
    }
    .watermark {
        position: absolute;
        top: 32%;
        left: 20%;
        width: 60%;
        opacity: 0.04;
        text-align: center;
        z-index: -100;
    }
    .watermark img {
        width: 320px;
        max-width: 100%;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    /* Header Section */
    .header-table {
        margin-bottom: 22px;
        border-bottom: 2px solid #0F766E;
        padding-bottom: 14px;
    }
    .inst-logo {
        max-height: 60px;
        max-width: 180px;
        margin-bottom: 4px;
    }
    .inst-title {
        font-size: 15px;
        font-weight: bold;
        color: #0F766E;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .inst-tagline {
        font-size: 10px;
        color: #64748b;
        margin-top: 1px;
    }
    .invoice-title {
        font-size: 24px;
        font-weight: 800;
        color: #0F766E;
        text-align: right;
        letter-spacing: 1px;
    }
    .invoice-status {
        display: inline-block;
        padding: 3px 10px;
        font-size: 9px;
        font-weight: bold;
        text-transform: uppercase;
        border-radius: 4px;
        margin-top: 4px;
        background-color: <?= $statusBg ?>;
        color: <?= $statusColor ?>;
    }

    /* Billing Info Section */
    .info-table {
        margin-bottom: 22px;
    }
    .info-col-title {
        font-size: 10px;
        font-weight: bold;
        color: #0F766E;
        text-transform: uppercase;
        margin-bottom: 6px;
        letter-spacing: 0.5px;
    }
    .info-row {
        font-size: 11px;
        color: #334155;
        line-height: 1.5;
    }
    .info-meta-label {
        color: #64748b;
        width: 90px;
        font-weight: normal;
    }
    .info-meta-val {
        color: #0f172a;
        font-weight: bold;
    }

    /* Items Table */
    .items-table {
        margin-bottom: 20px;
        border: 1px solid #cbd5e1;
    }
    .items-table th {
        background-color: #0F766E;
        color: #ffffff;
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 8px 10px;
        text-align: left;
    }
    .items-table th.text-right,
    .items-table td.text-right {
        text-align: right;
    }
    .items-table th.text-center,
    .items-table td.text-center {
        text-align: center;
    }
    .items-table td {
        padding: 8px 10px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 10.5px;
        color: #1e293b;
    }
    .items-table tr:nth-child(even) td {
        background-color: #f8fafc;
    }
    .item-desc {
        font-size: 9.5px;
        color: #64748b;
        margin-top: 2px;
    }

    /* Summary & Totals Section */
    .summary-table {
        margin-top: 6px;
        margin-bottom: 24px;
    }
    .totals-box {
        width: 100%;
        border-collapse: collapse;
    }
    .totals-box td {
        padding: 4px 8px;
        font-size: 10.5px;
    }
    .totals-label {
        text-align: right;
        color: #64748b;
        width: 60%;
    }
    .totals-val {
        text-align: right;
        font-weight: bold;
        color: #0f172a;
        width: 40%;
    }
    .totals-grand {
        background-color: #f0fdfa;
        border-top: 2px solid #0F766E;
        border-bottom: 2px solid #0F766E;
    }
    .totals-grand td {
        padding: 6px 8px;
        font-size: 12px;
        font-weight: 800;
        color: #0F766E;
    }
    .totals-due {
        background-color: #fef2f2;
        border-top: 1px solid #fca5a5;
    }
    .totals-due td {
        padding: 5px 8px;
        font-size: 11px;
        font-weight: bold;
        color: #b91c1c;
    }
    .notes-box {
        background-color: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 4px;
        padding: 8px 10px;
        margin-top: 8px;
        font-size: 10px;
        color: #475569;
    }

    /* Footer & Signature Section */
    .footer-table {
        margin-top: 20px;
        border-top: 1px solid #e2e8f0;
        padding-top: 12px;
    }
    .contact-cell {
        font-size: 9.5px;
        color: #64748b;
        line-height: 1.5;
        vertical-align: top;
    }
    .signature-cell {
        text-align: right;
        vertical-align: top;
        width: 200px;
    }
    .sig-img {
        max-height: 48px;
        max-width: 150px;
        margin-bottom: 4px;
    }
    .sig-line {
        width: 140px;
        border-top: 1px solid #475569;
        margin-left: auto;
        margin-top: 2px;
        margin-bottom: 3px;
    }
    .sig-text {
        font-size: 9px;
        font-weight: bold;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Audit Bar */
    .audit-bar {
        margin-top: 18px;
        padding: 6px 10px;
        background-color: #f1f5f9;
        border-radius: 4px;
        font-size: 8.5px;
        color: #64748b;
        text-align: center;
    }
</style>
</head>
<body>

<div class="wrapper">
    <?php if (!empty($watermarkBase64)): ?>
    <div class="watermark">
        <img src="<?= $watermarkBase64 ?>" alt="Watermark">
    </div>
    <?php endif; ?>

    <!-- Top Header -->
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <?php if (!empty($logoBase64)): ?>
                    <img src="<?= $logoBase64 ?>" alt="<?= $instName ?>" class="inst-logo"><br>
                <?php endif; ?>
                <div class="inst-title"><?= $instName ?></div>
                <?php if ($instTagline): ?>
                    <div class="inst-tagline"><?= $instTagline ?></div>
                <?php endif; ?>
            </td>
            <td style="width: 40%; vertical-align: top; text-align: right;">
                <div class="invoice-title">INVOICE</div>
                <div style="font-size: 11px; font-weight: bold; color: #475569; margin-top: 2px;">#<?= $invNo ?></div>
                <div style="text-align: right;">
                    <span class="invoice-status"><?= strtoupper($status) ?></span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Billing Info -->
    <table class="info-table">
        <tr>
            <!-- Billed To -->
            <td style="width: 50%; vertical-align: top; padding-right: 15px;">
                <div class="info-col-title">Billed To:</div>
                <div class="info-row" style="font-weight: bold; font-size: 12px; color: #0f172a;"><?= $sName ?></div>
                <?php if ($sEmail): ?>
                    <div class="info-row">Email: <?= $sEmail ?></div>
                <?php endif; ?>
                <?php if ($sPhone): ?>
                    <div class="info-row">Phone: <?= $sPhone ?></div>
                <?php endif; ?>
                <?php if ($sCountry): ?>
                    <div class="info-row">Country: <?= $sCountry ?></div>
                <?php endif; ?>
            </td>
            <!-- Invoice Meta Details -->
            <td style="width: 50%; vertical-align: top;">
                <div class="info-col-title" style="text-align: right;">Invoice Details:</div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="info-meta-label" style="text-align: right; padding-right: 8px;">Invoice Date:</td>
                        <td class="info-meta-val" style="text-align: right;"><?= $invDate ?></td>
                    </tr>
                    <tr>
                        <td class="info-meta-label" style="text-align: right; padding-right: 8px;">Due Date:</td>
                        <td class="info-meta-val" style="text-align: right;"><?= $dueDate ?></td>
                    </tr>
                    <tr>
                        <td class="info-meta-label" style="text-align: right; padding-right: 8px;">Currency:</td>
                        <td class="info-meta-val" style="text-align: right;"><?= $curr ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;" class="text-center">#</th>
                <th style="width: 48%;">Item &amp; Description</th>
                <th style="width: 14%;" class="text-center">Qty</th>
                <th style="width: 16%;" class="text-right">Unit Price</th>
                <th style="width: 16%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $idx => $item): ?>
                    <tr>
                        <td class="text-center" style="color: #64748b;"><?= $idx + 1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($item['item_name'] ?? 'Item', ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (!empty($item['description'])): ?>
                                <div class="item-desc"><?= nl2br(htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8')) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= number_format((float) ($item['quantity'] ?? 1), 2) ?></td>
                        <td class="text-right"><?= number_format((float) ($item['unit_price'] ?? 0), 2) ?></td>
                        <td class="text-right font-bold"><?= number_format((float) ($item['amount'] ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center" style="padding: 14px; color: #64748b;">No items recorded</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Financial Summary -->
    <table class="summary-table">
        <tr>
            <!-- Notes and Institution Note -->
            <td style="width: 55%; vertical-align: top; padding-right: 20px;">
                <?php if ($notes): ?>
                    <div style="font-size: 9.5px; font-weight: bold; color: #475569; text-transform: uppercase;">Customer Notes:</div>
                    <div class="notes-box"><?= nl2br($notes) ?></div>
                <?php endif; ?>
                <div style="margin-top: 10px; font-size: 9.5px; color: #64748b; font-style: italic;">
                    <?= $footerNote ?>
                </div>
            </td>
            <!-- Totals Column -->
            <td style="width: 45%; vertical-align: top;">
                <table class="totals-box">
                    <tr>
                        <td class="totals-label">Subtotal:</td>
                        <td class="totals-val"><?= $curr ?> <?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <?php if ($discount > 0): ?>
                    <tr>
                        <td class="totals-label" style="color: #16a34a;">Discount:</td>
                        <td class="totals-val" style="color: #16a34a;">-<?= $curr ?> <?= number_format($discount, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($vatAmount > 0): ?>
                    <tr>
                        <td class="totals-label">VAT (<?= number_format($vatPercent, 1) ?>%):</td>
                        <td class="totals-val"><?= $curr ?> <?= number_format($vatAmount, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="totals-grand">
                        <td class="totals-label" style="color: #0F766E;">Grand Total:</td>
                        <td class="totals-val" style="color: #0F766E;"><?= $curr ?> <?= number_format($grandTotal, 2) ?></td>
                    </tr>
                    <?php if ($amountPaid > 0): ?>
                    <tr>
                        <td class="totals-label">Amount Paid:</td>
                        <td class="totals-val" style="color: #16a34a;"><?= $curr ?> <?= number_format($amountPaid, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($amountDue > 0): ?>
                    <tr class="totals-due">
                        <td class="totals-label" style="color: #b91c1c;">Balance Due:</td>
                        <td class="totals-val" style="color: #b91c1c;"><?= $curr ?> <?= number_format($amountDue, 2) ?></td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td colspan="2" style="text-align: right; padding-top: 6px;">
                            <span style="background-color: #dcfce7; color: #166534; font-size: 9.5px; font-weight: bold; padding: 3px 8px; border-radius: 3px;">
                                Fully Paid
                            </span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </td>
        </tr>
    </table>

    <!-- Modern Footer with Contact & Authorized Signature -->
    <table class="footer-table">
        <tr>
            <td class="contact-cell">
                <strong><?= $instName ?></strong><br>
                <?php if ($instPhone): ?>Phone: <?= $instPhone ?><br><?php endif; ?>
                <?php if ($instEmail): ?>Email: <?= $instEmail ?><br><?php endif; ?>
                <?php if ($instAddr): ?>Address: <?= $instAddr ?><?php endif; ?>
            </td>
            <?php if (!empty($invoice['show_signature']) && !empty($signatureBase64)): ?>
            <td class="signature-cell">
                <img src="<?= $signatureBase64 ?>" alt="Authorized Signature" class="sig-img"><br>
                <div class="sig-line"></div>
                <div class="sig-text">Authorized Signature</div>
            </td>
            <?php endif; ?>
        </tr>
    </table>

    <!-- Audit Footer Bar -->
    <div class="audit-bar">
        Generated By: <?= htmlspecialchars($creatorName ?? 'System Admin', ENT_QUOTES, 'UTF-8') ?> &nbsp;|&nbsp;
        Invoice #: <?= $invNo ?> &nbsp;|&nbsp;
        Generated: <?= date('Y-m-d H:i:s') ?>
    </div>
</div>

</body>
</html>

