<?php
/**
 * Invoice Settings View ” Configure institution details & invoice number format.
 * Accessible only by ROLE_ADMIN via public/admin/invoices/settings.php
 */
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$errors  = $errors  ?? [];
$success = $success ?? false;

$GLOBALS['settings'] = $settings ?? [];
$GLOBALS['errors']   = $errors;

function s(string $key): string {
    global $settings;
    return htmlspecialchars((string)($settings[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}

function serr(string $key): string {
    global $errors;
    return isset($errors[$key])
        ? '<p class="mt-1 text-xs text-red-600">' . htmlspecialchars($errors[$key], ENT_QUOTES, 'UTF-8') . '</p>'
        : '';
}
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
    <div class="max-w-[860px] mx-auto px-6 py-8 space-y-6">

        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[#1e293b] tracking-tight">Invoice Settings</h1>
                <p class="text-sm text-[#64748b] mt-1">Configure institution details and invoice number format.</p>
            </div>
            <a href="/admin/invoices/index.php"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-[#64748b] bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
        </div>

        <?php if ($success || isset($_GET['saved'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Settings Saved!',
                    text: 'Your institution details and invoice settings have been saved successfully.',
                    icon: 'success',
                    confirmButtonColor: '#0F766E',
                    confirmButtonText: 'Great!'
                });
            });
        </script>
        <?php endif; ?>

        <form method="POST" action="/admin/invoices/settings.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="invoice_settings_submitted" value="1">

            <div class="space-y-6">

                <!-- =============================================
                     SECTION 1: Institution Details
                ============================================== -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
                    <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <h2 class="text-base font-semibold text-[#0f172a]">Institution Details</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Institution Name -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="institution_name">Institution Name</label>
                            <input type="text" id="institution_name" name="institution_name"
                                   value="<?= s('institution_name') ?>"
                                   placeholder="e.g. Rahe Nazat Institute"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            <?= serr('institution_name') ?>
                        </div>
                        <!-- Tagline -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="institution_tagline">Tagline / Subtitle</label>
                            <input type="text" id="institution_tagline" name="institution_tagline"
                                   value="<?= s('institution_tagline') ?>"
                                   placeholder="e.g. Excellence in Education"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                        </div>
                        <!-- Address -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="institution_address">Address</label>
                            <input type="text" id="institution_address" name="institution_address"
                                   value="<?= s('institution_address') ?>"
                                   placeholder="e.g. 123 Education Road, Dhaka, Bangladesh"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                        </div>
                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="institution_phone">Phone</label>
                            <input type="text" id="institution_phone" name="institution_phone"
                                   value="<?= s('institution_phone') ?>"
                                   placeholder="+880 1XXX-XXXXXX"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                        </div>
                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="institution_email">Email</label>
                            <input type="email" id="institution_email" name="institution_email"
                                   value="<?= s('institution_email') ?>"
                                   placeholder="info@institute.edu"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                        </div>
                        <!-- Footer Note -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="invoice_footer_note">Invoice Footer Note</label>
                            <input type="text" id="invoice_footer_note" name="invoice_footer_note"
                                   value="<?= s('invoice_footer_note') ?>"
                                   placeholder="e.g. Thank you for your payment. Please retain this invoice."
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                        </div>
                        
                        <!-- Institution Logo -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="institution_logo">Institution Logo (Optional)</label>
                            <?php if (s('institution_logo')): ?>
                                <div class="mb-3">
                                    <img src="<?= s('institution_logo') ?>" alt="Logo" class="h-16 object-contain">
                                </div>
                            <?php endif; ?>
                            <input type="file" id="institution_logo" name="institution_logo" accept="image/*"
                                   class="w-full px-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            <p class="text-xs text-gray-500 mt-1">Upload a logo to appear on the invoice. Recommended size: 200x200px max.</p>
                        </div>
                    </div>

                    <!-- Save Button for Section 1 -->
                    <div class="flex justify-end pt-4 border-t border-gray-50 mt-6">
                        <button type="submit"
                                class="px-6 py-2.5 text-sm font-semibold text-white bg-primary rounded-xl hover:bg-green-700 transition-colors shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Save Details
                        </button>
                    </div>
                </div>

                <!-- =============================================
                     SECTION 2: Authorized Signature Management
                ============================================== -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 flex-wrap gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-[#0f172a]">Authorized Signature Management</h2>
                                <p class="text-xs text-[#64748b]">Upload a transparent PNG signature and configure global automatic signature inclusion.</p>
                            </div>
                        </div>

                        <!-- Global Signature On/Off Switch -->
                        <div class="flex items-center gap-3 bg-gray-50 px-3.5 py-1.5 rounded-xl border border-gray-200">
                            <span class="text-xs font-semibold text-[#475569]">Global Signature</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="invoice_signature_enabled" value="1"
                                       class="sr-only peer"
                                       <?= (s('invoice_signature_enabled') === '' || s('invoice_signature_enabled') === '1') ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-start">
                        <!-- File Upload -->
                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-[#374151]" for="institution_signature">
                                Upload Signature (Transparent PNG)
                            </label>
                            <input type="file" id="institution_signature" name="institution_signature" accept="image/png"
                                   class="w-full px-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                            <?= serr('institution_signature') ?>
                            <div class="text-xs text-gray-500 space-y-1 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <p>• <strong>Format:</strong> Only transparent background PNG (.png) is permitted.</p>
                                <p>• <strong>Max Size:</strong> 1 MB. Recommended resolution: ~300×100 px.</p>
                            </div>
                        </div>

                        <!-- Signature Preview Box -->
                        <div>
                            <span class="block text-sm font-medium text-[#374151] mb-2">Current Signature Preview</span>
                            <?php if (s('invoice_signature')): ?>
                                <div class="p-4 rounded-xl border border-gray-200 inline-block bg-white shadow-inner">
                                    <img src="<?= s('invoice_signature') ?>" alt="Signature Preview" class="h-14 max-w-[220px] object-contain">
                                </div>
                                <div class="mt-2.5">
                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-red-600 hover:text-red-700 cursor-pointer bg-red-50/50 px-2.5 py-1 rounded-lg border border-red-100">
                                        <input type="checkbox" name="remove_signature" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        <span>Remove current signature</span>
                                    </label>
                                </div>
                            <?php else: ?>
                                <div class="p-6 rounded-xl border-2 border-dashed border-gray-200 text-center bg-gray-50/50">
                                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                    <p class="text-xs text-gray-400 font-medium">No signature uploaded yet</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Upload a transparent PNG above</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bg-emerald-50/70 rounded-xl p-3.5 border border-emerald-100 flex items-start gap-3">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-emerald-800 leading-relaxed">
                            <strong>Note:</strong> When <strong>Global Signature</strong> is turned ON, newly created invoices will automatically have the signature inclusion option checked by default. You can still manually include or exclude the signature for individual invoices during creation or editing.
                        </p>
                    </div>

                    <!-- Save Button for Section 2 -->
                    <div class="flex justify-end pt-4 border-t border-gray-50 mt-4">
                        <button type="submit"
                                class="px-6 py-2.5 text-sm font-semibold text-white bg-primary rounded-xl hover:bg-green-700 transition-colors shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Save Signature Settings
                        </button>
                    </div>
                </div>

                <!-- =============================================
                     SECTION 3: Invoice Number Format
                ============================================== -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
                    <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                        </div>
                        <h2 class="text-base font-semibold text-[#0f172a]">Invoice Number Format</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Prefix -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="invoice_prefix">Prefix</label>
                            <input type="text" id="invoice_prefix" name="invoice_prefix"
                                   value="<?= s('invoice_prefix') ?>"
                                   maxlength="10"
                                   placeholder="e.g. INV"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all font-mono"
                                   oninput="updatePreview()">
                            <?= serr('invoice_prefix') ?>
                        </div>
                        <!-- Format -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="invoice_number_format">Format Template</label>
                            <select id="invoice_number_format" name="invoice_number_format"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-white appearance-none cursor-pointer"
                                    onchange="updatePreview()">
                                <?php
                                $fmts = [
                                    '{PREFIX}{YEAR}{MONTH}{SEQ4}'     => 'PREFIX + Year + Month + Seq(4)',
                                    '{PREFIX}{YEAR}{MONTH}{DAY}{SEQ4}'=> 'PREFIX + Year + Month + Day + Seq(4)',
                                    '{PREFIX}{YEAR}{SEQ6}'            => 'PREFIX + Year + Seq(6)',
                                    '{PREFIX}{SEQ6}'                  => 'PREFIX + Seq(6)',
                                ];
                                $curFmt = $settings['invoice_number_format'] ?? '{PREFIX}{YEAR}{MONTH}{SEQ4}';
                                foreach ($fmts as $val => $label):
                                ?>
                                <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $curFmt === $val ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Live Preview -->
                    <div class="bg-green-50 rounded-xl p-4 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-lg bg-white flex items-center justify-center shadow-sm shrink-0">
                            <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-primary uppercase tracking-wider mb-0.5">Live Preview</p>
                            <p id="format-preview" class="text-lg font-bold font-mono text-[#0f172a] tracking-widest">
                                <?= htmlspecialchars($numberPreview ?? 'INV2506040001', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                    </div>

                    <!-- Format Tokens Help -->
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs font-bold text-[#94a3b8] uppercase tracking-wider mb-3">Available Tokens</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <?php
                            $tokens = [
                                '{PREFIX}' => 'Your custom prefix (e.g. INV)',
                                '{YEAR}'   => 'Current year (e.g. 2506)',
                                '{MONTH}'  => 'Current month (e.g. 06)',
                                '{DAY}'    => 'Current day (e.g. 29)',
                                '{SEQ4}'   => '4-digit sequence (e.g. 0001)',
                                '{SEQ6}'   => '6-digit sequence (e.g. 000001)',
                            ];
                            foreach ($tokens as $tok => $desc):
                            ?>
                            <div class="flex items-start gap-1.5">
                                <code class="text-xs bg-white border border-gray-200 px-2 py-0.5 rounded-md font-mono text-primary shrink-0"><?= htmlspecialchars($tok, ENT_QUOTES, 'UTF-8') ?></code>
                                <span class="text-xs text-[#64748b]"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- =============================================
                     SECTION 4: Invoice Email Settings
                ============================================== -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
                    <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-[#0f172a]">Invoice Email Settings</h2>
                            <p class="text-xs text-[#64748b]">Configure automatic delivery and custom invoice email parameters.</p>
                        </div>
                    </div>

                    <!-- Automatic Email Dispatch Toggle -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100">
                        <div>
                            <span class="text-sm font-semibold text-[#1e293b] block">Automatic Email Dispatch on Invoice Generation</span>
                            <span class="text-xs text-[#64748b] block mt-0.5">When enabled, newly created invoices will be automatically compiled into vector PDFs and emailed to the student/customer.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                            <input type="checkbox" name="invoice_auto_email_enabled" value="1"
                                   <?= (!empty($settings['invoice_auto_email_enabled']) && $settings['invoice_auto_email_enabled'] === '1') ? 'checked' : '' ?>
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Email Subject Template -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="invoice_email_subject">
                                Email Subject Line
                            </label>
                            <input type="text" id="invoice_email_subject" name="invoice_email_subject"
                                   value="<?= s('invoice_email_subject') ?: 'Invoice #{invoice_number} from {institution_name}' ?>"
                                   placeholder="e.g. Invoice #{invoice_number} from {institution_name}"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            <p class="text-xs text-[#64748b] mt-1.5">
                                Available variables: <code class="bg-gray-100 px-1 py-0.5 rounded text-primary">{invoice_number}</code>, <code class="bg-gray-100 px-1 py-0.5 rounded text-primary">{institution_name}</code>, <code class="bg-gray-100 px-1 py-0.5 rounded text-primary">{student_name}</code>, <code class="bg-gray-100 px-1 py-0.5 rounded text-primary">{grand_total}</code>, <code class="bg-gray-100 px-1 py-0.5 rounded text-primary">{currency}</code>
                            </p>
                        </div>

                        <!-- BCC Email -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="invoice_email_bcc">
                                Accounts / Admin BCC Email (Optional)
                            </label>
                            <input type="email" id="invoice_email_bcc" name="invoice_email_bcc"
                                   value="<?= s('invoice_email_bcc') ?>"
                                   placeholder="e.g. accounts@institute.edu"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            <?= serr('invoice_email_bcc') ?>
                            <p class="text-xs text-[#64748b] mt-1">Receive a silent blind copy of every invoice sent.</p>
                        </div>

                        <!-- Notes in Email Body -->
                        <div>
                            <label class="block text-sm font-medium text-[#374151] mb-1.5" for="invoice_email_custom_notes">
                                Custom Note in Email Body (Optional)
                            </label>
                            <input type="text" id="invoice_email_custom_notes" name="invoice_email_custom_notes"
                                   value="<?= s('invoice_email_custom_notes') ?>"
                                   placeholder="e.g. Thank you for your payment."
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            <p class="text-xs text-[#64748b] mt-1">Appears above the invoice breakdown in the email body.</p>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <button type="submit"
                            class="px-8 py-3 text-sm font-semibold text-white bg-primary rounded-xl hover:bg-green-700 transition-colors shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Save Settings
                    </button>
                </div>

            </div>
        </form>

        <!-- =============================================
             SECTION 3: Currency Management
        ============================================== -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mt-8 space-y-5">
            <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h2 class="text-base font-semibold text-[#0f172a]">Currency Management</h2>
            </div>

            <?php if (isset($_GET['currency_added'])): ?>
                <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-green-700">Currency added successfully!</div>
            <?php endif; ?>
            <?php if (isset($_GET['currency_deleted'])): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-700">Currency deleted.</div>
            <?php endif; ?>

            <!-- Add Currency Form -->
            <form method="POST" action="/admin/invoices/settings.php" class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="add_currency">
                
                <h3 class="text-sm font-semibold text-[#1e293b] mb-3">Add New Currency</h3>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-medium text-[#64748b] mb-1">Code (e.g. USD)</label>
                        <input type="text" name="code" required maxlength="10" placeholder="USD" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-[#64748b] mb-1">Name (e.g. US Dollar)</label>
                        <input type="text" name="name" required placeholder="US Dollar" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-[#64748b] mb-1">Symbol (e.g. $)</label>
                        <input type="text" name="symbol" required placeholder="$" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-[#64748b] mb-1">Flag Emoji (e.g. 🇺🇸)</label>
                        <div class="flex gap-2">
                            <input type="text" name="flag" placeholder="🇺🇸" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-green-700 transition-colors shrink-0">Add</button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Currencies List -->
            <div class="mt-6 border border-gray-100 rounded-xl overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-[#64748b]">Code</th>
                            <th class="px-4 py-3 font-semibold text-[#64748b]">Name</th>
                            <th class="px-4 py-3 font-semibold text-[#64748b]">Symbol</th>
                            <th class="px-4 py-3 font-semibold text-[#64748b]">Flag</th>
                            <th class="px-4 py-3 font-semibold text-[#64748b] text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($currencies as $code => $info): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-4 py-3 font-mono font-medium text-[#0f172a]"><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-[#334155]"><?= htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-[#334155]"><?= htmlspecialchars($info['symbol'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-lg"><?= htmlspecialchars($info['flag'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="/admin/invoices/settings.php" data-confirm="Delete this currency?" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="delete_currency">
                                    <input type="hidden" name="code" value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700 transition-colors p-1" title="Delete">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /max-w -->
</main>

<script nonce="<?php echo csp_nonce(); ?>">
(function () {
    'use strict';

    const year  = '<?= date('Y') ?>';
    const month = '<?= date('m') ?>';
    const day   = '<?= date('d') ?>';

    function updatePreview() {
        const prefix = document.getElementById('invoice_prefix').value || 'INV';
        const fmt    = document.getElementById('invoice_number_format').value;

        let preview = fmt
            .replace('{PREFIX}', prefix)
            .replace('{YEAR}',   year.slice(-2))  // 2-digit year like existing pattern
            .replace('{MONTH}',  month)
            .replace('{DAY}',    day)
            .replace('{SEQ4}',   '0001')
            .replace('{SEQ6}',   '000001');

        document.getElementById('format-preview').textContent = preview;
    }

    document.getElementById('invoice_prefix').addEventListener('input', updatePreview);
    document.getElementById('invoice_number_format').addEventListener('change', updatePreview);
}());
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>


