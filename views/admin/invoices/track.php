<?php
/**
 * Super Admin Invoice Tracking & Audit Dashboard View
 * Accessible exclusively by Super Administrators.
 */
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$searchQuery     = htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8');
$deletionFilter  = $_GET['deletion_status'] ?? 'all';
$restoredMessage = isset($_GET['restored']) ? 'Invoice has been successfully restored to active status.' : null;
$errorMessage    = isset($_GET['error']) ? htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') : null;
?>

<main class="flex-1 overflow-y-auto bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 border border-teal-200 flex items-center justify-center text-teal-700 shadow-sm">
                        <i class="ph ph-magnifying-glass text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Invoice Tracking &amp; Audit</h1>
                        <p class="text-sm text-slate-500 mt-0.5">Super Admin forensic audit, creator details, account history, and soft-delete tracker</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="/admin/invoices/index.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                    <i class="ph ph-receipt text-base"></i>
                    Invoice Dashboard
                </a>
                <a href="/admin/invoices/create.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-teal-600 rounded-xl hover:bg-teal-700 transition-colors shadow-sm">
                    <i class="ph ph-plus-circle text-base"></i>
                    Generate Invoice
                </a>
            </div>
        </div>

        <!-- Notification Alerts -->
        <?php if ($restoredMessage): ?>
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center gap-3 text-emerald-800 shadow-sm">
                <i class="ph ph-check-circle text-2xl text-emerald-600 shrink-0"></i>
                <div>
                    <h4 class="font-semibold text-sm">Invoice Restored</h4>
                    <p class="text-xs text-emerald-700 mt-0.5"><?= $restoredMessage ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-center gap-3 text-rose-800 shadow-sm">
                <i class="ph ph-warning-circle text-2xl text-rose-600 shrink-0"></i>
                <div>
                    <h4 class="font-semibold text-sm">Action Failed</h4>
                    <p class="text-xs text-rose-700 mt-0.5"><?= $errorMessage ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search Bar Section -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <form method="GET" action="/admin/invoices/track.php" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label for="track-search-input" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Search by Invoice Number
                    </label>
                    <div class="relative">
                        <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                        <input type="text"
                               id="track-search-input"
                               name="search"
                               value="<?= $searchQuery ?>"
                               placeholder="e.g. INV2506040001 or customer name"
                               required
                               class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all font-mono text-slate-800">
                    </div>
                </div>

                <div class="w-full md:w-56">
                    <label for="deletion_status_filter" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Status Filter
                    </label>
                    <select id="deletion_status_filter"
                            name="deletion_status"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 transition-colors">
                        <option value="all" <?= $deletionFilter === 'all' ? 'selected' : '' ?>>All Invoices (Active &amp; Deleted)</option>
                        <option value="active" <?= $deletionFilter === 'active' ? 'selected' : '' ?>>Active Records Only</option>
                        <option value="deleted" <?= $deletionFilter === 'deleted' ? 'selected' : '' ?>>Soft-Deleted Records Only</option>
                    </select>
                </div>

                <div class="flex gap-2 w-full md:w-auto">
                    <button type="submit"
                            class="flex-1 md:flex-none inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition-colors shadow-sm">
                        <i class="ph ph-magnifying-glass text-base"></i>
                        Audit Track
                    </button>
                    <?php if ($searchQuery !== ''): ?>
                        <a href="/admin/invoices/track.php"
                           class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors"
                           title="Clear Search">
                            <i class="ph ph-x text-base"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Search Result Dossier (When Search is Performed) -->
        <?php if ($searchPerformed): ?>
            <?php if ($trackingData): ?>
                <?php
                $inv        = $trackingData['invoice'];
                $items      = $trackingData['items'];
                $activities = $trackingData['activities'];
                $isDeleted  = !empty($inv['deleted_at']);

                $statusBadgeClass = match ($inv['status']) {
                    'paid'   => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    'unpaid' => 'bg-amber-100 text-amber-800 border-amber-200',
                    'draft'  => 'bg-slate-100 text-slate-700 border-slate-200',
                    default  => 'bg-slate-100 text-slate-700 border-slate-200',
                };
                ?>

                <!-- Main Invoice Tracking Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    
                    <!-- Dossier Header -->
                    <div class="px-6 py-5 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="text-xl font-bold font-mono tracking-wide"><?= htmlspecialchars($inv['invoice_number'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider border <?= $statusBadgeClass ?>">
                                    <?= ucfirst(htmlspecialchars($inv['status'], ENT_QUOTES, 'UTF-8')) ?>
                                </span>
                                <?php if ($isDeleted): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-rose-600 text-white shadow-sm">
                                        <i class="ph ph-trash text-sm"></i>
                                        SOFT-DELETED
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                        <i class="ph ph-check-circle text-sm"></i>
                                        ACTIVE RECORD
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-slate-300">
                                Database ID: #<?= (int) $inv['id'] ?> &bull; Created on <?= htmlspecialchars(date('d M Y, h:i:s A', strtotime($inv['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="/admin/invoices/print.php?id=<?= (int) $inv['id'] ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-white/10 hover:bg-white/20 text-white rounded-xl transition-colors border border-white/10">
                                <i class="ph ph-printer text-sm"></i>
                                View Printable PDF
                            </a>
                            <?php if (!$isDeleted): ?>
                                <a href="/admin/invoices/view.php?id=<?= (int) $inv['id'] ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-white/10 hover:bg-white/20 text-white rounded-xl transition-colors border border-white/10">
                                    <i class="ph ph-eye text-sm"></i>
                                    Standard View
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Soft-Deleted Alert & Restore Action (if deleted) -->
                    <?php if ($isDeleted): ?>
                        <div class="p-6 bg-rose-50/70 border-b border-rose-200">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-rose-100 border border-rose-200 flex items-center justify-center text-rose-700 shrink-0">
                                        <i class="ph ph-trash text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-rose-900">This invoice was soft-deleted</h3>
                                        <p class="text-xs text-rose-700 mt-0.5">
                                            Deleted on <strong><?= htmlspecialchars(date('d M Y, h:i:s A', strtotime($inv['deleted_at'])), ENT_QUOTES, 'UTF-8') ?></strong>
                                            by <strong><?= htmlspecialchars($inv['deleter_name'] ?? ('User #' . ($inv['deleted_by'] ?? 'N/A')), ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?= !empty($inv['deleter_email']) ? '(' . htmlspecialchars($inv['deleter_email'], ENT_QUOTES, 'UTF-8') . ')' : '' ?>.
                                        </p>
                                        <p class="text-xs text-slate-600 mt-1">
                                            The underlying database record and sequential invoice number are preserved to protect sequence continuity.
                                        </p>
                                    </div>
                                </div>

                                <form method="POST" action="/admin/invoices/track.php" onsubmit="return confirm('Restore this invoice back to active status?');">
                                    <input type="hidden" name="action" value="restore">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="id" value="<?= (int) $inv['id'] ?>">
                                    <input type="hidden" name="search_query" value="<?= $searchQuery ?>">
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-rose-700 hover:bg-rose-800 rounded-xl transition-colors shadow-sm">
                                        <i class="ph ph-arrow-counter-clockwise text-base"></i>
                                        Restore Invoice
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Information Cards Grid -->
                    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">

                        <!-- Card 1: Creator Account Information -->
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-3">
                            <div class="flex items-center gap-2 pb-2 border-b border-slate-200">
                                <i class="ph ph-user-circle-gear text-teal-600 text-lg"></i>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Creator Account</h3>
                            </div>
                            <div class="space-y-2 text-xs">
                                <div>
                                    <span class="text-slate-400 block">Created By:</span>
                                    <span class="font-semibold text-slate-800 text-sm">
                                        <?= htmlspecialchars($inv['creator_name'] ?? ('Admin #' . $inv['created_by']), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Account Email:</span>
                                    <span class="font-mono text-slate-700">
                                        <?= htmlspecialchars($inv['creator_email'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Creator User ID:</span>
                                    <span class="font-mono text-slate-700">#<?= (int) $inv['created_by'] ?></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Creation Timestamp:</span>
                                    <span class="font-medium text-slate-700">
                                        <?= htmlspecialchars(date('d M Y, h:i:s A', strtotime($inv['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Client & Billing Information -->
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-3">
                            <div class="flex items-center gap-2 pb-2 border-b border-slate-200">
                                <i class="ph ph-address-book text-blue-600 text-lg"></i>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Client Account</h3>
                            </div>
                            <div class="space-y-2 text-xs">
                                <div>
                                    <span class="text-slate-400 block">Student / Client Name:</span>
                                    <span class="font-semibold text-slate-800 text-sm">
                                        <?= htmlspecialchars($inv['student_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Email &amp; Phone:</span>
                                    <span class="text-slate-700">
                                        <?= htmlspecialchars($inv['student_email'] ?: 'No Email', ENT_QUOTES, 'UTF-8') ?>
                                        <?= $inv['student_phone'] ? ' &bull; ' . htmlspecialchars($inv['student_phone'], ENT_QUOTES, 'UTF-8') : '' ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Country / Address:</span>
                                    <span class="text-slate-700"><?= htmlspecialchars($inv['student_country'] ?: 'Not Specified', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Invoice Date / Due Date:</span>
                                    <span class="text-slate-700 font-medium">
                                        <?= htmlspecialchars($inv['invoice_date'], ENT_QUOTES, 'UTF-8') ?>
                                        <?= !empty($inv['due_date']) ? ' &bull; Due: ' . htmlspecialchars($inv['due_date'], ENT_QUOTES, 'UTF-8') : '' ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Financial Totals Breakdown -->
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-3">
                            <div class="flex items-center gap-2 pb-2 border-b border-slate-200">
                                <i class="ph ph-coins text-amber-600 text-lg"></i>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Financial Breakdown</h3>
                            </div>
                            <div class="space-y-1.5 text-xs">
                                <div class="flex justify-between text-slate-600">
                                    <span>Subtotal:</span>
                                    <span class="font-mono"><?= number_format((float) $inv['subtotal'], 2) ?> <?= htmlspecialchars($inv['currency'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="flex justify-between text-slate-600">
                                    <span>Discount:</span>
                                    <span class="font-mono text-emerald-600">-<?= number_format((float) $inv['discount'], 2) ?></span>
                                </div>
                                <div class="flex justify-between text-slate-600">
                                    <span>VAT (<?= number_format((float) $inv['vat_percent'], 2) ?>%):</span>
                                    <span class="font-mono">+<?= number_format((float) $inv['vat_amount'], 2) ?></span>
                                </div>
                                <div class="flex justify-between text-sm font-bold text-slate-900 pt-1 border-t border-slate-200">
                                    <span>Grand Total:</span>
                                    <span class="font-mono"><?= number_format((float) $inv['grand_total'], 2) ?> <?= htmlspecialchars($inv['currency'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="flex justify-between text-xs text-slate-600 pt-1">
                                    <span>Amount Paid:</span>
                                    <span class="font-mono text-emerald-700 font-semibold"><?= number_format((float) $inv['amount_paid'], 2) ?></span>
                                </div>
                                <div class="flex justify-between text-xs font-bold text-rose-700">
                                    <span>Remaining Due:</span>
                                    <span class="font-mono"><?= number_format((float) $inv['amount_due'], 2) ?> <?= htmlspecialchars($inv['currency'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Line Items Table -->
                    <div class="px-6 pb-6">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">Invoice Line Items</h4>
                        <div class="border border-slate-200 rounded-xl overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-100 text-slate-700 border-b border-slate-200 font-bold uppercase">
                                    <tr>
                                        <th class="py-2.5 px-4 w-12 text-center">#</th>
                                        <th class="py-2.5 px-4">Item &amp; Description</th>
                                        <th class="py-2.5 px-4 text-center w-24">Qty</th>
                                        <th class="py-2.5 px-4 text-right w-32">Unit Price</th>
                                        <th class="py-2.5 px-4 text-right w-32">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <?php if (!empty($items)): ?>
                                        <?php foreach ($items as $idx => $item): ?>
                                            <tr class="hover:bg-slate-50">
                                                <td class="py-2.5 px-4 text-center text-slate-400"><?= $idx + 1 ?></td>
                                                <td class="py-2.5 px-4">
                                                    <span class="font-semibold text-slate-800"><?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php if (!empty($item['description'])): ?>
                                                        <span class="block text-slate-500 text-[11px] mt-0.5"><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-2.5 px-4 text-center font-mono text-slate-700"><?= (float) $item['quantity'] ?></td>
                                                <td class="py-2.5 px-4 text-right font-mono text-slate-700"><?= number_format((float) $item['unit_price'], 2) ?></td>
                                                <td class="py-2.5 px-4 text-right font-mono font-semibold text-slate-900"><?= number_format((float) $item['amount'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="py-4 text-center text-slate-400">No line items recorded for this invoice.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Complete Activity History Timeline -->
                    <div class="px-6 pb-6 pt-2 border-t border-slate-200">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                                <i class="ph ph-clock-counter-clockwise text-teal-600 text-base"></i>
                                Activity History &amp; Audit Trail
                            </h4>
                            <span class="text-xs text-slate-400"><?= count($activities) ?> recorded event(s)</span>
                        </div>

                        <?php if (!empty($activities)): ?>
                            <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                                <?php foreach ($activities as $act): ?>
                                    <?php
                                    $actBadgeClass = match ($act['action']) {
                                        'CREATED'        => 'bg-teal-500 text-white',
                                        'UPDATED'        => 'bg-blue-500 text-white',
                                        'STATUS_CHANGED' => 'bg-amber-500 text-white',
                                        'SOFT_DELETED'   => 'bg-rose-500 text-white',
                                        'RESTORED'       => 'bg-emerald-600 text-white',
                                        default          => 'bg-slate-500 text-white',
                                    };
                                    ?>
                                    <div class="relative flex items-start gap-4 text-xs">
                                        <div class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 -ml-6 border-2 border-white shadow-sm <?= $actBadgeClass ?>">
                                            <span class="text-[9px] font-bold">&bull;</span>
                                        </div>
                                        <div class="flex-1 bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-1">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($act['description'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase font-bold <?= $actBadgeClass ?>">
                                                        <?= htmlspecialchars($act['action'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </div>
                                                <span class="text-slate-400 font-mono text-[11px]">
                                                    <?= htmlspecialchars(date('d M Y, h:i:s A', strtotime($act['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </div>
                                            <div class="text-slate-600 flex items-center gap-3 text-[11px]">
                                                <span>
                                                    <strong>Actor:</strong>
                                                    <?= htmlspecialchars($act['actor_username'] ?? ('User #' . ($act['actor_id'] ?? 'System')), ENT_QUOTES, 'UTF-8') ?>
                                                    <?= !empty($act['actor_email']) ? '(' . htmlspecialchars($act['actor_email'], ENT_QUOTES, 'UTF-8') . ')' : '' ?>
                                                </span>
                                                <?php if (!empty($act['ip_address'])): ?>
                                                    <span><strong>IP:</strong> <?= htmlspecialchars($act['ip_address'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($act['details_json'])): ?>
                                                <div class="mt-2 pt-2 border-t border-slate-200 text-slate-500 font-mono text-[11px] bg-white p-2 rounded border border-slate-200 overflow-x-auto">
                                                    <?= htmlspecialchars($act['details_json'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-4 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-center text-xs text-slate-400">
                                No granular activity history recorded yet for this invoice. (Initial creation record exists).
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            <?php else: ?>
                <!-- Not Found State -->
                <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
                    <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center mx-auto mb-4">
                        <i class="ph ph-warning-circle text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">No Invoice Found</h3>
                    <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                        No invoice record matched "<strong><?= $searchQuery ?></strong>". Please verify the invoice number and try again.
                    </p>
                    <div class="mt-6">
                        <a href="/admin/invoices/track.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                            <i class="ph ph-arrow-left text-base"></i>
                            Back to All Invoices
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- All Invoices Audit Registry Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">System Invoice Audit Registry</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Comprehensive audit index of active and soft-deleted invoices</p>
                </div>
                <div class="text-xs text-slate-500">
                    Showing <strong><?= count($listData['invoices']) ?></strong> of <strong><?= $listData['total'] ?></strong> total invoices
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-slate-600 uppercase font-bold text-[11px]">
                        <tr>
                            <th class="py-3 px-4">Invoice #</th>
                            <th class="py-3 px-4">Student / Client</th>
                            <th class="py-3 px-4">Total &amp; Due</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Lifecycle</th>
                            <th class="py-3 px-4">Creator Account</th>
                            <th class="py-3 px-4">Created Date</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (!empty($listData['invoices'])): ?>
                            <?php foreach ($listData['invoices'] as $invRow): ?>
                                <?php
                                $rowIsDeleted = !empty($invRow['deleted_at']);
                                $rowStatusBadge = match ($invRow['status']) {
                                    'paid'   => 'bg-emerald-100 text-emerald-800',
                                    'unpaid' => 'bg-amber-100 text-amber-800',
                                    'draft'  => 'bg-slate-100 text-slate-700',
                                    default  => 'bg-slate-100 text-slate-700',
                                };
                                ?>
                                <tr class="hover:bg-slate-50 transition-colors <?= $rowIsDeleted ? 'bg-rose-50/30' : '' ?>">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                        <a href="/admin/invoices/track.php?search=<?= urlencode($invRow['invoice_number']) ?>" class="hover:text-teal-600 hover:underline">
                                            <?= htmlspecialchars($invRow['invoice_number'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-semibold text-slate-800"><?= htmlspecialchars($invRow['student_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if (!empty($invRow['student_email'])): ?>
                                            <span class="block text-slate-400 text-[11px]"><?= htmlspecialchars($invRow['student_email'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 font-mono">
                                        <div class="font-semibold text-slate-900"><?= number_format((float) $invRow['grand_total'], 2) ?> <?= htmlspecialchars($invRow['currency'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if ((float) ($invRow['amount_due'] ?? 0) > 0): ?>
                                            <div class="text-[11px] text-rose-600 font-medium">Due: <?= number_format((float) $invRow['amount_due'], 2) ?></div>
                                        <?php else: ?>
                                            <div class="text-[11px] text-emerald-600 font-medium">Paid in Full</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-1 rounded-full font-semibold text-[10px] uppercase <?= $rowStatusBadge ?>">
                                            <?= htmlspecialchars($invRow['status'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php if ($rowIsDeleted): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200" title="Deleted by <?= htmlspecialchars($invRow['deleter_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="ph ph-trash"></i>
                                                Soft-Deleted
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                <i class="ph ph-check-circle"></i>
                                                Active
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-slate-800 font-medium"><?= htmlspecialchars($invRow['creator_name'] ?? ('Admin #' . $invRow['created_by']), ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">
                                        <?= htmlspecialchars(date('d M Y, h:i A', strtotime($invRow['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="/admin/invoices/track.php?search=<?= urlencode($invRow['invoice_number']) ?>"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-teal-700 bg-teal-50 border border-teal-200 rounded-lg hover:bg-teal-100 transition-colors">
                                            <i class="ph ph-magnifying-glass"></i>
                                            Audit Track
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400">
                                    No invoices matching the selected criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <?php if ($listData['pages'] > 1): ?>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Page <?= $listData['currentPage'] ?> of <?= $listData['pages'] ?>
                    </span>
                    <div class="flex items-center gap-1">
                        <?php for ($p = 1; $p <= $listData['pages']; $p++): ?>
                            <?php
                            $pageUrl = '/admin/invoices/track.php?' . http_build_query(array_merge($_GET, ['page' => $p]));
                            $isActive = ($p === $listData['currentPage']);
                            ?>
                            <a href="<?= htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8') ?>"
                               class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors <?= $isActive ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' ?>">
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

