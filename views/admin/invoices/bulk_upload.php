<?php
/**
 * Bulk Invoice Upload View
 * Template download, instructions and CSV file upload dropzone.
 */
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
    <div class="max-w-[1200px] mx-auto px-6 py-8 space-y-6">

        <!-- Header & Breadcrumbs -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-[#64748b] mb-1">
                    <a href="/admin/invoices/index.php" class="hover:text-primary transition-colors">Invoices</a>
                    <span>/</span>
                    <span class="text-[#0f172a]">Bulk Upload & Generate</span>
                </div>
                <h1 class="text-2xl font-bold text-[#1e293b] tracking-tight">Bulk Invoice Upload & Generate</h1>
                <p class="text-sm text-[#64748b] mt-1">Upload multiple student invoices at once using our standard Excel/CSV template.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="/admin/invoices/index.php"
                   class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-[#475569] bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Invoices
                </a>
                <a href="/admin/invoices/bulk_template.php"
                   class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download Template (.CSV)
                </a>
            </div>
        </div>

        <?php if (!empty($errorMsg)): ?>
            <div class="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-start gap-3 text-sm text-red-700 shadow-sm animate-fade-in">
                <svg class="w-5 h-5 shrink-0 mt-0.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="flex-1">
                    <p class="font-bold">Upload Error</p>
                    <p class="mt-0.5"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Two Column Layout: Instructions & Upload Box -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Left: Step-by-Step Instructions -->
            <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-6">
                <div class="flex items-center gap-3 pb-4 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-[#0f172a]">How It Works</h2>
                        <p class="text-xs text-[#64748b]">Follow these simple steps for bulk invoicing</p>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="flex items-start gap-4">
                        <div class="w-7 h-7 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">1</div>
                        <div>
                            <h3 class="text-sm font-semibold text-[#1e293b]">Download Standard Template</h3>
                            <p class="text-xs text-[#64748b] mt-1 leading-relaxed">
                                Click <strong class="text-emerald-700">Download Template</strong> to get the standard CSV template. It opens seamlessly in Microsoft Excel or Google Sheets.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-7 h-7 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">2</div>
                        <div>
                            <h3 class="text-sm font-semibold text-[#1e293b]">Fill in Student & Billing Data</h3>
                            <p class="text-xs text-[#64748b] mt-1 leading-relaxed">
                                Required fields: <code>student_name</code>, <code>item_name</code>, and <code>unit_price</code>. Dates must follow <code>YYYY-MM-DD</code> format.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-7 h-7 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">3</div>
                        <div>
                            <h3 class="text-sm font-semibold text-[#1e293b]">Multi-Item Invoices (Optional)</h3>
                            <p class="text-xs text-[#64748b] mt-1 leading-relaxed">
                                To combine multiple items into a single invoice for a student, give them the exact same <code>invoice_group_key</code> (e.g. <code>GRP-001</code>).
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-7 h-7 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">4</div>
                        <div>
                            <h3 class="text-sm font-semibold text-[#1e293b]">Upload & Review Live Validation</h3>
                            <p class="text-xs text-[#64748b] mt-1 leading-relaxed">
                                System will validate each row and show an interactive preview. You can review valid and invalid rows before generating invoices.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-amber-50/70 border border-amber-200/60 rounded-xl p-4 text-xs text-amber-800 leading-relaxed">
                    <p class="font-bold flex items-center gap-1.5 mb-1">
                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Important Notice
                    </p>
                    Do not modify or rename column header names in the template. The file must be saved in CSV format and cannot exceed 5MB (max 1000 rows).
                </div>
            </div>

            <!-- Right: Upload Form Dropzone -->
            <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
                <form id="bulkUploadForm" action="/admin/invoices/bulk_upload.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

                    <div>
                        <label class="block text-sm font-bold text-[#1e293b] mb-2">Upload Completed CSV File</label>
                        <p class="text-xs text-[#64748b] mb-4">Select or drag & drop your prepared invoice template spreadsheet file here.</p>

                        <!-- Dropzone Area -->
                        <div id="dropzoneContainer"
                             class="relative border-2 border-dashed border-gray-200 hover:border-emerald-500 rounded-2xl p-8 text-center bg-gray-50/50 hover:bg-emerald-50/20 transition-all cursor-pointer group">
                            <input type="file"
                                   id="csv_file"
                                   name="csv_file"
                                   accept=".csv,text/csv,application/vnd.ms-excel"
                                   required
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                            <div class="flex flex-col items-center justify-center space-y-3 pointer-events-none">
                                <div class="w-16 h-16 rounded-2xl bg-white border border-gray-200/80 shadow-sm flex items-center justify-center text-emerald-600 group-hover:scale-105 transition-transform">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-[#0f172a]">
                                        <span class="text-emerald-600 hover:underline">Click to browse</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-[#94a3b8] mt-1">Standard CSV format (.csv) up to 5MB</p>
                                </div>
                            </div>

                            <!-- Selected File Badge (hidden by default) -->
                            <div id="fileSelectedBadge" class="hidden mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-emerald-200 text-emerald-800 text-xs font-semibold shadow-sm">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span id="selectedFileName">filename.csv</span>
                                <span id="selectedFileSize" class="text-gray-400 font-normal">(0 KB)</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <div class="text-xs text-[#94a3b8]">
                            Safe & isolated staging process
                        </div>
                        <button type="submit"
                                id="submitUploadBtn"
                                class="inline-flex items-center gap-2 px-6 py-3 text-sm font-bold text-white bg-primary rounded-xl hover:bg-green-700 active:scale-95 transition-all shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span>Upload & Validate Rows</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</main>

<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('csv_file');
    const badge = document.getElementById('fileSelectedBadge');
    const nameEl = document.getElementById('selectedFileName');
    const sizeEl = document.getElementById('selectedFileSize');
    const submitBtn = document.getElementById('submitUploadBtn');

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                const file = this.files[0];
                nameEl.textContent = file.name;
                const sizeKb = (file.size / 1024).toFixed(1);
                sizeEl.textContent = `(${sizeKb} KB)`;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        });
    }

    const form = document.getElementById('bulkUploadForm');
    if (form) {
        form.addEventListener('submit', function () {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Uploading & Validating...
                `;
            }
        });
    }
});
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

