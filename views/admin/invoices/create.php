<?php
/**
 * Invoice Generator View — Create/Generate Invoice
 * Accessible only by ROLE_ADMIN via public/admin/invoices/create.php
 */
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

// Repopulate old values after failed submission
$old      = $old      ?? [];
$errors   = $errors   ?? [];
$todayDate = date('Y-m-d');
$dueDateDefault = date('Y-m-d', strtotime('+7 days'));

/**
 * Helper: safe old value with htmlspecialchars
 */
function inv_old(string $key, $default = ''): string
{
    global $old;
    return htmlspecialchars((string) ($old[$key] ?? $default), ENT_QUOTES, 'UTF-8');
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
                <h1 class="text-2xl font-bold text-[#1e293b] tracking-tight">Generate Invoice</h1>
                <p class="text-sm text-[#64748b] mt-1">Create a new payment invoice for a student manually.</p>
            </div>
            <a href="/admin/invoices/index.php"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-[#64748b] bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Dashboard
            </a>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-red-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="text-sm font-semibold text-red-700">Please fix the errors below before continuing.</p>
                <?php foreach ($errors as $err): ?>
                    <p class="text-xs text-red-600 mt-0.5">&bull; <?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <form id="invoice-form" method="POST" action="/admin/invoices/create.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                <!-- =============================================
                     SECTION 1: Customer Information
                ============================================== -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
                    <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <h2 class="text-base font-semibold text-[#0f172a]">Customer Information</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Student Name -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="student_name">
                                Student Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="student_name" name="student_name"
                                   value="<?= inv_old('student_name') ?>"
                                   placeholder="Enter full student name"
                                   class="w-full px-4 py-2.5 text-sm border <?= isset($errors['student_name']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?> rounded-xl focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all"
                                   required>
                            <?= inv_err('student_name') ?>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="student_email">Email Address</label>
                            <input type="email" id="student_email" name="student_email"
                                   value="<?= inv_old('student_email') ?>"
                                   placeholder="student@example.com"
                                   class="w-full px-4 py-2.5 text-sm border <?= isset($errors['student_email']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?> rounded-xl focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all">
                            <?= inv_err('student_email') ?>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="student_phone">Phone Number</label>
                            <input type="text" id="student_phone" name="student_phone"
                                   value="<?= inv_old('student_phone') ?>"
                                   placeholder="+880 1XXX-XXXXXX"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all">
                        </div>

                        <!-- Country -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="student_country">Country</label>
                            <input type="text" id="student_country" name="student_country"
                                   value="<?= inv_old('student_country', 'Bangladesh') ?>"
                                   placeholder="e.g. Bangladesh"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all">
                        </div>
                    </div>
                </div>

                <!-- =============================================
                     SECTION 2: Invoice Information
                ============================================== -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
                    <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <h2 class="text-base font-semibold text-[#0f172a]">Invoice Information</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Invoice Number -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="invoice_number">Invoice Number</label>
                            <input type="text" id="invoice_number" name="invoice_number"
                                   value="<?= inv_old('invoice_number', htmlspecialchars($invoiceNumber ?? '', ENT_QUOTES, 'UTF-8')) ?>"
                                   placeholder="Auto-generated"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all font-mono bg-gray-50">
                            <p class="mt-1 text-xs text-[#94a3b8]">Auto-generated. You may override it.</p>
                        </div>

                        <!-- Invoice Date -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="invoice_date">
                                Invoice Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="invoice_date" name="invoice_date"
                                   value="<?= inv_old('invoice_date', $todayDate) ?>"
                                   class="w-full px-4 py-2.5 text-sm border <?= isset($errors['invoice_date']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?> rounded-xl focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all"
                                   required>
                            <?= inv_err('invoice_date') ?>
                        </div>

                        <!-- Due Date -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="due_date">Due Date</label>
                            <input type="date" id="due_date" name="due_date"
                                   value="<?= inv_old('due_date', $dueDateDefault) ?>"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all">
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="status">Status</label>
                            <select id="status" name="status"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all bg-white appearance-none cursor-pointer">
                                <?php $oldStatus = inv_old('status', 'unpaid'); ?>
                                <option value="unpaid" <?= $oldStatus === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                <option value="paid"   <?= $oldStatus === 'paid'   ? 'selected' : '' ?>>Paid</option>
                                <option value="draft"  <?= $oldStatus === 'draft'  ? 'selected' : '' ?>>Draft</option>
                            </select>
                        </div>

                        <!-- Notes -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="notes">Notes (Optional)</label>
                            <textarea id="notes" name="notes" rows="2"
                                      placeholder="Any additional notes for the student..."
                                      class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all resize-none"><?= inv_old('notes') ?></textarea>
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
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-[#7c3aed] rounded-xl hover:bg-[#6d28d9] transition-colors shadow-sm">
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
                                <th class="text-left pb-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider w-8">#</th>
                                <th class="text-left pb-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider">Course / Service</th>
                                <th class="text-left pb-3 text-xs font-semibold text-[#94a3b8] uppercase tracking-wider hidden md:table-cell">Description</th>
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
                <div id="items-empty" class="py-10 text-center text-[#94a3b8]">
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
                        <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h2 class="text-base font-semibold text-[#0f172a]">Currency</h2>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[#374151] mb-1.5" for="currency">
                            Select Currency <span class="text-red-500">*</span>
                        </label>
                        <select id="currency" name="currency"
                                class="w-full px-4 py-2.5 text-sm border <?= isset($errors['currency']) ? 'border-red-400' : 'border-gray-200' ?> rounded-xl focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all bg-white appearance-none cursor-pointer"
                                required>
                            <?php
                            $oldCurrency = strtoupper(inv_old('currency', 'BDT'));
                            foreach ($currencies as $code => $info):
                            ?>
                                <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $oldCurrency === $code ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($info['flag'] . ' ' . $code . ' — ' . $info['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= inv_err('currency') ?>
                    </div>
                </div>

                <!-- Summary -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                    <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <h2 class="text-base font-semibold text-[#0f172a]">Summary</h2>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between text-sm text-[#475569]">
                            <span>Subtotal</span>
                            <span id="summary-subtotal" class="font-medium text-[#0f172a]">0.00</span>
                        </div>

                        <div class="flex items-center justify-between text-sm text-[#475569]">
                            <label for="discount" class="shrink-0">Discount</label>
                            <div class="flex items-center gap-2">
                                <input type="number" id="discount" name="discount"
                                       value="<?= inv_old('discount', '0') ?>"
                                       min="0" step="0.01" placeholder="0.00"
                                       class="w-28 px-3 py-1.5 text-sm text-right border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all">
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-sm text-[#475569]">
                            <label for="vat_percent" class="shrink-0">VAT (%)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" id="vat_percent" name="vat_percent"
                                       value="<?= inv_old('vat_percent', '0') ?>"
                                       min="0" max="100" step="0.01" placeholder="0"
                                       class="w-20 px-3 py-1.5 text-sm text-right border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all">
                                <span id="summary-vat-amount" class="w-24 text-right font-medium text-[#0f172a]">0.00</span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-gray-100">
                            <div class="flex justify-between items-center">
                                <span class="text-base font-bold text-[#0f172a]">Grand Total</span>
                                <span id="summary-grand-total" class="text-2xl font-bold text-[#7c3aed]">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 ACTION BAR
            ============================================== -->
            <div class="mt-6 flex items-center justify-between bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-4">
                <a href="/admin/invoices/index.php"
                   class="px-5 py-2.5 text-sm font-medium text-[#64748b] border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <div class="flex gap-3">
                    <button type="submit" name="action" value="draft"
                            class="px-5 py-2.5 text-sm font-medium text-[#374151] bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-2 shadow-sm">
                        <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Save as Draft
                    </button>
                    <button type="submit" name="action" value="generate"
                            class="px-6 py-2.5 text-sm font-semibold text-white bg-[#7c3aed] rounded-xl hover:bg-[#6d28d9] transition-colors flex items-center gap-2 shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Generate Invoice
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
                   class="item-name w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all" required>
        </td>
        <td class="py-3 pr-2 align-top hidden md:table-cell">
            <input type="text" name="items[__IDX__][description]" placeholder="Optional description"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all">
        </td>
        <td class="py-3 pr-2 align-top">
            <input type="number" name="items[__IDX__][quantity]" value="1" min="0.01" step="0.01"
                   class="item-qty w-full px-3 py-2 text-sm text-right border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all">
        </td>
        <td class="py-3 pr-2 align-top">
            <input type="number" name="items[__IDX__][unit_price]" value="0.00" min="0" step="0.01"
                   class="item-price w-full px-3 py-2 text-sm text-right border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-transparent transition-all">
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
<script>
(function () {
    'use strict';

    let itemIndex = 0;

    const addBtn      = document.getElementById('add-item-btn');
    const tbody       = document.getElementById('items-body');
    const emptyState  = document.getElementById('items-empty');
    const tpl         = document.getElementById('item-row-tpl');
    const discountEl  = document.getElementById('discount');
    const vatEl       = document.getElementById('vat_percent');
    const subtotalEl  = document.getElementById('summary-subtotal');
    const vatAmtEl    = document.getElementById('summary-vat-amount');
    const grandTotalEl = document.getElementById('summary-grand-total');
    const form        = document.getElementById('invoice-form');

    // ── Add row ──────────────────────────────────────────────
    function addRow(name = '', desc = '', qty = 1, price = 0) {
        const html = tpl.innerHTML
            .replaceAll('__IDX__', itemIndex)
            .replaceAll('__NUM__', itemIndex + 1);
        tbody.insertAdjacentHTML('beforeend', html);
        const row = tbody.lastElementChild;

        // Populate values if provided (re-fill on error)
        if (name)  row.querySelector('.item-name').value  = name;
        if (price) row.querySelector('.item-price').value = parseFloat(price).toFixed(2);
        if (qty)   row.querySelector('.item-qty').value   = parseFloat(qty).toFixed(2);

        recalcRow(row);
        itemIndex++;
        toggleEmpty();
        recalcTotals();
    }

    // ── Remove row ───────────────────────────────────────────
    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-item-btn');
        if (!btn) return;
        btn.closest('tr').remove();
        renumberRows();
        toggleEmpty();
        recalcTotals();
    });

    // ── Input change ─────────────────────────────────────────
    tbody.addEventListener('input', function (e) {
        const row = e.target.closest('tr.item-row');
        if (row) recalcRow(row);
        recalcTotals();
    });

    discountEl.addEventListener('input', recalcTotals);
    vatEl.addEventListener('input', recalcTotals);

    // ── Recalculate a single row amount ──────────────────────
    function recalcRow(row) {
        const qty   = parseFloat(row.querySelector('.item-qty').value)   || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const amt   = qty * price;
        row.querySelector('.item-amount').value = amt.toFixed(2);
    }

    // ── Recalculate totals ───────────────────────────────────
    function recalcTotals() {
        let subtotal = 0;
        tbody.querySelectorAll('.item-amount').forEach(function (el) {
            subtotal += parseFloat(el.value) || 0;
        });

        const discount    = Math.max(0, parseFloat(discountEl.value) || 0);
        const vatPercent  = Math.max(0, Math.min(100, parseFloat(vatEl.value) || 0));
        const afterDisc   = Math.max(0, subtotal - discount);
        const vatAmount   = afterDisc * (vatPercent / 100);
        const grandTotal  = afterDisc + vatAmount;

        subtotalEl.textContent  = subtotal.toFixed(2);
        vatAmtEl.textContent    = vatAmount.toFixed(2);
        grandTotalEl.textContent = grandTotal.toFixed(2);
    }

    // ── Renumber rows after removal ──────────────────────────
    function renumberRows() {
        tbody.querySelectorAll('tr.item-row').forEach(function (row, i) {
            row.querySelector('.row-num').textContent = i + 1;
            // Update input names to keep array contiguous
            row.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
            });
            row.dataset.index = i;
        });
    }

    // ── Toggle empty state ───────────────────────────────────
    function toggleEmpty() {
        const hasRows = tbody.querySelectorAll('tr.item-row').length > 0;
        emptyState.style.display = hasRows ? 'none' : 'block';
        document.querySelector('#items-table thead').style.display = hasRows ? '' : 'none';
    }

    // ── Handle Draft vs Generate button ─────────────────────
    form.addEventListener('submit', function (e) {
        const action = document.activeElement?.value;
        if (action === 'draft') {
            document.getElementById('status').value = 'draft';
        }
    });

    // ── Init ─────────────────────────────────────────────────
    addBtn.addEventListener('click', function () { addRow(); });

    // Re-fill form if POST validation failed
    <?php if (!empty($old['items']) && is_array($old['items'])): ?>
    <?php foreach ($old['items'] as $i => $item): ?>
    addRow(
        <?= json_encode($item['item_name']   ?? '') ?>,
        <?= json_encode($item['description'] ?? '') ?>,
        <?= json_encode($item['quantity']    ?? 1) ?>,
        <?= json_encode($item['unit_price']  ?? 0) ?>
    );
    <?php endforeach; ?>
    <?php else: ?>
    // Start with one empty row
    addRow();
    <?php endif; ?>

    toggleEmpty();
    recalcTotals();

}());
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
