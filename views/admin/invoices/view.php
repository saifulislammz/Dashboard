<?php
/**
 * Invoice View — Single invoice detail page (with sidebar).
 * Accessible only by ROLE_ADMIN via public/admin/invoices/view.php
 */
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$invNo    = htmlspecialchars($invoice['invoice_number'],   ENT_QUOTES, 'UTF-8');
$sName    = htmlspecialchars($invoice['student_name'],      ENT_QUOTES, 'UTF-8');
$sEmail   = htmlspecialchars($invoice['student_email'] ?? '', ENT_QUOTES, 'UTF-8');
$sPhone   = htmlspecialchars($invoice['student_phone'] ?? '', ENT_QUOTES, 'UTF-8');
$sCountry = htmlspecialchars($invoice['student_country'] ?? '', ENT_QUOTES, 'UTF-8');
$curr     = htmlspecialchars($invoice['currency'],          ENT_QUOTES, 'UTF-8');
$status   = $invoice['status'] ?? 'unpaid';
$iDate    = htmlspecialchars($invoice['invoice_date'] ?? '', ENT_QUOTES, 'UTF-8');
$dDate    = htmlspecialchars($invoice['due_date'] ?? '',     ENT_QUOTES, 'UTF-8');
$notes    = htmlspecialchars($invoice['notes'] ?? '',        ENT_QUOTES, 'UTF-8');

$currInfo = $currencies[$invoice['currency']] ?? ['name' => $curr, 'symbol' => $curr, 'flag' => '💰'];
$instName = htmlspecialchars($settings['institution_name'] ?? 'Rahe Nazat Institute', ENT_QUOTES, 'UTF-8');

$statusClasses = match ($status) {
    'paid'   => 'bg-emerald-100 text-emerald-700',
    'unpaid' => 'bg-amber-100 text-amber-700',
    'draft'  => 'bg-gray-100 text-gray-600',
    default  => 'bg-slate-100 text-slate-600',
};
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
    <div class="max-w-[900px] mx-auto px-6 py-8 space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <a href="/admin/invoices/index.php" class="text-[#94a3b8] hover:text-[#64748b] transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <h1 class="text-2xl font-bold text-[#1e293b] tracking-tight">Invoice <?= $invNo ?></h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= $statusClasses ?>">
                        <?= ucfirst($status) ?>
                    </span>
                </div>
                <p class="text-sm text-[#64748b] mt-1 ml-8">Invoice for <?= $sName ?></p>
            </div>
            <div class="flex items-center gap-3 ml-8 sm:ml-0">
                <a href="/admin/invoices/print.php?id=<?= (int) $invoice['id'] ?>" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-[#7c3aed] rounded-xl hover:bg-[#6d28d9] transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print / Download
                </a>
                <form method="POST" action="/admin/invoices/delete.php"
                      onsubmit="return confirm('Delete this invoice? This cannot be undone.')">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $invoice['id'] ?>">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-red-600 bg-red-50 border border-red-100 rounded-xl hover:bg-red-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Delete
                    </button>
                </form>
            </div>
        </div>

        <?php if ($created): ?>
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center gap-2 text-sm text-green-700">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Invoice generated successfully! You can now print or download it.
        </div>
        <?php endif; ?>

        <!-- Invoice Preview Card -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            <!-- Invoice Header Band -->
            <div class="bg-gradient-to-r from-[#7c3aed] to-[#4f46e5] px-8 py-6 text-white">
                <div class="flex flex-col sm:flex-row justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold tracking-wide"><?= $instName ?></h2>
                        <?php if (!empty($settings['institution_tagline'])): ?>
                        <p class="text-white/70 text-sm mt-0.5"><?= htmlspecialchars($settings['institution_tagline'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if (!empty($settings['institution_address'])): ?>
                        <p class="text-white/60 text-xs mt-1"><?= htmlspecialchars($settings['institution_address'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-white/60 uppercase tracking-widest font-semibold">Invoice</p>
                        <p class="text-2xl font-bold font-mono mt-1"><?= $invNo ?></p>
                        <span class="inline-flex items-center mt-2 px-3 py-1 rounded-full text-xs font-bold <?= match ($status) { 'paid' => 'bg-emerald-400/20 text-emerald-200', 'unpaid' => 'bg-amber-400/20 text-amber-200', default => 'bg-white/20 text-white/80' } ?>">
                            <?= strtoupper($status) ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-8 space-y-8">

                <!-- Info Grid: Billed To + Invoice Details -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Billed To -->
                    <div>
                        <h3 class="text-xs font-bold text-[#94a3b8] uppercase tracking-widest mb-3">Billed To</h3>
                        <p class="text-base font-bold text-[#0f172a]"><?= $sName ?></p>
                        <?php if ($sEmail): ?><p class="text-sm text-[#64748b] mt-1"><?= $sEmail ?></p><?php endif; ?>
                        <?php if ($sPhone): ?><p class="text-sm text-[#64748b]"><?= $sPhone ?></p><?php endif; ?>
                        <?php if ($sCountry): ?><p class="text-sm text-[#64748b]"><?= $sCountry ?></p><?php endif; ?>
                    </div>
                    <!-- Invoice Details -->
                    <div class="sm:text-right">
                        <h3 class="text-xs font-bold text-[#94a3b8] uppercase tracking-widest mb-3">Invoice Details</h3>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex sm:justify-end gap-4">
                                <span class="text-[#94a3b8] min-w-[90px] sm:text-right">Issue Date:</span>
                                <span class="font-medium text-[#0f172a]"><?= $iDate ?></span>
                            </div>
                            <?php if ($dDate): ?>
                            <div class="flex sm:justify-end gap-4">
                                <span class="text-[#94a3b8] min-w-[90px] sm:text-right">Due Date:</span>
                                <span class="font-medium text-[#0f172a]"><?= $dDate ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex sm:justify-end gap-4">
                                <span class="text-[#94a3b8] min-w-[90px] sm:text-right">Currency:</span>
                                <span class="font-medium text-[#0f172a]"><?= $currInfo['flag'] ?> <?= $curr ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-y border-gray-100">
                                <th class="text-left py-3 text-xs font-bold text-[#94a3b8] uppercase tracking-wider">#</th>
                                <th class="text-left py-3 text-xs font-bold text-[#94a3b8] uppercase tracking-wider">Service / Course</th>
                                <th class="text-left py-3 text-xs font-bold text-[#94a3b8] uppercase tracking-wider hidden sm:table-cell">Description</th>
                                <th class="text-right py-3 text-xs font-bold text-[#94a3b8] uppercase tracking-wider">Qty</th>
                                <th class="text-right py-3 text-xs font-bold text-[#94a3b8] uppercase tracking-wider">Unit Price</th>
                                <th class="text-right py-3 text-xs font-bold text-[#94a3b8] uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($items as $i => $item): ?>
                            <tr>
                                <td class="py-3 text-xs text-[#94a3b8]"><?= $i + 1 ?></td>
                                <td class="py-3 font-medium text-[#0f172a]"><?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-3 text-[#64748b] hidden sm:table-cell"><?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-3 text-right text-[#475569]"><?= number_format((float)$item['quantity'], 2) ?></td>
                                <td class="py-3 text-right text-[#475569] font-mono"><?= number_format((float)$item['unit_price'], 2) ?></td>
                                <td class="py-3 text-right font-semibold text-[#0f172a] font-mono"><?= number_format((float)$item['amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="flex justify-end">
                    <div class="w-full sm:w-72 space-y-2">
                        <div class="flex justify-between text-sm text-[#64748b]">
                            <span>Subtotal</span>
                            <span class="font-mono font-medium text-[#0f172a]"><?= number_format((float)$invoice['subtotal'], 2) ?></span>
                        </div>
                        <?php if ((float)$invoice['discount'] > 0): ?>
                        <div class="flex justify-between text-sm text-[#64748b]">
                            <span>Discount</span>
                            <span class="font-mono font-medium text-red-500">-<?= number_format((float)$invoice['discount'], 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ((float)$invoice['vat_percent'] > 0): ?>
                        <div class="flex justify-between text-sm text-[#64748b]">
                            <span>VAT (<?= number_format((float)$invoice['vat_percent'], 2) ?>%)</span>
                            <span class="font-mono font-medium text-[#0f172a]"><?= number_format((float)$invoice['vat_amount'], 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="pt-3 border-t-2 border-[#7c3aed] flex justify-between items-center">
                            <span class="font-bold text-[#0f172a]">Grand Total</span>
                            <span class="text-xl font-bold text-[#7c3aed] font-mono">
                                <?= $curr ?> <?= number_format((float)$invoice['grand_total'], 2) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if ($notes): ?>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs font-bold text-[#94a3b8] uppercase tracking-wider mb-2">Notes</p>
                    <p class="text-sm text-[#64748b]"><?= nl2br($notes) ?></p>
                </div>
                <?php endif; ?>

            </div>
        </div><!-- /invoice card -->

    </div><!-- /max-w -->
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
