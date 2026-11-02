<?php
/**
 * Invoice Edit View — Update Invoice
 * Accessible only by ROLE_ADMIN via public/admin/invoices/edit.php
 */
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$invoice = $invoice ?? [];
$items   = $items   ?? [];
$errors  = $errors  ?? [];

/**
 * Helper: get invoice field with fallback to default
 */
function inv_val(string $key, $default = ''): string
{
    global $invoice;
    return htmlspecialchars((string) ($invoice[$key] ?? $default), ENT_QUOTES, 'UTF-8');
}

function inv_err(string $key): string
{
    global $errors;
    return isset($errors[$key])
        ? '<p class="mt-1 text-xs text-red-600">' . htmlspecialchars($errors[$key], ENT_QUOTES, 'UTF-8') . '</p>'
        : '';
}
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
    <div class="max-w-[1400px] mx-auto px-6 py-8 space-y-6">

        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[#1e293b] tracking-tight">Edit Invoice #<?= inv_val('invoice_number') ?></h1>
                <p class="text-sm text-[#64748b] mt-1">Modify invoice details, line items, discounts, or signature preference.</p>
            </div>
            <a href="/admin/invoices/view.php?id=<?= (int)($invoice['id'] ?? 0) ?>"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-[#64748b] bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Invoice
            </a>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 text-sm text-red-700 space-y-1">
            <p class="font-semibold flex items-center gap-2">
                <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Please correct the errors below:
            </p>
            <ul class="list-disc list-inside space-y-0.5 text-xs">
                <?php foreach ($errors as $field => $msg): ?>
                <li><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" action="/admin/invoices/edit.php?id=<?= (int)($invoice['id'] ?? 0) ?>" id="invoice-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="<?= (int)($invoice['id'] ?? 0) ?>">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- =============================================
                     SECTION 1: Student Information
                ============================================== -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                    <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <h2 class="text-base font-semibold text-[#0f172a]">Bill To (Student Information)</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Student Name -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="student_name">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="student_name" name="student_name"
                                   value="<?= inv_val('student_name') ?>"
                                   placeholder="e.g. Abdullah Al Mamun"
                                   class="w-full px-4 py-2.5 text-sm border <?= isset($errors['student_name']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?> rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                   required maxlength="150">
                            <?= inv_err('student_name') ?>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="student_email">Email Address</label>
                            <input type="email" id="student_email" name="student_email"
                                   value="<?= inv_val('student_email') ?>"
                                   placeholder="student@example.com"
                                   class="w-full px-4 py-2.5 text-sm border <?= isset($errors['student_email']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?> rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            <?= inv_err('student_email') ?>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="student_phone">Phone Number</label>
                            <input type="text" id="student_phone" name="student_phone"
                                   value="<?= inv_val('student_phone') ?>"
                                   placeholder="+880 1XXX-XXXXXX"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                        </div>

                        <!-- Country -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="student_country">Country / City</label>
                            <input type="text" id="student_country" name="student_country"
                                   value="<?= inv_val('student_country') ?>"
                                   placeholder="e.g. Bangladesh / Dhaka"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                        </div>
                    </div>
                </div>

                <!-- =============================================
                     SECTION 2: Invoice Details
                ============================================== -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                    <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <h2 class="text-base font-semibold text-[#0f172a]">Invoice Details</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Invoice Number (Immutable in Edit to protect accounting integrity) -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="invoice_number">Invoice Number</label>
                            <input type="text" id="invoice_number" name="invoice_number"
                                   value="<?= inv_val('invoice_number') ?>"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 text-gray-500 font-mono font-bold cursor-not-allowed"
                                   readonly>
                            <p class="text-[11px] text-gray-400 mt-1">Invoice number is locked for sequence integrity.</p>
                        </div>

                        <!-- Invoice Date -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="invoice_date">
                                Invoice Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="invoice_date" name="invoice_date"
                                   value="<?= inv_val('invoice_date') ?>"
                                   class="w-full px-4 py-2.5 text-sm border <?= isset($errors['invoice_date']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?> rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                   required>
                            <?= inv_err('invoice_date') ?>
                        </div>

                        <!-- Due Date -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="due_date">Due Date</label>
                            <input type="date" id="due_date" name="due_date"
                                   value="<?= inv_val('due_date') ?>"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="status">Status</label>
                            <select id="status" name="status"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white appearance-none cursor-pointer">
                                <?php $curStatus = $invoice['status'] ?? 'unpaid'; ?>
                                <option value="unpaid" <?= $curStatus === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                <option value="paid"   <?= $curStatus === 'paid'   ? 'selected' : '' ?>>Paid</option>
                                <option value="draft"  <?= $curStatus === 'draft'  ? 'selected' : '' ?>>Draft</option>
                            </select>
                        </div>

                        <!-- Notes -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="notes">Notes (Optional)</label>
                            <textarea id="notes" name="notes" rows="2"
                                      placeholder="Any additional notes for the student..."
                                      class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all resize-none"><?= inv_val('notes') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 SECTION 3: Service Items
            ============================================== -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5 mt-6">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <h2 class="text-base font-semibold text-[#0f172a]">Service / Course Items</h2>
                    </div>
                    <button type="button" id="add-item-btn"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary rounded-xl hover:bg-green-700 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Item
                    </button>
                </div>

                <?php if (isset($errors['items'])): ?>
                <p class="text-xs text-red-600"><?= htmlspecialchars($errors['items'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>

                <!-- Items Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="items-table">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left pb-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider w-8">Sl</th>
                                <th class="text-left pb-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider">Course / Service</th>
                                <th class="text-right pb-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider w-20">Qty</th>
                                <th class="text-right pb-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider w-28">Unit Price</th>
                                <th class="text-right pb-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider w-28">Amount</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            <!-- Rows injected by JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div id="items-empty" class="py-10 text-center text-[#94a3b8] hidden">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    <p class="text-sm font-medium">No items added yet.</p>
                    <p class="text-xs mt-1">Click <strong>Add Item</strong> to add a course or service.</p>
                </div>
            </div>

            <!-- =============================================
                 SECTION 4: Currency & Summary
            ============================================== -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
                <!-- Currency -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                    <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-yellow-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h2 class="text-base font-semibold text-[#0f172a]">Currency</h2>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[#374151] mb-1.5" for="currency">
                            Select Currency <span class="text-red-500">*</span>
                        </label>
                        <select id="currency" name="currency"
                                class="w-full px-4 py-2.5 text-sm border <?= isset($errors['currency']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?> rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white appearance-none cursor-pointer"
                                required>
                            <?php
                            $selectedCurr = strtoupper((string)($invoice['currency'] ?? 'BDT'));
                            foreach ($currencies as $code => $info):
                            ?>
                            <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"
                                    data-symbol="<?= htmlspecialchars($info['symbol'], ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $selectedCurr === $code ? 'selected' : '' ?>>
                                <?= htmlspecialchars($info['flag'] . ' ' . $code . ' — ' . $info['name'] . ' (' . $info['symbol'] . ')', ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?= inv_err('currency') ?>
                    </div>
                </div>

                <!-- Totals & Summary -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                    <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <h2 class="text-base font-semibold text-[#0f172a]">Invoice Summary</h2>
                    </div>

                    <!-- Discount & VAT Inputs -->
                    <div class="grid grid-cols-2 gap-4 pb-4 border-b border-gray-100">
                        <div>
                            <label class="block text-xs font-medium text-[#64748b] mb-1" for="discount">Flat Discount</label>
                            <input type="number" id="discount" name="discount"
                                   value="<?= inv_val('discount', '0.00') ?>" min="0" step="0.01"
                                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-right font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-[#64748b] mb-1" for="vat_percent">VAT / Tax (%)</label>
                            <input type="number" id="vat_percent" name="vat_percent"
                                   value="<?= inv_val('vat_percent', '0.00') ?>" min="0" max="100" step="0.01"
                                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-right font-medium">
                        </div>
                    </div>

                    <!-- Computed summary rows -->
                    <div class="space-y-2 text-sm text-[#475569]">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span class="font-semibold text-[#0f172a]" id="summary-subtotal">0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>VAT Amount</span>
                            <span class="font-semibold text-[#0f172a]" id="summary-vat-amount">0.00</span>
                        </div>
                        <div class="flex justify-between text-base font-bold text-[#0f172a] pt-2 border-t border-gray-100">
                            <span>Grand Total</span>
                            <span id="summary-grand-total">0.00</span>
                        </div>

                        <!-- Amount Paid Input Row -->
                        <div class="pt-3 border-t border-gray-100">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-[#374151]" for="amount_paid">Amount Paid</label>
                                    <p class="text-[11px] text-[#94a3b8]">Amount collected from student</p>
                                </div>
                                <div class="w-36">
                                    <input type="number" id="amount_paid" name="amount_paid"
                                           value="<?= inv_val('amount_paid', '0.00') ?>" min="0" step="0.01"
                                           class="w-full px-3 py-2 text-sm font-semibold text-right border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-emerald-700 bg-emerald-50/40">
                                </div>
                            </div>
                            <?= inv_err('amount_paid') ?>
                        </div>

                        <!-- DUE Badge / Indicator -->
                        <div id="due-section" class="flex justify-between items-center text-sm font-bold text-red-600 bg-red-50 px-3 py-2 rounded-xl mt-2">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Balance DUE
                            </span>
                            <span id="summary-due" class="text-base font-extrabold font-mono">0.00</span>
                        </div>

                        <!-- Fully Paid badge (hidden when due > 0) -->
                        <div id="fully-paid-section" class="hidden justify-between items-center text-sm font-bold text-emerald-700 bg-emerald-50 px-3 py-2 rounded-xl mt-2">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Payment Status
                            </span>
                            <span class="text-xs uppercase tracking-wider font-extrabold bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full">Fully Paid</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 ACTION BAR
            ============================================== -->
            <div class="mt-6 flex items-center justify-between bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-4">
                <a href="/admin/invoices/view.php?id=<?= (int)($invoice['id'] ?? 0) ?>"
                   class="px-5 py-2.5 text-sm font-medium text-[#64748b] border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer border-r border-gray-200 pr-6 select-none group">
                        <input type="checkbox" name="show_signature" value="1" <?= !empty($invoice['show_signature']) ? 'checked' : '' ?>
                               class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                        <span class="text-sm font-medium text-[#475569] group-hover:text-primary transition-colors">Include Authorized Signature</span>
                    </label>
                    <button type="submit"
                            class="px-7 py-2.5 text-sm font-semibold text-white bg-primary rounded-xl hover:bg-green-700 transition-colors flex items-center gap-2 shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Update Invoice
                    </button>
                </div>
            </div>

        </form><!-- /invoice-form -->
    </div><!-- /max-w -->
</main>

<!-- ================================================================
     ITEM ROW TEMPLATE (hidden, cloned by JS)
================================================================ -->
<template id="item-row-tpl">
    <tr class="item-row border-b border-gray-50 group/row" data-index="__IDX__">
        <td class="py-3 pr-2 text-xs font-medium text-[#94a3b8] align-top pt-4 row-num">__NUM__</td>
        <td class="py-3 pr-2 align-top">
            <input type="text" name="items[__IDX__][item_name]" placeholder="Course or service name"
                   class="item-name w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" required>
        </td>
        <td class="py-3 pr-2 align-top">
            <input type="number" name="items[__IDX__][quantity]" value="1" min="1" step="1"
                   class="item-qty w-full px-3 py-2 text-sm text-right border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
        </td>
        <td class="py-3 pr-2 align-top">
            <input type="number" name="items[__IDX__][unit_price]" value="0.00" min="0" step="0.01"
                   class="item-price w-full px-3 py-2 text-sm text-right border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
        </td>
        <td class="py-3 pr-2 align-top">
            <input type="number" name="items[__IDX__][amount]" value="0.00" readonly
                   class="item-amount w-full px-3 py-2 text-sm text-right border border-gray-100 rounded-lg bg-gray-50 text-[#0f172a] font-medium cursor-not-allowed">
        </td>
        <td class="py-3 align-top">
            <button type="button" class="remove-item-btn w-8 h-8 flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </td>
    </tr>
</template>

<!-- ================================================================
     JAVASCRIPT — Live invoice calculator
================================================================ -->
<script nonce="<?php echo csp_nonce(); ?>">
(function () {
    'use strict';

    let itemIndex = 0;

    const addBtn       = document.getElementById('add-item-btn');
    const tbody        = document.getElementById('items-body');
    const emptyState   = document.getElementById('items-empty');
    const tpl          = document.getElementById('item-row-tpl');
    const discountEl   = document.getElementById('discount');
    const vatEl        = document.getElementById('vat_percent');
    const amountPaidEl = document.getElementById('amount_paid');
    const subtotalEl   = document.getElementById('summary-subtotal');
    const vatAmtEl     = document.getElementById('summary-vat-amount');
    const grandTotalEl = document.getElementById('summary-grand-total');
    const dueEl        = document.getElementById('summary-due');
    const dueSectionEl = document.getElementById('due-section');
    const paidSectionEl = document.getElementById('fully-paid-section');

    function addRow(name = '', desc = '', qty = 1, price = 0) {
        const html = tpl.innerHTML
            .replaceAll('__IDX__', itemIndex)
            .replaceAll('__NUM__', itemIndex + 1);
        tbody.insertAdjacentHTML('beforeend', html);
        const row = tbody.lastElementChild;

        if (name)  row.querySelector('.item-name').value  = name;
        if (price) row.querySelector('.item-price').value = parseFloat(price).toFixed(2);
        if (qty)   row.querySelector('.item-qty').value   = parseInt(qty, 10);

        recalcRow(row);
        itemIndex++;
        toggleEmpty();
        recalcTotals();
    }

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-item-btn');
        if (!btn) return;
        btn.closest('tr').remove();
        renumberRows();
        toggleEmpty();
        recalcTotals();
    });

    tbody.addEventListener('input', function (e) {
        const row = e.target.closest('tr.item-row');
        if (row) recalcRow(row);
        recalcTotals();
    });

    discountEl.addEventListener('input', recalcTotals);
    vatEl.addEventListener('input', recalcTotals);
    amountPaidEl.addEventListener('input', recalcTotals);

    function recalcRow(row) {
        const qty   = parseInt(row.querySelector('.item-qty').value, 10) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const amt   = qty * price;
        row.querySelector('.item-amount').value = amt.toFixed(2);
    }

    function recalcTotals() {
        let subtotal = 0;
        tbody.querySelectorAll('.item-amount').forEach(function (el) {
            subtotal += parseFloat(el.value) || 0;
        });

        const discount   = Math.max(0, parseFloat(discountEl.value) || 0);
        const vatPercent = Math.max(0, Math.min(100, parseFloat(vatEl.value) || 0));
        const afterDisc  = Math.max(0, subtotal - discount);
        const vatAmount  = afterDisc * (vatPercent / 100);
        const grandTotal = afterDisc + vatAmount;

        let paid = Math.max(0, parseFloat(amountPaidEl.value) || 0);
        if (paid > grandTotal) {
            paid = grandTotal;
        }

        const due = Math.max(0, grandTotal - paid);

        subtotalEl.textContent   = subtotal.toFixed(2);
        vatAmtEl.textContent     = vatAmount.toFixed(2);
        grandTotalEl.textContent = grandTotal.toFixed(2);
        dueEl.textContent        = due.toFixed(2);

        if (due <= 0.001) {
            dueSectionEl.classList.add('hidden');
            dueSectionEl.classList.remove('flex');
            paidSectionEl.classList.remove('hidden');
            paidSectionEl.classList.add('flex');
        } else {
            dueSectionEl.classList.remove('hidden');
            dueSectionEl.classList.add('flex');
            paidSectionEl.classList.add('hidden');
            paidSectionEl.classList.remove('flex');
        }
    }

    function renumberRows() {
        tbody.querySelectorAll('tr.item-row').forEach(function (row, i) {
            row.querySelector('.row-num').textContent = i + 1;
            row.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
            });
            row.dataset.index = i;
        });
    }

    function toggleEmpty() {
        const hasRows = tbody.querySelectorAll('tr.item-row').length > 0;
        emptyState.style.display = hasRows ? 'none' : 'block';
        document.querySelector('#items-table thead').style.display = hasRows ? '' : 'none';
    }

    addBtn.addEventListener('click', function () { addRow(); });

    // Populate existing invoice items into table
    <?php if (!empty($items) && is_array($items)): ?>
    <?php foreach ($items as $item): ?>
    addRow(
        <?= json_encode($item['item_name']   ?? '') ?>,
        <?= json_encode($item['description'] ?? '') ?>,
        <?= json_encode($item['quantity']    ?? 1) ?>,
        <?= json_encode($item['unit_price']  ?? 0) ?>
    );
    <?php endforeach; ?>
    <?php else: ?>
    addRow();
    <?php endif; ?>

    toggleEmpty();
    recalcTotals();

}());
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

