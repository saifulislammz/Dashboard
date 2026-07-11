<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($invoice['invoice_number'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($settings['institution_name'] ?? 'Invoice', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300;0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;0,14..32,800;1,14..32,400&display=swap" rel="stylesheet">

    <style>
        /* â”€â”€â”€ Reset & Base â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:        var(--color-primary-green);
            --brand-dark:   #5b21b6;
            --brand-light:  var(--color-primary-green);
            --text-primary: #0f172a;
            --text-secondary:#475569;
            --text-muted:   #94a3b8;
            --border:       #e2e8f0;
            --bg:           #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: var(--text-primary);
            background: var(--bg);
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* â”€â”€â”€ Screen-only controls â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .screen-only {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 12px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .screen-only .title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .screen-only .btn-group { display: flex; gap: 8px; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-primary:hover { background: var(--brand-dark); }
        .btn-secondary { background: #f1f5f9; color: var(--text-secondary); }
        .btn-secondary:hover { background: #e2e8f0; }

        /* â”€â”€â”€ Invoice container â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .invoice-wrapper {
            max-width: 820px;
            margin: 0 auto;
            padding: 80px 24px 60px; /* top pad for fixed bar */
        }

        .invoice {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 40px rgba(0,0,0,0.08);
        }

        /* â”€â”€â”€ Invoice Header â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .inv-header {
            background: linear-gradient(135deg, var(--brand) 0%, var(--color-primary-green) 100%);
            color: #fff;
            padding: 36px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            flex-wrap: wrap;
        }

        .inv-header .inst-logo {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: rgba(255,255,255,0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .inv-header .inst-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }

        .inv-header .inst-tagline {
            font-size: 12px;
            color: rgba(255,255,255,0.65);
            margin-top: 2px;
        }

        .inv-header .inst-contact {
            font-size: 11.5px;
            color: rgba(255,255,255,0.55);
            margin-top: 6px;
            line-height: 1.7;
        }

        .inv-header .inv-meta {
            text-align: right;
            min-width: 180px;
        }

        .inv-header .inv-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.55);
        }

        .inv-header .inv-number {
            font-size: 22px;
            font-weight: 800;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .status-pill {
            display: inline-block;
            margin-top: 10px;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .status-paid    { background: rgba(52,211,153,0.2);  color: #6ee7b7; }
        .status-unpaid  { background: rgba(251,191,36,0.2);  color: #fde68a; }
        .status-draft   { background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.7); }

        /* â”€â”€â”€ Invoice Body â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .inv-body {
            padding: 36px 40px;
            space-y: 28px;
        }

        /* â”€â”€â”€ Info Row: Bill To + Invoice Details â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .inv-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-bottom: 36px;
        }

        .info-section-title {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--border);
        }

        .bill-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .bill-detail {
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.8;
        }

        .inv-details-grid {
            display: grid;
            gap: 6px;
        }

        .inv-detail-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 12px;
        }

        .inv-detail-label { color: var(--text-muted); font-weight: 500; }
        .inv-detail-value { font-weight: 600; color: var(--text-primary); text-align: right; }

        /* â”€â”€â”€ Items Table â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }

        .items-table thead tr {
            background: var(--brand-light);
        }

        .items-table th {
            padding: 10px 14px;
            text-align: left;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--brand);
        }

        .items-table th:not(:first-child) { text-align: right; }
        .items-table th:nth-child(2) { text-align: left; }

        .items-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
        }

        .items-table tbody tr:last-child { border-bottom: none; }

        .items-table tbody tr:nth-child(even) { background: #fafbff; }

        .items-table td {
            padding: 11px 14px;
            font-size: 12.5px;
            color: var(--text-secondary);
        }

        .items-table td:not(:first-child) { text-align: right; }
        .items-table td:nth-child(2) { text-align: left; }

        .items-table td:first-child {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-align: left;
        }

        .item-name { font-weight: 600; color: var(--text-primary); }
        .item-desc { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
        .item-amount { font-weight: 700; color: var(--text-primary); font-family: 'Courier New', monospace; }

        /* â”€â”€â”€ Summary Box â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .summary-area {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 28px;
        }

        .summary-box {
            min-width: 280px;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 9px 18px;
            font-size: 12.5px;
            border-bottom: 1px solid #f1f5f9;
        }

        .summary-row:last-child { border-bottom: none; }

        .summary-label { color: var(--text-secondary); font-weight: 500; }
        .summary-value { font-family: 'Courier New', monospace; font-weight: 600; color: var(--text-primary); }
        .summary-value.red { color: #ef4444; }

        .summary-total {
            background: var(--brand-light);
            padding: 14px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .summary-total-label {
            font-size: 13px;
            font-weight: 800;
            color: var(--brand-dark);
        }

        .summary-total-value {
            font-size: 18px;
            font-weight: 800;
            color: var(--brand);
            font-family: 'Courier New', monospace;
        }

        /* â”€â”€â”€ Notes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .inv-notes {
            background: #fafbff;
            border: 1px solid #e9edf5;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 28px;
        }

        .inv-notes-title {
            font-size: 10px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .inv-notes-text {
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.7;
        }

        /* â”€â”€â”€ Footer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        .inv-footer {
            border-top: 1px solid var(--border);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            background: #fafbff;
        }

        .inv-footer-note {
            font-size: 11.5px;
            color: var(--text-muted);
            font-style: italic;
        }

        .inv-footer-brand {
            font-size: 11px;
            color: var(--text-muted);
            text-align: right;
        }

        .inv-footer-brand strong { color: var(--brand); }

        /* â”€â”€â”€ Print Styles â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        @media print {
            body { background: white; font-size: 12px; }
            .screen-only { display: none !important; }
            .invoice-wrapper { padding: 0; margin: 0; max-width: 100%; }
            .invoice {
                box-shadow: none;
                border-radius: 0;
            }
            .inv-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .items-table thead tr { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .summary-total { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 12mm 14mm; size: A4; }
        }

        /* â”€â”€â”€ Responsive (screen only) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        @media (max-width: 640px) {
            .inv-info { grid-template-columns: 1fr; }
            .inv-header { flex-direction: column; }
            .inv-header .inv-meta { text-align: left; }
            .items-table th:nth-child(3),
            .items-table td:nth-child(3) { display: none; }
        }
    </style>
</head>
<body>

<?php
// Prepare all data for rendering
$invNo    = htmlspecialchars($invoice['invoice_number'],      ENT_QUOTES, 'UTF-8');
$sName    = htmlspecialchars($invoice['student_name'],         ENT_QUOTES, 'UTF-8');
$sEmail   = htmlspecialchars($invoice['student_email'] ?? '',  ENT_QUOTES, 'UTF-8');
$sPhone   = htmlspecialchars($invoice['student_phone'] ?? '',  ENT_QUOTES, 'UTF-8');
$sCountry = htmlspecialchars($invoice['student_country'] ?? '', ENT_QUOTES, 'UTF-8');
$curr     = htmlspecialchars($invoice['currency'],             ENT_QUOTES, 'UTF-8');
$status   = $invoice['status'] ?? 'unpaid';
$iDate    = htmlspecialchars($invoice['invoice_date'] ?? '',   ENT_QUOTES, 'UTF-8');
$dDate    = htmlspecialchars($invoice['due_date'] ?? '',       ENT_QUOTES, 'UTF-8');
$notes    = htmlspecialchars($invoice['notes'] ?? '',          ENT_QUOTES, 'UTF-8');

$instName    = htmlspecialchars($settings['institution_name']    ?? 'Rahe Nazat Institute', ENT_QUOTES, 'UTF-8');
$instTagline = htmlspecialchars($settings['institution_tagline'] ?? 'Excellence in Education', ENT_QUOTES, 'UTF-8');
$instAddr    = htmlspecialchars($settings['institution_address'] ?? '', ENT_QUOTES, 'UTF-8');
$instPhone   = htmlspecialchars($settings['institution_phone']   ?? '', ENT_QUOTES, 'UTF-8');
$instEmail   = htmlspecialchars($settings['institution_email']   ?? '', ENT_QUOTES, 'UTF-8');
$footerNote  = htmlspecialchars($settings['invoice_footer_note'] ?? 'Thank you for your payment.', ENT_QUOTES, 'UTF-8');

$currInfo   = $currencies[$invoice['currency']] ?? ['name' => $curr, 'symbol' => $curr, 'flag' => ''];
$statusClass = match ($status) { 'paid' => 'status-paid', 'unpaid' => 'status-unpaid', default => 'status-draft' };
?>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     SCREEN-ONLY CONTROL BAR
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="screen-only">
    <span class="title">Invoice <?= $invNo ?></span>
    <div class="btn-group">
        <a href="/admin/invoices/view.php?id=<?= (int) $invoice['id'] ?>" class="btn btn-secondary">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print / Save as PDF
        </button>
    </div>
</div>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     INVOICE DOCUMENT
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="invoice-wrapper">
<div class="invoice">

    <!-- â”€â”€ Header â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="inv-header">
        <div>
            <div class="inst-logo">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z"/><path d="M2 17L12 22L22 17"/><path d="M2 12L12 17L22 12"/>
                </svg>
            </div>
            <div class="inst-name"><?= $instName ?></div>
            <div class="inst-tagline"><?= $instTagline ?></div>
            <div class="inst-contact">
                <?php if ($instAddr):   echo $instAddr . '<br>'; endif; ?>
                <?php if ($instPhone):  echo 'Phone: ' . $instPhone . '<br>'; endif; ?>
                <?php if ($instEmail):  echo 'Email: ' . $instEmail; endif; ?>
            </div>
        </div>

        <div class="inv-meta">
            <div class="inv-label">Invoice</div>
            <div class="inv-number"><?= $invNo ?></div>
            <span class="status-pill <?= $statusClass ?>"><?= strtoupper($status) ?></span>
        </div>
    </div>

    <!-- â”€â”€ Body â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="inv-body">

        <!-- Bill To + Invoice Details -->
        <div class="inv-info">
            <div>
                <div class="info-section-title">Billed To</div>
                <div class="bill-name"><?= $sName ?></div>
                <div class="bill-detail">
                    <?php if ($sEmail):   echo htmlspecialchars($sEmail, ENT_QUOTES, 'UTF-8') . '<br>'; endif; ?>
                    <?php if ($sPhone):   echo htmlspecialchars($sPhone, ENT_QUOTES, 'UTF-8') . '<br>'; endif; ?>
                    <?php if ($sCountry): echo htmlspecialchars($sCountry, ENT_QUOTES, 'UTF-8'); endif; ?>
                </div>
            </div>

            <div>
                <div class="info-section-title">Invoice Details</div>
                <div class="inv-details-grid">
                    <div class="inv-detail-row">
                        <span class="inv-detail-label">Issue Date</span>
                        <span class="inv-detail-value"><?= $iDate ?></span>
                    </div>
                    <?php if ($dDate): ?>
                    <div class="inv-detail-row">
                        <span class="inv-detail-label">Due Date</span>
                        <span class="inv-detail-value"><?= $dDate ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="inv-detail-row">
                        <span class="inv-detail-label">Currency</span>
                        <span class="inv-detail-value"><?= $currInfo['flag'] ?> <?= $curr ?></span>
                    </div>
                    <div class="inv-detail-row">
                        <span class="inv-detail-label">Invoice #</span>
                        <span class="inv-detail-value" style="font-family:monospace;"><?= $invNo ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:28px">#</th>
                    <th>Service / Course</th>
                    <th style="width:200px">Description</th>
                    <th style="width:60px">Qty</th>
                    <th style="width:100px">Unit Price</th>
                    <th style="width:110px">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <div class="item-name"><?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if (!empty($item['description'])): ?>
                        <div class="item-desc"><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= number_format((float)$item['quantity'], 2) ?></td>
                    <td><?= number_format((float)$item['unit_price'], 2) ?></td>
                    <td class="item-amount"><?= number_format((float)$item['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Summary -->
        <div class="summary-area">
            <div class="summary-box">
                <div class="summary-row">
                    <span class="summary-label">Subtotal</span>
                    <span class="summary-value"><?= number_format((float)$invoice['subtotal'], 2) ?></span>
                </div>
                <?php if ((float)$invoice['discount'] > 0): ?>
                <div class="summary-row">
                    <span class="summary-label">Discount</span>
                    <span class="summary-value red">- <?= number_format((float)$invoice['discount'], 2) ?></span>
                </div>
                <?php endif; ?>
                <?php if ((float)$invoice['vat_percent'] > 0): ?>
                <div class="summary-row">
                    <span class="summary-label">VAT (<?= number_format((float)$invoice['vat_percent'], 2) ?>%)</span>
                    <span class="summary-value"><?= number_format((float)$invoice['vat_amount'], 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="summary-total">
                    <span class="summary-total-label">GRAND TOTAL</span>
                    <span class="summary-total-value"><?= $curr ?> <?= number_format((float)$invoice['grand_total'], 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <?php if ($notes): ?>
        <div class="inv-notes">
            <div class="inv-notes-title">Notes</div>
            <div class="inv-notes-text"><?= nl2br($notes) ?></div>
        </div>
        <?php endif; ?>

    </div><!-- /inv-body -->

    <!-- â”€â”€ Footer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="inv-footer">
        <div class="inv-footer-note"><?= $footerNote ?></div>
        <div class="inv-footer-brand">
            Issued by <strong><?= $instName ?></strong><br>
            Generated: <?= date('d M Y') ?>
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

