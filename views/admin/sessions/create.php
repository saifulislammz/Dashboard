<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-3xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Schedule Sessions</h1>
            <a href="/admin/sessions/index.php?classroom_id=<?= $classroom['id'] ?>" class="text-sm font-medium text-primary hover:text-primary/80 transition-colors">
                &larr; Back to Sessions
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></h3>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="rounded-md bg-green-50 p-4 border border-green-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></h3>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($timeConflictError)): ?>
        <!-- Time-slot conflict data passed to JS (sanitised) -->
        <script>
            window._conflictData = {
                provider:  <?= json_encode(htmlspecialchars($timeConflictError['provider'],   ENT_QUOTES, 'UTF-8')) ?>,
                date:      <?= json_encode(htmlspecialchars($timeConflictError['date'],       ENT_QUOTES, 'UTF-8')) ?>,
                startTime: <?= json_encode(htmlspecialchars($timeConflictError['start_time'], ENT_QUOTES, 'UTF-8')) ?>,
                endTime:   <?= json_encode(htmlspecialchars($timeConflictError['end_time'],   ENT_QUOTES, 'UTF-8')) ?>
            };
        </script>
        <?php endif; ?>

        <!-- =========================================================
             TIME SLOT CONFLICT MODAL
             Hidden by default. Opened automatically by JS when the
             server signals a TimeSlotConflictException.
        ========================================================== -->
        <div
            id="conflictModal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="conflictModalTitle"
            class="fixed inset-0 z-50 hidden items-center justify-center p-4"
        >
            <!-- Backdrop -->
            <div
                id="conflictModalBackdrop"
                class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
                onclick="closeConflictModal()"
            ></div>

            <!-- Panel -->
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-auto overflow-hidden">

                <!-- Coloured header bar -->
                <div class="bg-amber-500 px-6 py-5 flex items-start gap-4">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h2 id="conflictModalTitle" class="text-lg font-bold text-white leading-tight">
                            Time Slot Already Booked
                        </h2>
                        <p class="text-amber-100 text-sm mt-0.5">
                            No session was created.
                        </p>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-6 py-5 space-y-4">
                    <p class="text-gray-700 text-sm leading-relaxed">
                        All <strong id="conflictProvider" class="text-gray-900"></strong> accounts are already
                        assigned to another class on
                        <strong id="conflictDate" class="text-gray-900"></strong>
                        between
                        <strong id="conflictStart" class="text-gray-900"></strong> and
                        <strong id="conflictEnd" class="text-gray-900"></strong>.
                    </p>

                    <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 flex gap-2">
                        <svg class="h-5 w-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        <p class="text-amber-800 text-sm">
                            Click <strong>Choose Another Time</strong> to clear only the time fields so you can pick a different slot. All other details will be kept.
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 pb-6 flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
                    <button
                        type="button"
                        id="conflictModalClose"
                        onclick="closeConflictModal()"
                        class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-5 py-2.5 border-2 border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors"
                    >
                        Dismiss
                    </button>
                    <button
                        type="button"
                        id="conflictModalReset"
                        onclick="resetTimeAndCloseModal()"
                        class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-5 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-amber-500 hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Choose Another Time
                    </button>
                </div>
            </div>
        </div>


        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex gap-4">
                <button type="button" onclick="setMode('single')" id="btnSingle" class="px-4 py-2 text-sm font-medium rounded-md bg-primary text-white transition-colors">Single Session</button>
                <button type="button" onclick="setMode('bulk')" id="btnBulk" class="px-4 py-2 text-sm font-medium rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">Multiple Sessions (Bulk)</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                <input type="hidden" name="mode" id="formMode" value="single">
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Classroom</label>
                        <div class="mt-1">
                            <input type="text" value="<?= htmlspecialchars($classroom['class_name']) ?>" disabled class="shadow-sm block w-full px-4 py-2.5 sm:text-sm border-2 border-gray-300 rounded-md bg-gray-100 text-gray-500 cursor-not-allowed">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="provider" class="block text-sm font-medium text-gray-700">Meeting Provider <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <select id="provider" name="provider" required onchange="updateAccountDropdown()" class="shadow-sm focus:ring-primary focus:border-primary block w-full px-4 py-2.5 sm:text-sm border-2 border-primary rounded-md text-gray-900">
                                <option value="zoom" <?= (isset($defaultProvider) && $defaultProvider === 'zoom') ? 'selected' : '' ?>>Zoom</option>
                                <option value="google_meet" <?= (isset($defaultProvider) && $defaultProvider === 'google_meet') ? 'selected' : '' ?>>Google Meet</option>
                            </select>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="provider_account_id" class="block text-sm font-medium text-gray-700">Provider Account (Optional)</label>
                        <div class="mt-1">
                            <select id="provider_account_id" name="provider_account_id" class="shadow-sm focus:ring-primary focus:border-primary block w-full px-4 py-2.5 sm:text-sm border-2 border-gray-300 rounded-md text-gray-900">
                                <option value="">-- Auto (Smart Account Selection) --</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Leave as Auto to let the system pick an available account, or force a specific account.</p>
                        </div>
                    </div>

                    <!-- Single Date Input -->
                    <div class="sm:col-span-2" id="singleDateWrapper">
                        <label for="session_date" class="block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input type="date" name="session_date" id="session_date" class="shadow-sm focus:ring-primary focus:border-primary block w-full px-4 py-2.5 sm:text-sm border-2 border-primary rounded-md text-gray-900">
                        </div>
                    </div>

                    <!-- Bulk Dates Input -->
                    <div class="sm:col-span-2 hidden" id="bulkDateWrapper">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Selected Dates <span class="text-red-500">*</span></label>
                        <div id="dateList" class="space-y-3">
                            <div class="flex gap-3 date-row">
                                <input type="date" name="dates[]" class="shadow-sm focus:ring-primary focus:border-primary block w-full px-4 py-2.5 sm:text-sm border-2 border-primary rounded-md text-gray-900">
                                <button type="button" onclick="removeDate(this)" class="px-4 py-2.5 border-2 border-gray-300 text-red-500 rounded-md hover:bg-red-50 hidden remove-btn transition-colors">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                        <button type="button" onclick="addDate()" class="mt-4 text-sm text-primary font-medium hover:underline flex items-center">
                            + Add another date
                        </button>
                    </div>

                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700">Start Time <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input type="time" name="start_time" id="start_time" required class="shadow-sm focus:ring-primary focus:border-primary block w-full px-4 py-2.5 sm:text-sm border-2 border-primary rounded-md text-gray-900">
                        </div>
                    </div>

                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700">End Time <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input type="time" name="end_time" id="end_time" required class="shadow-sm focus:ring-primary focus:border-primary block w-full px-4 py-2.5 sm:text-sm border-2 border-primary rounded-md text-gray-900">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                        <div class="mt-1">
                            <select id="timezone" name="timezone" class="shadow-sm focus:ring-primary focus:border-primary block w-full px-4 py-2.5 sm:text-sm border-2 border-primary rounded-md text-gray-900">
                                <option value="Asia/Dhaka" <?= (isset($defaultTimezone) && $defaultTimezone === 'Asia/Dhaka') ? 'selected' : '' ?>>Asia/Dhaka</option>
                                <?php foreach ($timezones as $tz): if ($tz === 'Asia/Dhaka') continue; ?>
                                    <option value="<?= $tz ?>" <?= (isset($defaultTimezone) && $defaultTimezone === $tz) ? 'selected' : '' ?>><?= $tz ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="topic" class="block text-sm font-medium text-gray-700">Topic / Title (Optional)</label>
                        <div class="mt-1">
                            <input type="text" name="topic" id="topic" placeholder="e.g. Grammar Lesson 1" class="shadow-sm focus:ring-primary focus:border-primary block w-full px-4 py-2.5 sm:text-sm border-2 border-primary rounded-md text-gray-900">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="agenda" class="block text-sm font-medium text-gray-700">Agenda / Notes (Optional)</label>
                        <div class="mt-1">
                            <textarea name="agenda" id="agenda" rows="4" class="shadow-sm focus:ring-primary focus:border-primary block w-full px-4 py-2.5 sm:text-sm border-2 border-primary rounded-md text-gray-900"></textarea>
                        </div>
                    </div>
                </div>

                <div class="pt-5 border-t border-gray-200 flex justify-end gap-3">
                    <a href="/admin/sessions/index.php?classroom_id=<?= $classroom['id'] ?>" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        Generate Meeting(s)
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    function setMode(mode) {
        document.getElementById('formMode').value = mode;
        const btnSingle = document.getElementById('btnSingle');
        const btnBulk = document.getElementById('btnBulk');
        const singleDate = document.getElementById('singleDateWrapper');
        const bulkDate = document.getElementById('bulkDateWrapper');

        if (mode === 'single') {
            btnSingle.className = "px-4 py-2 text-sm font-medium rounded-md bg-primary text-white transition-colors";
            btnBulk.className = "px-4 py-2 text-sm font-medium rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors";
            singleDate.classList.remove('hidden');
            document.getElementById('session_date').setAttribute('required', 'required');
            bulkDate.classList.add('hidden');
            document.querySelectorAll('input[name="dates[]"]').forEach(el => el.removeAttribute('required'));
        } else {
            btnBulk.className = "px-4 py-2 text-sm font-medium rounded-md bg-primary text-white transition-colors";
            btnSingle.className = "px-4 py-2 text-sm font-medium rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors";
            singleDate.classList.add('hidden');
            document.getElementById('session_date').removeAttribute('required');
            bulkDate.classList.remove('hidden');
            document.querySelectorAll('input[name="dates[]"]').forEach(el => el.setAttribute('required', 'required'));
            updateRemoveButtons();
        }
    }

    function addDate() {
        const list = document.getElementById('dateList');
        const firstRow = list.querySelector('.date-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelector('input').value = '';
        list.appendChild(newRow);
        updateRemoveButtons();
    }

    function removeDate(btn) {
        const list = document.getElementById('dateList');
        if (list.querySelectorAll('.date-row').length > 1) {
            btn.closest('.date-row').remove();
        }
        updateRemoveButtons();
    }

    function updateRemoveButtons() {
        const list = document.getElementById('dateList');
        const rows = list.querySelectorAll('.date-row');
        rows.forEach(row => {
            const btn = row.querySelector('.remove-btn');
            if (rows.length > 1) {
                btn.classList.remove('hidden');
            } else {
                btn.classList.add('hidden');
            }
        });
    }

    // Provider Accounts Data
    const accountsData = {
        'google_meet': [
            <?php foreach ($googleAccounts as $acc): ?>
                {id: <?= $acc['id'] ?>, text: '<?= htmlspecialchars(($acc['nickname'] ? $acc['nickname'] . ' - ' : '') . $acc['account_email']) ?>'},
            <?php endforeach; ?>
        ],
        'zoom': [
            <?php foreach ($zoomAccounts as $acc): ?>
                {id: <?= $acc['id'] ?>, text: '<?= htmlspecialchars(($acc['nickname'] ? $acc['nickname'] . ' - ' : '') . $acc['account_email']) ?>'},
            <?php endforeach; ?>
        ]
    };

    function updateAccountDropdown() {
        const provider = document.getElementById('provider').value;
        const dropdown = document.getElementById('provider_account_id');
        
        // Clear current options except Auto
        dropdown.innerHTML = '<option value="">-- Auto (Smart Account Selection) --</option>';
        
        const accounts = accountsData[provider] || [];
        accounts.forEach(acc => {
            const option = document.createElement('option');
            option.value = acc.id;
            option.textContent = acc.text;
            dropdown.appendChild(option);
        });
    }

    // Init
    setMode('single');
    updateAccountDropdown();

    // =========================================================
    // TIME SLOT CONFLICT MODAL — JS
    // =========================================================

    /**
     * Open the conflict modal and populate it with server-side data.
     * @param {object} data  { provider, date, startTime, endTime }
     */
    function openConflictModal(data) {
        const providerLabel = data.provider === 'google_meet' ? 'Google Meet' : 'Zoom';

        // Format time for display (HH:MM)
        function fmtTime(t) {
            if (!t) return t;
            const parts = t.split(':');
            let h = parseInt(parts[0], 10);
            const m = parts[1] || '00';
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return h + ':' + m + ' ' + ampm;
        }

        // Format date for display
        function fmtDate(d) {
            if (!d) return d;
            const dt = new Date(d + 'T00:00:00');
            return dt.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }

        document.getElementById('conflictProvider').textContent = providerLabel;
        document.getElementById('conflictDate').textContent    = fmtDate(data.date);
        document.getElementById('conflictStart').textContent   = fmtTime(data.startTime);
        document.getElementById('conflictEnd').textContent     = fmtTime(data.endTime);

        const modal = document.getElementById('conflictModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';

        // Focus the primary action button for accessibility
        document.getElementById('conflictModalReset').focus();
    }

    /**
     * Close the modal without resetting anything.
     */
    function closeConflictModal() {
        const modal = document.getElementById('conflictModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    /**
     * Close the modal AND clear only the time fields so the user
     * can pick a different slot. All other form data is preserved.
     */
    function resetTimeAndCloseModal() {
        document.getElementById('start_time').value = '';
        document.getElementById('end_time').value   = '';

        // Scroll the time fields into view so the user notices them
        document.getElementById('start_time').scrollIntoView({ behavior: 'smooth', block: 'center' });
        document.getElementById('start_time').focus();

        closeConflictModal();
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeConflictModal();
        }
    });

    // Auto-open if the server detected a time conflict
    if (typeof window._conflictData !== 'undefined' && window._conflictData) {
        openConflictModal(window._conflictData);
    }

</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
