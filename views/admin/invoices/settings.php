<?php
/**
 * Invoice Settings View ” Configure institution details & invoice number format.
 * Accessible only by ROLE_ADMIN via public/admin/invoices/settings.php
 */
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$errors  = $errors  ?? [];
$success = $success ?? false;

function s(string $key): string {
    global $settings;
    return htmlspecialchars($settings[$key] ?? '', ENT_QUOTES, 'UTF-8');
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
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center gap-2 text-sm text-green-700">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Settings saved successfully!
        </div>
        <?php endif; ?>

        <form method="POST" action="/admin/invoices/settings.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

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
                </div>

                <!-- =============================================
                     SECTION 2: Invoice Number Format
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
                                <form method="POST" action="/admin/invoices/settings.php" onsubmit="return handleConfirm(event, 'Delete this currency?');" class="inline">
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

<script>
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


