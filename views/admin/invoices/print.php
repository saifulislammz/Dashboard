<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($invoice['invoice_number'], ENT_QUOTES, 'UTF-8') ?> -
        <?= htmlspecialchars($settings['institution_name'] ?? 'Invoice', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ─── Reset & Base ────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand-blue: var(--color-primary-green, #0F766E);
            --brand-coral: var(--color-yellow, #EAB308);
            --text-main: var(--color-black, #000000);
            --text-muted: #6b7280;
            --border-color: #ccfbf1; /* Light green border for table */
        }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: var(--text-main);
            background: #f1f5f9;
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ─── Screen-only controls ──────────────────────── */
        .screen-only {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .screen-only .title {
            font-size: 14px;
            font-weight: 600;
            color: #475569;
        }

        .screen-only .btn-group {
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.15s;
        }

        .btn-primary {
            background: var(--brand-blue);
            color: #fff;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        /* ─── Invoice container ─────────────────────────── */
        .invoice-wrapper {
            max-width: 820px;
            margin: 0 auto;
            padding: 80px 24px 60px;
        }

        .invoice {
            background: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 40px rgba(0, 0, 0, 0.08);
            min-height: 1050px;
            display: flex;
            flex-direction: column;
        }

        /* ─── Shapes ────────────────────────────────────── */
        .shape-top-left {
            position: absolute;
            top: 0;
            left: 0;
            width: 300px;
            height: 180px;
            background-color: var(--brand-blue);
            clip-path: polygon(0 0, 100% 0, 0 100%);
            z-index: 1;
        }



        /* ─── Watermark & Header ────────────────────────── */
        .invoice-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            z-index: 0;
            pointer-events: none;
        }

        .invoice-watermark img {
            width: 450px;
            max-width: 80%;
            height: auto;
            filter: grayscale(100%);
        }

        .inv-header-top {
            display: flex;
            justify-content: flex-end;
            padding: 50px 60px 20px;
            position: relative;
            z-index: 10;
        }

        .company-logo-text {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .company-logo-text svg {
            width: 48px;
            height: 48px;
            margin-bottom: 8px;
            fill: var(--brand-blue);
        }

        .company-logo {
            width: auto;
            height: 60px;
            margin-bottom: 8px;
            object-fit: contain;
        }

        .company-name {
            font-size: 16px;
            font-weight: 800;
            color: var(--brand-blue);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* ─── Title ─────────────────────────────────────── */
        .inv-title-area {
            padding: 0 60px;
            margin-bottom: 30px;
            position: relative;
            z-index: 10;
        }

        .inv-title {
            font-size: 26px;
            font-weight: 800;
            color: var(--brand-blue);
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .status-pill {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
        }

        .status-unpaid {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-draft {
            background: #f3f4f6;
            color: #374151;
        }

        /* ─── Info ──────────────────────────────────────── */
        .inv-info-section {
            display: flex;
            justify-content: space-between;
            padding: 0 60px;
            margin-bottom: 30px;
            position: relative;
            z-index: 10;
        }

        .info-col h3 {
            font-size: 11px;
            font-weight: 700;
            color: var(--brand-blue);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-col p {
            font-size: 12px;
            line-height: 1.6;
            color: var(--text-main);
        }

        .details-table {
            border-collapse: collapse;
        }

        .details-table td {
            padding-bottom: 6px;
            font-size: 12px;
        }

        .details-table td:first-child {
            color: var(--text-muted);
            padding-right: 20px;
            white-space: nowrap;
        }

        .details-table td:last-child {
            color: var(--text-main);
            font-weight: 500;
        }

        /* ─── Table ─────────────────────────────────────── */
        .inv-table-wrapper {
            padding: 0 60px;
            margin-bottom: 40px;
            flex-grow: 1;
            position: relative;
            z-index: 10;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 2px solid var(--brand-blue);
            border-bottom: 2px solid var(--brand-blue);
        }

        .items-table th,
        .items-table td {
            border-left: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
            padding: 10px 14px;
        }

        .items-table th:first-child,
        .items-table td:first-child {
            border-left: none;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            border-right: none;
        }

        .items-table thead tr {
            border-bottom: 2px solid var(--brand-blue);
        }

        .items-table th {
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: var(--brand-blue);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table th.center {
            text-align: center;
        }

        .items-table th.right {
            text-align: right;
        }

        .items-table td {
            font-size: 12px;
            color: var(--text-main);
            vertical-align: top;
            border-bottom: 1px solid #f1f5f9;
        }

        .items-table td.center {
            text-align: center;
        }

        .items-table td.right {
            text-align: right;
        }

        /* ─── Bottom Section (Totals & Notes) ───────────── */
        .inv-bottom-section {
            display: flex;
            justify-content: space-between;
            gap: 40px;
            padding: 10px 60px 40px;
            position: relative;
            z-index: 10;
        }

        .inv-notes-terms {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .notes-box, .terms-box {
            background: #f8fafc;
            border-left: 3px solid var(--brand-blue);
            padding: 16px 20px;
            border-radius: 0 12px 12px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }

        .notes-box h4, .terms-box h4 {
            font-size: 11px;
            font-weight: 800;
            color: var(--brand-blue);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .notes-box p, .terms-box p {
            font-size: 11px;
            color: var(--text-main);
            line-height: 1.6;
        }

        .inv-totals {
            width: 340px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.05);
            padding: 24px;
            border: 1px solid #f1f5f9;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            font-size: 13px;
            color: var(--text-main);
            border-bottom: 1px dashed #e2e8f0;
        }

        .total-line:last-child {
            border-bottom: none;
        }

        .total-line .label {
            font-weight: 500;
            color: var(--text-muted);
        }

        .total-line .value {
            font-weight: 700;
        }

        .total-line.discount .value {
            color: var(--color-red, #EF4444);
        }

        .total-line.grand-total {
            margin-top: 12px;
            padding-top: 18px;
            border-top: 2px solid var(--brand-blue);
            border-bottom: none;
        }

        .total-line.grand-total .label {
            font-size: 14px;
            font-weight: 800;
            color: var(--brand-blue);
            text-transform: uppercase;
        }

        .total-line.grand-total .value {
            font-size: 18px;
            font-weight: 800;
            color: var(--brand-blue);
        }

        /* ─── Modern Footer ────────────────────────────── */
        .inv-modern-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 60px 40px;
            position: relative;
            z-index: 10;
        }

        .footer-contact h4 {
            font-size: 11px;
            font-weight: 800;
            color: var(--text-main);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .footer-contact p {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.7;
        }

        .footer-signature {
            width: 200px;
            text-align: center;
        }

        .footer-signature .sig-line {
            border-top: 1px dashed var(--text-muted);
            margin-bottom: 10px;
        }

        .footer-signature p {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-main);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ─── Print ─────────────────────────────────────── */
        @media print {
            body {
                background: white;
                font-size: 12px;
            }

            .screen-only {
                display: none !important;
            }

            .invoice-wrapper {
                padding: 0;
                margin: 0;
                max-width: 100%;
            }

            .invoice {
                box-shadow: none;
                min-height: 100vh;
            }

            @page {
                margin: 0;
                size: A4;
            }

            .shape-top-left {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .inv-table-wrapper th,
            .items-table {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .inv-totals {
                box-shadow: none;
                border: 1px solid var(--brand-blue);
            }
        }

        @media (max-width: 640px) {
            .inv-info-section {
                flex-direction: column;
                gap: 20px;
                padding: 0 20px;
            }

            .inv-header-top {
                padding: 30px 20px 20px;
            }

            .inv-title-area,
            .inv-table-wrapper,
            .inv-footer-area {
                padding: 0 20px;
            }

            .items-table th:nth-child(3),
            .items-table td:nth-child(3) {
                display: none;
            }

            .shape-top-left {
                width: 150px;
                height: 100px;
            }
        }
    </style>
</head>

<body>

    <?php
    // Prepare all data for rendering
    $invNo = htmlspecialchars($invoice['invoice_number'], ENT_QUOTES, 'UTF-8');
    $sName = htmlspecialchars($invoice['student_name'], ENT_QUOTES, 'UTF-8');
    $sEmail = htmlspecialchars($invoice['student_email'] ?? '', ENT_QUOTES, 'UTF-8');
    $sPhone = htmlspecialchars($invoice['student_phone'] ?? '', ENT_QUOTES, 'UTF-8');
    $sCountry = htmlspecialchars($invoice['student_country'] ?? '', ENT_QUOTES, 'UTF-8');
    $curr = htmlspecialchars($invoice['currency'], ENT_QUOTES, 'UTF-8');
    $status = $invoice['status'] ?? 'unpaid';
    $iDate = htmlspecialchars($invoice['invoice_date'] ?? '', ENT_QUOTES, 'UTF-8');
    $dDate = htmlspecialchars($invoice['due_date'] ?? '', ENT_QUOTES, 'UTF-8');
    $notes = htmlspecialchars($invoice['notes'] ?? '', ENT_QUOTES, 'UTF-8');

    $instName = htmlspecialchars($settings['institution_name'] ?? 'Rahe Nazat Institute', ENT_QUOTES, 'UTF-8');
    $instTagline = htmlspecialchars($settings['institution_tagline'] ?? 'Excellence in Education', ENT_QUOTES, 'UTF-8');
    $instAddr = htmlspecialchars($settings['institution_address'] ?? '', ENT_QUOTES, 'UTF-8');
    $instPhone = htmlspecialchars($settings['institution_phone'] ?? '', ENT_QUOTES, 'UTF-8');
    $instEmail = htmlspecialchars($settings['institution_email'] ?? '', ENT_QUOTES, 'UTF-8');
    $instLogo = htmlspecialchars($settings['institution_logo'] ?? '', ENT_QUOTES, 'UTF-8');
    $footerNote = htmlspecialchars($settings['invoice_footer_note'] ?? 'Thank you for your payment.', ENT_QUOTES, 'UTF-8');

    $currInfo = $currencies[$invoice['currency']] ?? ['name' => $curr, 'symbol' => $curr, 'flag' => ''];
    $statusClass = match ($status) { 'paid' => 'status-paid', 'unpaid' => 'status-unpaid', default => 'status-draft'};
    ?>

    <!-- ══════════════════════════════════════════════════════════════════════════
     SCREEN-ONLY CONTROL BAR
══════════════════════════════════════════════════════════════════════════ -->
    <div class="screen-only">
        <span class="title">Invoice <?= $invNo ?></span>
        <div class="btn-group">
            <a href="/admin/invoices/view.php?id=<?= (int) $invoice['id'] ?>" class="btn btn-secondary">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════════
     INVOICE DOCUMENT
══════════════════════════════════════════════════════════════════════════ -->
    <div class="invoice-wrapper">
        <div class="invoice">

            <div class="shape-top-left"></div>

            <?php if ($instLogo): ?>
                <div class="invoice-watermark">
                    <img src="<?= $instLogo ?>" alt="">
                </div>
            <?php endif; ?>

            <!-- ── Header ────────────────────────────────────────────────────────── -->
            <div class="inv-header-top">
                <div class="company-logo-text">
                    <?php if ($instLogo): ?>
                        <img src="<?= $instLogo ?>" alt="Logo" class="company-logo">
                    <?php else: ?>
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2L2 7L12 12L22 7L12 2Z" />
                            <path d="M2 17L12 22L22 17" />
                            <path d="M2 12L12 17L22 12" />
                        </svg>
                    <?php endif; ?>
                    <div class="company-name"><?= $instName ?></div>
                </div>
            </div>

            <!-- ── Title ─────────────────────────────────────────────────────────── -->
            <div class="inv-title-area">
                <div class="inv-title">
                    INVOICE
                    <span class="status-pill <?= $statusClass ?>"><?= strtoupper($status) ?></span>
                </div>
            </div>

            <!-- ── Info ──────────────────────────────────────────────────────────── -->
            <div class="inv-info-section">
                <div class="info-col">
                    <h3>INVOICE TO :</h3>
                    <p>
                        <strong><?= $sName ?></strong><br>
                        <?php if ($sCountry): ?>    <?= $sCountry ?><br><?php endif; ?>
                        <?php if ($sEmail): ?>    <?= $sEmail ?><br><?php endif; ?>
                        <?php if ($sPhone): ?>    <?= $sPhone ?><br><?php endif; ?>
                    </p>
                </div>

                <div class="info-col">
                    <h3>INVOICE DETAILS</h3>
                    <table class="details-table">
                        <tr>
                            <td>Invoice No :</td>
                            <td><?= $invNo ?></td>
                        </tr>
                        <tr>
                            <td>Invoice Date :</td>
                            <td><?= $iDate ?></td>
                        </tr>
                        <?php if ($dDate): ?>
                            <tr>
                                <td>Due Date :</td>
                                <td><?= $dDate ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- ── Table ─────────────────────────────────────────────────────────── -->
            <div class="inv-table-wrapper">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="center" style="width:40px">#</th>
                            <th>SERVICE / COURSE</th>
                            <th>DESCRIPTION</th>
                            <th class="center" style="width:60px">QTY</th>
                            <th class="right" style="width:100px">UNIT PRICE</th>
                            <th class="right" style="width:120px">AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $item): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <div class="item-name"><?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php if (!empty($item['description'])): ?>
                                        <div class="item-desc">
                                            <?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= number_format((float) $item['quantity'], 2) ?></td>
                                <td><?= number_format((float) $item['unit_price'], 2) ?></td>
                                <td class="item-amount"><?= number_format((float) $item['amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div><!-- /inv-table-wrapper -->

            <!-- ── Bottom Section (Totals & Notes) ── -->
            <div class="inv-bottom-section">
                <div class="inv-notes-terms">
                    <?php if ($notes): ?>
                    <div class="notes-box">
                        <h4>Notes</h4>
                        <p><?= nl2br($notes) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($footerNote): ?>
                    <div class="terms-box">
                        <h4>Terms & Conditions</h4>
                        <p><?= $footerNote ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="inv-totals">
                    <div class="total-line">
                        <span class="label">Subtotal</span>
                        <span class="value"><?= number_format((float) $invoice['subtotal'], 2) ?></span>
                    </div>
                    <?php if ((float) $invoice['discount'] > 0): ?>
                    <div class="total-line discount">
                        <span class="label">Discount</span>
                        <span class="value">- <?= number_format((float) $invoice['discount'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ((float) $invoice['vat_percent'] > 0): ?>
                    <div class="total-line">
                        <span class="label">VAT (<?= number_format((float) $invoice['vat_percent'], 2) ?>%)</span>
                        <span class="value"><?= number_format((float) $invoice['vat_amount'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="total-line grand-total">
                        <span class="label">GRAND TOTAL</span>
                        <span class="value"><?= $currInfo['symbol'] ?? $curr ?> <?= number_format((float) $invoice['grand_total'], 2) ?></span>
                    </div>
                </div>
            </div>

            <!-- ── Modern Footer ── -->
            <div class="inv-modern-footer">
                <div class="footer-contact">
                    <h4>Contact Us</h4>
                    <p>
                        <strong><?= $instName ?></strong><br>
                        <?php if ($instPhone): ?>Phone: <?= $instPhone ?><br><?php endif; ?>
                        <?php if ($instEmail): ?>Email: <?= $instEmail ?><br><?php endif; ?>
                        <?php if ($instAddr): ?><?= $instAddr ?><?php endif; ?>
                    </p>
                </div>
                <div class="footer-signature">
                    <div class="sig-line"></div>
                    <p>Authorized Signature</p>
                </div>
            </div>

        </div><!-- /invoice -->
    </div><!-- /invoice-wrapper -->

    <script>
        // Auto-open print dialog after a short delay (gives fonts time to load)
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 600);
        });
    </script>
</body>

</html>