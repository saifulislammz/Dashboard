<?php
/**
 * Admin Quiz & Exam List View
 * Shows all quizzes and exams with type badge, time duration, marks, attempt counts, and actions.
 */
$activeMenu = 'quiz_list';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$quizzes = $data['quizzes'] ?? [];
$total = $data['total'] ?? 0;
$currentPage = $data['current_page'] ?? 1;
$lastPage = $data['last_page'] ?? 1;

function quizPageUrl(int $p): string
{
    $params = $_GET;
    $params['page'] = $p;
    return '/admin/quiz/index.php?' . http_build_query($params);
}
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
    <div class="w-full max-w-[1750px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#1e293b] tracking-tight">Quiz & Exam Management</h1>
                <p class="text-sm text-[#64748b] mt-1">Manage quizzes and exams, duration limits, and evaluate results.</p>
            </div>
            <a href="/admin/quiz/create.php"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#059669] hover:bg-[#047857] text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Quiz / Exam
            </a>
        </div>

        <!-- Notifications -->
        <?php if (isset($_GET['deleted'])): ?>
            <div
                class="flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm font-medium">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Quiz/exam deleted successfully.
            </div>
        <?php endif; ?>

        <!-- Filter Bar -->
        <form method="GET" action="/admin/quiz/index.php"
            class="flex flex-wrap gap-3 p-4 bg-white border border-gray-100 rounded-2xl shadow-sm">
            <input type="text" name="search"
                value="<?php echo htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="Search title..." oninput="if(this.value.trim() === '') this.form.submit()"
                class="flex-1 min-w-[200px] px-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669]" />

            <select name="type"
                class="px-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#059669]/30 bg-white">
                <option value="">All Types</option>
                <option value="quiz" <?php echo ($_GET['type'] ?? '') === 'quiz' ? 'selected' : ''; ?>>Quiz Only</option>
                <option value="exam" <?php echo ($_GET['type'] ?? '') === 'exam' ? 'selected' : ''; ?>>Exam Only</option>
            </select>

            <select name="status"
                class="px-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#059669]/30 bg-white">
                <option value="">All Status</option>
                <option value="active" <?php echo ($_GET['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>

            <button type="submit"
                class="px-5 py-2 bg-[#059669] text-white text-sm font-semibold rounded-xl hover:bg-[#047857] transition-colors">
                Search
            </button>
            <a href="/admin/quiz/index.php"
                class="px-5 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-200 transition-colors">
                Reset
            </a>
        </form>

        <!-- Quiz Table -->
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <?php if (empty($quizzes)): ?>
                <div class="text-center py-16 text-[#64748b]">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="font-medium">No quizzes or exams found.</p>
                    <a href="/admin/quiz/create.php"
                        class="mt-3 inline-block text-[#059669] font-semibold hover:underline text-sm">Create new quiz or exam
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-[#f8fafc]">
                                <th class="px-5 py-3.5 text-left font-semibold text-[#64748b] whitespace-nowrap">#</th>
                                <th class="px-5 py-3.5 text-left font-semibold text-[#64748b] whitespace-nowrap min-w-[240px]">Title</th>
                                <th class="px-4 py-3.5 text-center font-semibold text-[#64748b] whitespace-nowrap">Type</th>
                                <th class="px-4 py-3.5 text-center font-semibold text-[#64748b] whitespace-nowrap">Duration</th>
                                <th class="px-4 py-3.5 text-center font-semibold text-[#64748b] whitespace-nowrap">Marks</th>
                                <th class="px-4 py-3.5 text-center font-semibold text-[#64748b] whitespace-nowrap">Status</th>
                                <th class="px-4 py-3.5 text-center font-semibold text-[#64748b] whitespace-nowrap">Participants</th>
                                <th class="px-4 py-3.5 text-center font-semibold text-[#64748b] whitespace-nowrap">Voice Answers</th>
                                <th class="px-4 py-3.5 text-center font-semibold text-[#64748b] whitespace-nowrap">Unreviewed</th>
                                <th class="px-4 py-3.5 text-center font-semibold text-[#64748b] whitespace-nowrap">Public URL</th>
                                <th class="px-5 py-3.5 text-right font-semibold text-[#64748b] whitespace-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($quizzes as $i => $q): ?>
                                <tr class="hover:bg-[#f8fafc] transition-colors">
                                    <td class="px-5 py-4 text-[#94a3b8] font-medium whitespace-nowrap">
                                        <?php echo (($currentPage - 1) * 15) + $i + 1; ?>
                                    </td>
                                    <td class="px-5 py-4 min-w-[240px]">
                                        <div class="font-semibold text-[#1e293b]">
                                            <?php echo htmlspecialchars($q['title'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                        <div class="text-xs text-[#94a3b8] mt-0.5">
                                            <?php echo date('d M Y', strtotime($q['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        <?php if (($q['type'] ?? 'quiz') === 'exam'): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                Exam
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Quiz
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        <?php if ((int) ($q['duration_minutes'] ?? 0) > 0): ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-amber-700 bg-amber-50 rounded-lg border border-amber-200 whitespace-nowrap shadow-xs">
                                                <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span><?php echo (int) $q['duration_minutes']; ?> min</span>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-gray-400 bg-gray-50 rounded-lg border border-gray-100 whitespace-nowrap">
                                                Unlimited
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 text-center font-bold text-[#1e293b] whitespace-nowrap">
                                        <?php echo number_format((float) ($q['total_marks'] ?? 0), 2); ?>
                                    </td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        <?php if ($q['status'] === 'active'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 text-center font-semibold text-[#1e293b] whitespace-nowrap">
                                        <?php echo (int) $q['total_attempts']; ?>
                                    </td>
                                    <td class="px-4 py-4 text-center font-semibold text-[#059669] whitespace-nowrap">
                                        <?php echo (int) $q['voice_count']; ?>
                                    </td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        <?php if ((int) $q['unreviewed_count'] > 0): ?>
                                            <span class="inline-flex items-center justify-center w-6 h-6 bg-red-500 text-white text-xs font-bold rounded-full">
                                                <?php echo (int) $q['unreviewed_count']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-300 font-bold">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        <button type="button"
                                            data-copy-quiz-path="/quiz/play.php?id=<?php echo (int) $q['id']; ?>"
                                            class="copy-quiz-btn inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 rounded-lg border border-emerald-200/90 hover:bg-emerald-100 hover:border-emerald-300 transition-all duration-150 whitespace-nowrap shadow-xs cursor-pointer active:scale-95">
                                            <svg class="copy-icon w-3.5 h-3.5 shrink-0 text-emerald-600 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <span class="copy-text">Copy Link</span>
                                        </button>
                                    </td>
                                    <td class="px-5 py-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                            <a href="/admin/quiz/view.php?id=<?php echo (int) $q['id']; ?>"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors">
                                                Report
                                            </a>
                                            <a href="/admin/quiz/edit.php?id=<?php echo (int) $q['id']; ?>"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 transition-colors">
                                                Edit
                                            </a>
                                            <form method="POST" action="/admin/quiz/delete.php"
                                                data-confirm="Are you sure you want to delete this quiz/exam?">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="id" value="<?php echo (int) $q['id']; ?>">
                                                <button type="submit"
                                                    class="px-3 py-1.5 text-xs font-semibold bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($lastPage > 1): ?>
                    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
                        <p class="text-sm text-[#64748b]">Total <strong><?php echo $total; ?></strong> Quizzes / Exams</p>
                        <div class="flex gap-1">
                            <?php for ($p = 1; $p <= $lastPage; $p++): ?>
                                <a href="<?php echo quizPageUrl($p); ?>"
                                    class="w-8 h-8 flex items-center justify-center text-sm rounded-lg font-medium transition-colors
                                  <?php echo $p === $currentPage ? 'bg-[#059669] text-white' : 'text-[#64748b] hover:bg-gray-100'; ?>">
                                    <?php echo $p; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script nonce="<?php echo csp_nonce(); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-copy-quiz-path]');
            if (!btn) return;
            e.preventDefault();
            const path = btn.getAttribute('data-copy-quiz-path');
            if (path) {
                copyQuizLink(btn, path);
            }
        });
    });

    function copyQuizLink(btn, path) {
        const fullUrl = window.location.origin + path;
        const copyTextSpan = btn.querySelector('.copy-text');
        const copyIcon = btn.querySelector('.copy-icon');

        const onCopySuccess = () => {
            const originalClasses = btn.className;
            const originalText = copyTextSpan ? copyTextSpan.textContent : 'Copy Link';

            btn.className = 'copy-quiz-btn inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 text-white border border-emerald-600 transition-all duration-150 whitespace-nowrap shadow-sm';
            if (copyTextSpan) {
                copyTextSpan.textContent = 'Copied!';
            }
            if (copyIcon) {
                copyIcon.classList.remove('text-emerald-600');
                copyIcon.classList.add('text-white');
                copyIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />';
            }

            showToastNotice('Quiz link copied to clipboard!');

            setTimeout(() => {
                btn.className = originalClasses;
                if (copyTextSpan) {
                    copyTextSpan.textContent = originalText;
                }
                if (copyIcon) {
                    copyIcon.classList.remove('text-white');
                    copyIcon.classList.add('text-emerald-600');
                    copyIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />';
                }
            }, 2000);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(fullUrl).then(onCopySuccess).catch(() => {
                fallbackCopy(fullUrl, onCopySuccess);
            });
        } else {
            fallbackCopy(fullUrl, onCopySuccess);
        }
    }

    function fallbackCopy(text, callback) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.top = '0';
        textArea.style.left = '0';
        textArea.style.width = '2em';
        textArea.style.height = '2em';
        textArea.style.padding = '0';
        textArea.style.border = 'none';
        textArea.style.outline = 'none';
        textArea.style.boxShadow = 'none';
        textArea.style.background = 'transparent';
        textArea.setAttribute('readonly', '');
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        textArea.setSelectionRange(0, 99999);

        let successful = false;
        try {
            successful = document.execCommand('copy');
        } catch (err) {
            successful = false;
        }

        document.body.removeChild(textArea);

        if (successful && typeof callback === 'function') {
            callback();
        } else {
            window.prompt('Copy quiz link:', text);
        }
    }

    function showToastNotice(message) {
        const existing = document.getElementById('quizCopyToast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'quizCopyToast';
        toast.className = 'fixed bottom-6 right-6 z-50 flex items-center gap-2 bg-[#059669] text-white text-xs font-semibold px-4 py-2.5 rounded-xl shadow-xl border border-emerald-500 transition-all transform';
        toast.innerHTML = `
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }
        }, 2200);
    }
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
