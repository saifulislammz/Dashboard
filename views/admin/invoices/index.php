<?php
/**
 * Invoice Dashboard View ” Lists all invoices with stats & filters.
 * Accessible only by ROLE_ADMIN via public/admin/invoices/index.php
 */
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$invoices = $listData['invoices'] ?? [];
$total = $listData['total'] ?? 0;
$pages = $listData['pages'] ?? 1;
$currentPage = $listData['currentPage'] ?? 1;
$perPage = $listData['perPage'] ?? 15;
$activeFilters = $listData['filters'] ?? [];

$statsTotal = $stats['total_count'] ?? 0;
$currencyTotals = $stats['currency_totals'] ?? [];

// Notification banners
$deleted = isset($_GET['deleted']) && $_GET['deleted'] === '1';
$created = isset($_GET['created']) && $_GET['created'] === '1';

// Status badge helper
function statusBadge(string $s): string
{
    return match ($s) {
        'paid' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Paid</span>',
        'unpaid' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">Unpaid</span>',
        'draft' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">Draft</span>',
        default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">' . htmlspecialchars($s, ENT_QUOTES, 'UTF-8') . '</span>',
    };
}

// Pagination URL helper
function pageUrl(int $p): string
{
    $params = $_GET;
    $params['page'] = $p;
    return '/admin/invoices/index.php?' . http_build_query($params);
}
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
    <div class="max-w-[1600px] mx-auto px-6 py-8 space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#1e293b] tracking-tight">Invoice Dashboard</h1>
                <p class="text-sm text-[#64748b] mt-1">View, manage and track all generated invoices.</p>
            </div>
            <a href="/admin/invoices/create.php"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-primary rounded-xl hover:bg-green-700 transition-colors shadow-sm self-start sm:self-auto">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Generate Invoice
            </a>
        </div>

        <?php if ($deleted): ?>
            <div
                class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center gap-2 text-sm text-green-700">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Invoice deleted successfully.
            </div>
        <?php endif; ?>

        <!-- =============================================
             STATS CARDS
        ============================================== -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

            <!-- Total Invoices -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-green-50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-[#94a3b8] uppercase tracking-wider mb-1">Total Invoices</p>
                    <p class="text-3xl font-bold text-[#0f172a] leading-none">
                        <?= htmlspecialchars((string) $statsTotal, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <!-- Total Amount by Currency -->
            <?php if (empty($currencyTotals)): ?>
                <div
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-yellow-50 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-[#94a3b8] uppercase tracking-wider mb-1">Total Amount</p>
                        <p class="text-xl font-semibold text-[#64748b]">No invoices yet</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <p class="text-xs font-bold text-[#94a3b8] uppercase tracking-wider mb-4">Total Revenue</p>
                    <div class="space-y-2">
                        <?php foreach ($currencyTotals as $ct):
                            $code = htmlspecialchars($ct['currency'], ENT_QUOTES, 'UTF-8');
                            $amount = number_format((float) $ct['total'], 2);
                            $info = $currencies[$ct['currency']] ?? null;
                            $flag = $info ? $info['flag'] : '💰';
                            ?>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-[#475569] flex items-center gap-1.5">
                                    <span><?= $flag ?></span>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 text-xs font-mono font-bold"><?= $code ?></span>
                                </span>
                                <span class="text-sm font-bold text-[#0f172a]"><?= $amount ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div><!-- /stats -->

        <!-- =============================================
             FILTER BAR
        ============================================== -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <form method="GET" action="/admin/invoices/index.php" class="flex flex-wrap items-end gap-3">

                <!-- Search -->
                <div class="flex-1 min-w-[200px]">
                    <label
                        class="block text-xs font-semibold text-[#94a3b8] mb-1.5 uppercase tracking-wider">Search</label>
                    <div class="relative">
                        <input type="text" name="search" id="filter-search"
                            value="<?= htmlspecialchars($activeFilters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Invoice # or student name¦"
                            oninput="if(this.value.trim() === '') this.form.submit()"
                            class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Status -->
                <div class="min-w-[130px]">
                    <label
                        class="block text-xs font-semibold text-[#94a3b8] mb-1.5 uppercase tracking-wider">Status</label>
                    <select name="status" id="filter-status"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-white appearance-none cursor-pointer">
                        <option value="">All Status</option>
                        <?php foreach (['unpaid' => 'Unpaid', 'paid' => 'Paid', 'draft' => 'Draft'] as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($activeFilters['status'] ?? '') === $val ? 'selected' : '' ?>>
                                <?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Currency -->
                <div class="min-w-[130px]">
                    <label
                        class="block text-xs font-semibold text-[#94a3b8] mb-1.5 uppercase tracking-wider">Currency</label>
                    <select name="currency" id="filter-currency"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-white appearance-none cursor-pointer">
                        <option value="">All Currencies</option>
                        <?php foreach ($currencies as $code => $info): ?>
                            <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"
                                <?= ($activeFilters['currency'] ?? '') === $code ? 'selected' : '' ?>>
                                <?= htmlspecialchars($info['flag'] . ' ' . $code, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-xs font-semibold text-[#94a3b8] mb-1.5 uppercase tracking-wider">From
                        Date</label>
                    <input type="date" name="date_from" id="filter-date-from"
                        value="<?= htmlspecialchars($activeFilters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-xs font-semibold text-[#94a3b8] mb-1.5 uppercase tracking-wider">To
                        Date</label>
                    <input type="date" name="date_to" id="filter-date-to"
                        value="<?= htmlspecialchars($activeFilters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                </div>

                <!-- Buttons -->
                <div class="flex gap-2">
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-xl hover:bg-green-700 transition-colors flex items-center gap-1.5 shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>
                    <a href="/admin/invoices/index.php"
                        class="px-4 py-2 text-sm font-medium text-[#64748b] border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset
                    </a>
                </div>

            </form>
        </div><!-- /filter bar -->

        <!-- =============================================
             INVOICES TABLE
        ============================================== -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <?php if (empty($invoices)): ?>
                <div class="py-20 text-center text-[#94a3b8]">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-base font-semibold text-[#64748b]">No invoices found.</p>
                    <p class="text-sm mt-1">
                        <?= !empty(array_filter($activeFilters)) ? 'Try adjusting your filters.' : 'Generate your first invoice to get started.' ?>
                    </p>
                    <?php if (empty(array_filter($activeFilters))): ?>
                        <a href="/admin/invoices/create.php"
                            class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-primary rounded-xl hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Generate First Invoice
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/60">
                                <th
                                    class="text-left px-4 py-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider">
                                    Invoice #</th>
                                <th
                                    class="text-left px-4 py-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider">
                                    Student</th>
                                <th
                                    class="text-center px-4 py-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider">
                                    Currency</th>
                                <th
                                    class="text-right px-4 py-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider">
                                    Amount</th>
                                <th
                                    class="text-center px-4 py-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="text-center px-4 py-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider hidden lg:table-cell">
                                    Invoice Date</th>
                                <th
                                    class="text-center px-4 py-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider hidden lg:table-cell">
                                    Due Date</th>
                                <th
                                    class="text-center px-4 py-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($invoices as $inv):
                                $id = (int) $inv['id'];
                                $invNo = htmlspecialchars($inv['invoice_number'], ENT_QUOTES, 'UTF-8');
                                $name = htmlspecialchars($inv['student_name'], ENT_QUOTES, 'UTF-8');
                                $email = htmlspecialchars($inv['student_email'] ?? '', ENT_QUOTES, 'UTF-8');
                                $curr = htmlspecialchars($inv['currency'], ENT_QUOTES, 'UTF-8');
                                $amount = number_format((float) $inv['grand_total'], 2);
                                $iDate = htmlspecialchars($inv['invoice_date'] ?? '', ENT_QUOTES, 'UTF-8');
                                $dDate = htmlspecialchars($inv['due_date'] ?? '-', ENT_QUOTES, 'UTF-8');
                                $status = $inv['status'] ?? 'unpaid';
                                $flag = $currencies[$inv['currency']]['flag'] ?? '💰';
                                ?>
                                <tr class="hover:bg-gray-50/80 transition-colors group">
                                    <!-- Invoice Number -->
                                    <td class="px-4 py-3.5">
                                        <a href="/admin/invoices/view.php?id=<?= $id ?>"
                                            class="font-mono text-primary hover:text-green-700 font-semibold text-xs hover:underline">
                                            <?= $invNo ?>
                                        </a>
                                    </td>
                                    <!-- Student -->
                                    <td class="px-4 py-3.5">
                                        <p class="font-medium text-[#0f172a]"><?= $name ?></p>
                                        <?php if ($email): ?>
                                            <p class="text-xs text-[#94a3b8] mt-0.5"><?= $email ?></p><?php endif; ?>
                                    </td>
                                    <!-- Currency Badge -->
                                    <td class="px-4 py-3.5 text-center">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-bold font-mono">
                                            <?= $flag ?>         <?= $curr ?>
                                        </span>
                                    </td>
                                    <!-- Amount -->
                                    <td class="px-4 py-3.5 text-right font-semibold text-[#0f172a] font-mono"><?= $amount ?>
                                    </td>
                                    <!-- Status -->
                                    <td class="px-4 py-3.5 text-center"><?= statusBadge($status) ?></td>
                                    <!-- Invoice Date -->
                                    <td class="px-4 py-3.5 text-center text-[#64748b] hidden lg:table-cell"><?= $iDate ?></td>
                                    <!-- Due Date -->
                                    <td class="px-4 py-3.5 text-center text-[#64748b] hidden lg:table-cell"><?= $dDate ?></td>
                                    <!-- Actions -->
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- View -->
                                            <a href="/admin/invoices/view.php?id=<?= $id ?>" title="View Invoice"
                                                class="w-9 h-9 flex items-center justify-center rounded-lg text-indigo-600 bg-indigo-50 hover:bg-indigo-600 hover:text-white hover:shadow-md hover:-translate-y-0.5 transform transition-all duration-200">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <!-- Print/Download -->
                                            <a href="/admin/invoices/print.php?id=<?= $id ?>" target="_blank"
                                                title="Print / Download PDF"
                                                class="w-9 h-9 flex items-center justify-center rounded-lg text-teal-600 bg-teal-50 hover:bg-teal-600 hover:text-white hover:shadow-md hover:-translate-y-0.5 transform transition-all duration-200">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                            </a>
                                            <!-- Delete -->
                                            <form method="POST" action="/admin/invoices/delete.php"
                                                onsubmit="return handleConfirm(event, 'Delete invoice <?= $invNo ?>? This cannot be undone.')">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= $id ?>">
                                                <button type="submit" title="Delete Invoice"
                                                    class="w-9 h-9 flex items-center justify-center rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white hover:shadow-md hover:-translate-y-0.5 transform transition-all duration-200">
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div><!-- /overflow-x-auto -->

                <!-- Table Footer: count + pagination -->
                <div
                    class="px-4 py-3 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-xs text-[#94a3b8]">
                        Showing <?= (($currentPage - 1) * $perPage) + 1 ?> “ <?= min($total, $currentPage * $perPage) ?> of
                        <?= $total ?> invoices
                    </p>

                    <?php if ($pages > 1): ?>
                        <nav class="flex items-center gap-1" aria-label="Invoice pagination">
                            <?php if ($currentPage > 1): ?>
                                <a href="<?= pageUrl($currentPage - 1) ?>"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-[#64748b] hover:bg-gray-50 transition-colors text-sm">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $currentPage - 2);
                            $end = min($pages, $currentPage + 2);
                            if ($start > 1): ?>
                                <a href="<?= pageUrl(1) ?>"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-[#64748b] hover:bg-gray-50 text-sm">1</a>
                                <?php if ($start > 2): ?><span class="text-[#94a3b8] px-1">¦</span><?php endif; ?>
                            <?php endif; ?>

                            <?php for ($p = $start; $p <= $end; $p++): ?>
                                <a href="<?= pageUrl($p) ?>"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium transition-colors <?= $p === $currentPage ? 'bg-primary text-white shadow-sm' : 'border border-gray-200 text-[#64748b] hover:bg-gray-50' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($end < $pages): ?>
                                <?php if ($end < $pages - 1): ?><span class="text-[#94a3b8] px-1">¦</span><?php endif; ?>
                                <a href="<?= pageUrl($pages) ?>"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-[#64748b] hover:bg-gray-50 text-sm"><?= $pages ?></a>
                            <?php endif; ?>

                            <?php if ($currentPage < $pages): ?>
                                <a href="<?= pageUrl($currentPage + 1) ?>"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-[#64748b] hover:bg-gray-50 transition-colors text-sm">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        </div><!-- /table card -->

    </div><!-- /max-w -->
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>