<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-3xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Session</h1>
            <a href="/admin/sessions/index.php?classroom_id=<?= $session['classroom_id'] ?>" class="text-sm font-medium text-primary hover:text-primary/80 transition-colors">
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

        <?php if ($session['status'] === 'cancelled'): ?>
            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">This session has been cancelled. Details cannot be edited.</h3>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <form action="" method="POST" class="p-6 space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Classroom</label>
                        <div class="mt-1">
                            <input type="text" value="<?= htmlspecialchars($session['class_name']) ?>" disabled class="shadow-sm block w-full sm:text-sm border-gray-300 rounded-md bg-gray-50 text-gray-500">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Provider</label>
                        <div class="mt-1">
                            <input type="text" value="<?= $session['provider'] === 'google_meet' ? 'Google Meet' : 'Zoom' ?>" disabled class="shadow-sm block w-full sm:text-sm border-gray-300 rounded-md bg-gray-50 text-gray-500">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Provider cannot be changed after creation.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="session_date" class="block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input type="date" name="session_date" id="session_date" value="<?= htmlspecialchars($session['session_date']) ?>" required class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700">Start Time <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input type="time" name="start_time" id="start_time" value="<?= htmlspecialchars($session['start_time']) ?>" required class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700">End Time <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input type="time" name="end_time" id="end_time" value="<?= htmlspecialchars($session['end_time']) ?>" required class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                        <div class="mt-1">
                            <select id="timezone" name="timezone" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                                <?php foreach ($timezones as $tz): ?>
                                    <option value="<?= $tz ?>" <?= $tz === $session['timezone'] ? 'selected' : '' ?>><?= $tz ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="topic" class="block text-sm font-medium text-gray-700">Topic / Title (Optional)</label>
                        <div class="mt-1">
                            <input type="text" name="topic" id="topic" value="<?= htmlspecialchars($session['topic'] ?? '') ?>" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="agenda" class="block text-sm font-medium text-gray-700">Agenda / Notes (Optional)</label>
                        <div class="mt-1">
                            <textarea name="agenda" id="agenda" rows="3" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md"><?= htmlspecialchars($session['agenda'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="pt-5 border-t border-gray-200 flex justify-end gap-3">
                    <a href="/admin/sessions/index.php?classroom_id=<?= $session['classroom_id'] ?>" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        Cancel
                    </a>
                    <?php if ($session['status'] !== 'cancelled'): ?>
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                            Update Session
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
