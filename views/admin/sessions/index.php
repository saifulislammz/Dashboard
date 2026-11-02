<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$canSchedule = (function_exists('isSuperAdmin') && isSuperAdmin()) || (function_exists('hasPermission') && hasPermission('sessions.create'));
$canEditSession = (function_exists('isSuperAdmin') && isSuperAdmin()) || (function_exists('hasPermission') && hasPermission('sessions.edit'));
$canCancelSession = (function_exists('isSuperAdmin') && isSuperAdmin()) || (function_exists('hasPermission') && hasPermission('sessions.cancel'));
$canRetrySession = (function_exists('isSuperAdmin') && isSuperAdmin()) || (function_exists('hasPermission') && hasPermission('sessions.retry'));
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Live Sessions</h1>
                <p class="text-sm text-gray-500 mt-1">Classroom: <span class="font-semibold"><?= htmlspecialchars($classroom['class_name']) ?></span> (<?= htmlspecialchars($classroom['class_code']) ?>)</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="/admin/attendance/detail.php?classroom_id=<?= (int) $classroom['id'] ?>" class="inline-flex items-center gap-1.5 bg-white py-2 px-3.5 border border-gray-200 rounded-md shadow-2xs text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Attendance Report
                </a>
                <a href="/admin/classrooms/edit.php?id=<?= $classroom['id'] ?>" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-2xs text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    Class Details
                </a>
                <?php if ($canSchedule): ?>
                <a href="/admin/sessions/create.php?classroom_id=<?= $classroom['id'] ?>" class="inline-flex items-center justify-center py-2 px-4 border border-transparent shadow-xs text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 transition-colors">
                    <svg class="mr-2 -ml-1 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Schedule Session
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_GET['cancelled'])): ?>
            <div class="rounded-md bg-green-50 p-4 border border-green-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Session cancelled successfully.</h3>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['retry_ok'])): ?>
            <div class="rounded-md bg-green-50 p-4 border border-green-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Meeting generation retried successfully.</h3>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['retry_failed'])): ?>
            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Meeting generation retry failed. Check credentials.</h3>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filter Form -->
        <div class="bg-white rounded-xl shadow-2xs border border-gray-200 p-4">
            <form action="" method="GET" class="flex flex-col sm:flex-row gap-4">
                <input type="hidden" name="classroom_id" value="<?= $classroom['id'] ?>">
                <div class="w-full sm:w-64">
                    <select name="status" class="shadow-2xs focus:ring-primary focus:border-primary block w-full py-2.5 pl-3 pr-10 sm:text-sm border-gray-300 rounded-md text-gray-900">
                        <option value="">All Statuses</option>
                        <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>
                <button type="submit" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-2xs text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    Filter
                </button>
                <?php if (!empty($status)): ?>
                    <a href="/admin/sessions/index.php?classroom_id=<?= $classroom['id'] ?>" class="text-sm text-gray-500 hover:text-gray-700 flex items-center">
                        Clear Filters
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Sessions List -->
        <div class="bg-white shadow-2xs border border-gray-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Topic</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Provider</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Meeting Link</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($sessions)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-sm">No sessions found.</p>
                                    <?php if ($canSchedule): ?>
                                    <a href="/admin/sessions/create.php?classroom_id=<?= $classroom['id'] ?>" class="text-primary hover:text-primary/80 text-sm font-medium mt-2">Schedule one now</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($sessions as $session): ?>
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars(date('M j, Y', strtotime($session['session_date']))) ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?= htmlspecialchars(date('g:i A', strtotime($session['start_time']))) ?> - <?= htmlspecialchars(date('g:i A', strtotime($session['end_time']))) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 truncate max-w-[200px]" title="<?= htmlspecialchars($session['topic'] ?? '') ?>">
                                        <?= htmlspecialchars($session['topic'] ?: 'Session #' . ($session['session_number'] ?? '')) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if ($session['provider'] === 'google_meet'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                Google Meet
                                            </span>
                                        <?php elseif ($session['provider'] === 'zoom'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                Zoom
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $statusClass = [
                                            'scheduled'       => 'bg-gray-100 text-gray-800',
                                            'meeting_pending' => 'bg-yellow-100 text-yellow-800',
                                            'active'          => 'bg-green-100 text-green-800',
                                            'completed'       => 'bg-green-100 text-green-800',
                                            'cancelled'       => 'bg-red-100 text-red-800',
                                            'failed'          => 'bg-red-100 text-red-800',
                                        ][$session['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $session['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($session['generation_status'] === 'success'): ?>
                                        <div class="flex items-center gap-2">
                                            <a href="/session/join.php?id=<?= $session['id'] ?>" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-medium transition-colors" title="One-Click Observer Join">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                                One-Click Join
                                            </a>
                                        </div>
                                    <?php elseif ($session['generation_status'] === 'failed'): ?>
                                        <div class="text-xs text-red-600 truncate max-w-[150px]" title="<?= htmlspecialchars($session['error_message'] ?? '') ?>">
                                            Failed: <?= htmlspecialchars($session['error_message'] ?? 'Unknown error') ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-500">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2.5">
                                        <?php if ($session['generation_status'] === 'failed' && $session['status'] !== 'cancelled' && $canRetrySession): ?>
                                            <form action="/admin/sessions/retry.php" method="POST" class="inline-flex m-0">
                                                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                                <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                                                <input type="hidden" name="classroom_id" value="<?= $classroom['id'] ?>">
                                                <button type="submit" class="inline-flex items-center justify-center p-2 text-yellow-600 bg-yellow-50 hover:bg-yellow-100 hover:text-yellow-700 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-yellow-500 shadow-2xs hover:shadow-xs" title="Retry meeting generation">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($session['status'] !== 'cancelled' && $session['status'] !== 'completed'): ?>
                                            <?php if ($canEditSession): ?>
                                            <a href="/admin/sessions/edit.php?id=<?= $session['id'] ?>" class="inline-flex items-center justify-center p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs hover:shadow-xs" title="Edit Session">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($canCancelSession): ?>
                                            <button type="button" onclick="confirmCancel(<?= $session['id'] ?>)" class="inline-flex items-center justify-center p-2 text-red-600 bg-red-50 hover:bg-red-100 hover:text-red-700 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 shadow-2xs hover:shadow-xs" title="Cancel Session">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?= ($page - 1) * $limit + 1 ?></span> to <span class="font-medium"><?= min($page * $limit, $total) ?></span> of <span class="font-medium"><?= $total ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-2xs -space-x-px" aria-label="Pagination">
                            <?php for ($i = 1; $i <= $pages; $i++): ?>
                                <a href="?classroom_id=<?= $classroom['id'] ?>&page=<?= $i ?>&status=<?= urlencode($status) ?>" class="<?= $i === $page ? 'z-10 bg-primary/10 border-primary text-primary' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Cancel Modal -->
<div id="cancelModal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end sm:items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeCancelModal()"></div>
        <div class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="/admin/sessions/cancel.php" method="POST" id="cancelForm">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                <input type="hidden" name="session_id" id="cancelSessionId" value="">
                <input type="hidden" name="classroom_id" value="<?= $classroom['id'] ?>">

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Cancel Session
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to cancel this session? The meeting will also be removed from the provider (Zoom / Google Meet).
                                </p>
                                <div class="mt-3">
                                    <label for="cancelReason" class="block text-xs font-medium text-gray-700">Reason for cancellation (optional):</label>
                                    <input type="text" name="reason" id="cancelReason" class="mt-1 block w-full shadow-2xs sm:text-sm border-gray-300 rounded-md focus:ring-red-500 focus:border-red-500" placeholder="e.g. Teacher unavailable">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-2xs px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Confirm Cancel
                    </button>
                    <button type="button" onclick="closeCancelModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-2xs px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Dismiss
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
function confirmCancel(sessionId) {
    document.getElementById('cancelSessionId').value = sessionId;
    document.getElementById('cancelModal').classList.remove('hidden');
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
    document.getElementById('cancelSessionId').value = '';
    document.getElementById('cancelReason').value = '';
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
