<?php
/**
 * Bulk Invoice Generation Summary Report View
 * Displays final outcome: Total, Successfully Generated, and Failed records.
 */
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$batchUuid    = $batch['batch_uuid'] ?? '';
$totalRows    = (int) ($batch['total_rows'] ?? 0);
$successRows  = (int) ($batch['success_rows'] ?? 0);
$failedRows   = (int) ($batch['failed_rows'] ?? 0);
$filename     = $batch['original_name'] ?? 'CSV Batch';
$status       = $batch['status'] ?? 'completed';

$generatedInvoices = $generatedInvoices ?? [];
$errorRows         = $errorRows ?? [];
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
    <div class="max-w-[1600px] mx-auto px-6 py-8 space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-[#64748b] mb-1">
                    <a href="/admin/invoices/index.php" class="hover:text-primary transition-colors">Invoices</a>
                    <span>/</span>
                    <a href="/admin/invoices/bulk_upload.php" class="hover:text-primary transition-colors">Bulk Upload</a>
                    <span>/</span>
                    <span class="text-[#0f172a]">Summary Report</span>
                </div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-[#1e293b] tracking-tight">Bulk Invoicing Summary Report</h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?= $failedRows === 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                        <?= $failedRows === 0 ? 'Fully Completed' : 'Completed with Exceptions' ?>
                    </span>
                </div>
                <p class="text-sm text-[#64748b] mt-1">Batch processing report for <strong><?= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') ?></strong>.</p>
            </div>

            <!-- Top Action Buttons -->
            <div class="flex items-center gap-3">
                <a href="/admin/invoices/bulk_upload.php"
                   class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-[#475569] bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Upload Another Batch
                </a>
                <a href="/admin/invoices/index.php"
                   class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-primary rounded-xl hover:bg-green-700 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    View Invoice Dashboard
                </a>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <!-- Total Records -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-[#94a3b8] uppercase tracking-wider mb-1">Total Records in Batch</p>
                    <p class="text-3xl font-bold text-[#0f172a]"><?= number_format($totalRows) ?></p>
                </div>
            </div>

            <!-- Successfully Generated -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-1">Successfully Generated</p>
                    <p class="text-3xl font-bold text-emerald-700"><?= number_format($successRows) ?></p>
                </div>
            </div>

            <!-- Failed / Skipped Records -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-red-600 uppercase tracking-wider mb-1">Failed / Skipped</p>
                    <p class="text-3xl font-bold text-red-700"><?= number_format($failedRows) ?></p>
                </div>
            </div>
        </div>

        <!-- If Errors Exist, Show Download Error Report Card -->
        <?php if (!empty($errorRows)): ?>
            <div class="bg-red-50/80 border border-red-200 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-red-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-bold text-red-900"><?= count($errorRows) ?> records could not be generated</h3>
                        <p class="text-xs text-red-700 mt-0.5">Download the error report CSV to review exact errors, fix the values, and re-upload.</p>
                    </div>
                </div>
                <a href="/admin/invoices/bulk_errors.php?batch=<?= urlencode($batchUuid) ?>"
                   class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors shadow-sm self-start sm:self-auto shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download Error CSV Report
                </a>
            </div>
        <?php endif; ?>

        <!-- Table 1: Successfully Generated Invoices -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-sm">
                        <?= count($generatedInvoices) ?>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-[#0f172a]">Generated Invoices</h2>
                        <p class="text-xs text-[#64748b]">All database records created from this bulk batch</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/70 border-b border-gray-100 text-[#64748b] uppercase tracking-wider font-bold">
                            <th class="py-3 px-4 w-12">#</th>
                            <th class="py-3 px-4">Invoice #</th>
                            <th class="py-3 px-4">Student Name</th>
                            <th class="py-3 px-4 text-right">Grand Total</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4">Invoice Date</th>
                            <th class="py-3 px-4">Due Date</th>
                            <th class="py-3 px-4 text-center">Quick Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($generatedInvoices)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-10 text-[#94a3b8]">
                                    No invoices were generated in this batch.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($generatedInvoices as $inv): ?>
                                <tr class="hover:bg-gray-50/80 transition-colors">
                                    <td class="py-3.5 px-4 font-bold text-[#94a3b8]">
                                        <?= (int) ($inv['row_number'] ?? 0) ?>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <a href="/admin/invoices/view.php?id=<?= (int)$inv['invoice_id'] ?>"
                                           class="font-mono font-bold text-primary hover:underline">
                                            <?= htmlspecialchars($inv['invoice_number'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td class="py-3.5 px-4 font-semibold text-[#1e293b]">
                                        <?= htmlspecialchars($inv['student_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-bold text-[#0f172a]">
                                        <?= htmlspecialchars($inv['currency'], ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)$inv['grand_total'], 2) ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <?php if ($inv['status'] === 'paid'): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">Paid</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800">Unpaid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-[#64748b]">
                                        <?= htmlspecialchars($inv['invoice_date'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-[#64748b]">
                                        <?= htmlspecialchars($inv['due_date'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="inline-flex items-center gap-2">
                                            <a href="/admin/invoices/view.php?id=<?= (int)$inv['invoice_id'] ?>"
                                               class="p-1 text-[#64748b] hover:text-primary transition-colors" title="View Details">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <a href="/admin/invoices/print.php?id=<?= (int)$inv['invoice_id'] ?>" target="_blank"
                                               class="p-1 text-[#64748b] hover:text-emerald-600 transition-colors" title="Print Invoice">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

