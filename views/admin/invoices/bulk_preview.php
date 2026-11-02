<?php
/**
 * Bulk Invoice Validation Preview View
 * Displays staged rows, highlights invalid rows, and allows confirming bulk generation.
 */
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$batchUuid    = $batch['batch_uuid'] ?? '';
$totalRows    = (int) ($batch['total_rows'] ?? 0);
$validRows    = (int) ($batch['valid_rows'] ?? 0);
$invalidRows  = (int) ($batch['invalid_rows'] ?? 0);
$filename     = $batch['original_name'] ?? 'Uploaded CSV';
$createdAt    = $batch['created_at'] ?? '';

// Helper for filter links
function filterUrl(string $f, string $uuid): string
{
    return '/admin/invoices/bulk_preview.php?batch=' . urlencode($uuid) . '&filter=' . urlencode($f);
}

function previewPageUrl(int $p, string $uuid, string $f): string
{
    return '/admin/invoices/bulk_preview.php?batch=' . urlencode($uuid) . '&filter=' . urlencode($f) . '&page=' . $p;
}
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
    <div class="max-w-[1600px] mx-auto px-6 py-8 space-y-6">

        <!-- Header & Breadcrumbs -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-[#64748b] mb-1">
                    <a href="/admin/invoices/index.php" class="hover:text-primary transition-colors">Invoices</a>
                    <span>/</span>
                    <a href="/admin/invoices/bulk_upload.php" class="hover:text-primary transition-colors">Bulk Upload</a>
                    <span>/</span>
                    <span class="text-[#0f172a]">Validation Preview</span>
                </div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-[#1e293b] tracking-tight">Validation Preview</h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                        <?= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <p class="text-sm text-[#64748b] mt-1">Review row-by-row validation results before generating invoices into the database.</p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3">
                <a href="/admin/invoices/bulk_upload.php"
                   class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-[#475569] bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Upload Another File
                </a>

                <?php if ($validRows > 0): ?>
                    <form action="/admin/invoices/bulk_generate.php" method="POST" id="generateBulkForm" onsubmit="return confirmGenerate(event);">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="batch_uuid" value="<?= htmlspecialchars($batchUuid, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit"
                                id="generateBtn"
                                class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-bold text-white bg-primary rounded-xl hover:bg-green-700 active:scale-95 transition-all shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span>Generate Bulk Invoices (<?= $validRows ?>)</span>
                        </button>
                    </form>
                <?php else: ?>
                    <button type="button" disabled
                            class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-bold text-gray-400 bg-gray-200 rounded-xl cursor-not-allowed">
                        <span>No Valid Rows to Generate</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <!-- Total Rows -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-[#94a3b8] uppercase tracking-wider mb-0.5">Total Uploaded</p>
                    <p class="text-2xl font-bold text-[#0f172a]"><?= number_format($totalRows) ?></p>
                </div>
            </div>

            <!-- Valid Records -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-0.5">Valid & Ready</p>
                    <p class="text-2xl font-bold text-emerald-700"><?= number_format($validRows) ?></p>
                </div>
            </div>

            <!-- Invalid Records -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-red-600 uppercase tracking-wider mb-0.5">Errors Detected</p>
                    <p class="text-2xl font-bold text-red-700"><?= number_format($invalidRows) ?></p>
                </div>
            </div>
        </div>

        <?php if ($invalidRows > 0): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center justify-between gap-4 text-xs text-amber-800">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span><strong><?= $invalidRows ?> invalid row(s)</strong> will be skipped during generation. You can proceed with valid records or fix errors and re-upload.</span>
                </div>
                <a href="<?= filterUrl('invalid', $batchUuid) ?>" class="font-bold underline text-amber-900 hover:text-amber-700 shrink-0">
                    View Invalid Rows Only
                </a>
            </div>
        <?php endif; ?>

        <!-- Filter Tabs & Staging Table Card -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <!-- Filter Bar -->
            <div class="p-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-gray-50/40">
                <div class="flex items-center gap-2">
                    <a href="<?= filterUrl('all', $batchUuid) ?>"
                       class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors <?= $activeFilter === 'all' ? 'bg-white text-[#0f172a] shadow-sm border border-gray-200' : 'text-[#64748b] hover:text-[#0f172a]' ?>">
                        All Rows (<?= $totalRows ?>)
                    </a>
                    <a href="<?= filterUrl('valid', $batchUuid) ?>"
                       class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors <?= $activeFilter === 'valid' ? 'bg-emerald-50 text-emerald-800 shadow-sm border border-emerald-200' : 'text-[#64748b] hover:text-emerald-700' ?>">
                        Valid Only (<?= $validRows ?>)
                    </a>
                    <a href="<?= filterUrl('invalid', $batchUuid) ?>"
                       class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors <?= $activeFilter === 'invalid' ? 'bg-red-50 text-red-800 shadow-sm border border-red-200' : 'text-[#64748b] hover:text-red-700' ?>">
                        Invalid Only (<?= $invalidRows ?>)
                    </a>
                </div>

                <div class="text-xs text-[#94a3b8]">
                    Showing <?= count($rows) ?> of <?= $totalStaged ?> records
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/70 border-b border-gray-100 text-[#64748b] uppercase tracking-wider font-bold">
                            <th class="py-3 px-4 w-14">#</th>
                            <th class="py-3 px-4 w-24">Status</th>
                            <th class="py-3 px-4">Student Info</th>
                            <th class="py-3 px-4">Item & Description</th>
                            <th class="py-3 px-4 text-right">Qty × Price</th>
                            <th class="py-3 px-4 text-right">Totals</th>
                            <th class="py-3 px-4">Dates</th>
                            <th class="py-3 px-4">Validation Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-12 text-[#94a3b8]">
                                    No records found matching this filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r):
                                $isValid = (bool) $r['is_valid'];
                                $raw     = $r['raw'];
                                $parsed  = $r['parsed'];
                                $rowNum  = $r['row_number'];
                                $error   = $r['validation_error'];

                                $studentName = $parsed['student_name'] ?? ($raw['student_name'] ?? '');
                                $studentEmail = $parsed['student_email'] ?? ($raw['student_email'] ?? '');
                                $studentPhone = $parsed['student_phone'] ?? ($raw['student_phone'] ?? '');

                                $itemName   = $parsed['item_name'] ?? ($raw['item_name'] ?? '');
                                $itemDesc   = $parsed['item_description'] ?? ($raw['item_description'] ?? '');
                                $qty        = $parsed['quantity'] ?? ($raw['quantity'] ?? '1');
                                $price      = $parsed['unit_price'] ?? ($raw['unit_price'] ?? '0');
                                $currency   = $parsed['currency'] ?? ($raw['currency'] ?? 'BDT');

                                $invDate = $parsed['invoice_date'] ?? ($raw['invoice_date'] ?? '');
                                $dueDate = $parsed['due_date'] ?? ($raw['due_date'] ?? '');
                                $subtotal = (float)$qty * (float)$price;
                            ?>
                                <tr class="hover:bg-gray-50/80 transition-colors <?= !$isValid ? 'bg-red-50/20' : '' ?>">
                                    <!-- Row # -->
                                    <td class="py-3.5 px-4 font-bold text-[#64748b]">
                                        <?= (int) $rowNum ?>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="py-3.5 px-4">
                                        <?php if ($isValid): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                                <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Valid
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-red-100 text-red-800">
                                                <svg class="w-3 h-3 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Error
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Student Info -->
                                    <td class="py-3.5 px-4">
                                        <p class="font-bold text-[#1e293b] text-xs">
                                            <?= htmlspecialchars($studentName ?: '—', ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                        <?php if ($studentEmail): ?>
                                            <p class="text-[11px] text-[#64748b]"><?= htmlspecialchars($studentEmail, ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php endif; ?>
                                        <?php if ($studentPhone): ?>
                                            <p class="text-[11px] text-[#94a3b8]"><?= htmlspecialchars($studentPhone, ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Item & Description -->
                                    <td class="py-3.5 px-4 max-w-[240px]">
                                        <p class="font-semibold text-[#1e293b] truncate" title="<?= htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($itemName ?: '—', ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                        <?php if ($itemDesc): ?>
                                            <p class="text-[11px] text-[#94a3b8] truncate" title="<?= htmlspecialchars($itemDesc, ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($itemDesc, ENT_QUOTES, 'UTF-8') ?>
                                            </p>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Qty × Price -->
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <span class="text-[#64748b]"><?= htmlspecialchars((string)$qty, ENT_QUOTES, 'UTF-8') ?> ×</span>
                                        <span class="font-semibold text-[#1e293b]"><?= number_format((float)$price, 2) ?></span>
                                    </td>

                                    <!-- Grand Total -->
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <span class="font-bold text-[#0f172a]"><?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?> <?= number_format($subtotal, 2) ?></span>
                                    </td>

                                    <!-- Dates -->
                                    <td class="py-3.5 px-4 whitespace-nowrap text-[11px]">
                                        <div><span class="text-[#94a3b8]">Inv:</span> <?= htmlspecialchars($invDate ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if ($dueDate): ?>
                                            <div><span class="text-[#94a3b8]">Due:</span> <?= htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Validation Error / Notes -->
                                    <td class="py-3.5 px-4">
                                        <?php if (!$isValid): ?>
                                            <div class="inline-flex items-start gap-1.5 text-red-600 bg-red-50 border border-red-200/60 px-2.5 py-1 rounded-lg text-[11px]">
                                                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <span><?= htmlspecialchars($error ?: 'Validation failed', ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-[11px]">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <?php if ($pages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between text-xs text-[#64748b]">
                    <span>Page <?= $currentPage ?> of <?= $pages ?></span>
                    <div class="flex items-center gap-1.5">
                        <?php if ($currentPage > 1): ?>
                            <a href="<?= previewPageUrl($currentPage - 1, $batchUuid, $activeFilter) ?>"
                               class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 font-semibold">Previous</a>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $pages; $p++): ?>
                            <?php if ($p === $currentPage): ?>
                                <span class="px-3 py-1.5 bg-primary text-white font-bold rounded-lg"><?= $p ?></span>
                            <?php elseif ($p <= 5 || $p === $pages || abs($p - $currentPage) <= 1): ?>
                                <a href="<?= previewPageUrl($p, $batchUuid, $activeFilter) ?>"
                                   class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 font-semibold"><?= $p ?></a>
                            <?php elseif ($p === 6 && $pages > 7): ?>
                                <span class="px-2">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($currentPage < $pages): ?>
                            <a href="<?= previewPageUrl($currentPage + 1, $batchUuid, $activeFilter) ?>"
                               class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 font-semibold">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<script nonce="<?= csp_nonce() ?>">
function confirmGenerate(e) {
    if (!confirm('Are you sure you want to generate <?= $validRows ?> invoices into the database? This process will assign unique sequential invoice numbers.')) {
        e.preventDefault();
        return false;
    }
    const btn = document.getElementById('generateBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            Generating Invoices...
        `;
    }
    return true;
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

