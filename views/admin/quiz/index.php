<?php
/**
 * Admin Quiz List View
 * Shows all quizzes with status, attempt counts, and actions.
 */
$activeMenu = 'quiz_list';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$quizzes     = $data['quizzes']      ?? [];
$total       = $data['total']        ?? 0;
$currentPage = $data['current_page'] ?? 1;
$lastPage    = $data['last_page']    ?? 1;

function quizPageUrl(int $p): string
{
    $params = $_GET;
    $params['page'] = $p;
    return '/admin/quiz/index.php?' . http_build_query($params);
}
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
    <div class="max-w-[1400px] mx-auto px-6 py-8 space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#1e293b] tracking-tight">কুইজ ম্যানেজমেন্ট</h1>
                <p class="text-sm text-[#64748b] mt-1">আরবি কুইজ তৈরি, পরিচালনা ও রিপোর্ট দেখুন।</p>
            </div>
            <a href="/admin/quiz/create.php"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#059669] hover:bg-[#047857] text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                নতুন কুইজ
            </a>
        </div>

        <!-- Notifications -->
        <?php if (isset($_GET['deleted'])): ?>
        <div class="flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm font-medium">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            কুইজটি সফলভাবে মুছে ফেলা হয়েছে।
        </div>
        <?php endif; ?>

        <!-- Filter Bar -->
        <form method="GET" action="/admin/quiz/index.php"
              class="flex flex-wrap gap-3 p-4 bg-white border border-gray-100 rounded-2xl shadow-sm">
            <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                   placeholder="শিরোনাম খুঁজুন..."
                   class="flex-1 min-w-[200px] px-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669]"/>
            <select name="status"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#059669]/30 bg-white">
                <option value="">সব স্ট্যাটাস</option>
                <option value="active"   <?php echo ($_GET['status'] ?? '') === 'active'   ? 'selected' : ''; ?>>সক্রিয়</option>
                <option value="inactive" <?php echo ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>নিষ্ক্রিয়</option>
            </select>
            <button type="submit"
                    class="px-5 py-2 bg-[#059669] text-white text-sm font-semibold rounded-xl hover:bg-[#047857] transition-colors">
                খুঁজুন
            </button>
            <a href="/admin/quiz/index.php"
               class="px-5 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-200 transition-colors">
                রিসেট
            </a>
        </form>

        <!-- Quiz Table -->
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <?php if (empty($quizzes)): ?>
                <div class="text-center py-16 text-[#64748b]">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="font-medium">কোনো কুইজ পাওয়া যায়নি।</p>
                    <a href="/admin/quiz/create.php" class="mt-3 inline-block text-[#059669] font-semibold hover:underline text-sm">নতুন কুইজ তৈরি করুন →</a>
                </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-[#f8fafc]">
                            <th class="px-6 py-4 text-left font-semibold text-[#64748b]">#</th>
                            <th class="px-6 py-4 text-left font-semibold text-[#64748b]">শিরোনাম</th>
                            <th class="px-6 py-4 text-center font-semibold text-[#64748b]">স্ট্যাটাস</th>
                            <th class="px-6 py-4 text-center font-semibold text-[#64748b]">অংশগ্রহণকারী</th>
                            <th class="px-6 py-4 text-center font-semibold text-[#64748b]">ভয়েস জমা</th>
                            <th class="px-6 py-4 text-center font-semibold text-[#64748b]">অপঠিত</th>
                            <th class="px-6 py-4 text-center font-semibold text-[#64748b]">তৈরির তারিখ</th>
                            <th class="px-6 py-4 text-center font-semibold text-[#64748b]">Public URL</th>
                            <th class="px-6 py-4 text-right font-semibold text-[#64748b]">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                    <?php foreach ($quizzes as $i => $q): ?>
                        <tr class="hover:bg-[#f8fafc] transition-colors">
                            <td class="px-6 py-4 text-[#94a3b8] font-medium">
                                <?php echo (($currentPage - 1) * 15) + $i + 1; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-[#1e293b]">
                                    <?php echo htmlspecialchars($q['title'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($q['status'] === 'active'): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">সক্রিয়</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">নিষ্ক্রিয়</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-[#1e293b]">
                                <?php echo (int) $q['total_attempts']; ?>
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-[#059669]">
                                <?php echo (int) $q['voice_count']; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ((int)$q['unreviewed_count'] > 0): ?>
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-red-500 text-white text-xs font-bold rounded-full">
                                        <?php echo (int) $q['unreviewed_count']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-300">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center text-[#64748b]">
                                <?php echo date('d M Y', strtotime($q['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button onclick="copyUrl('/quiz/play.php?id=<?php echo (int)$q['id']; ?>')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-[#ecfdf5] text-[#059669] rounded-lg hover:bg-emerald-100 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    Copy
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/admin/quiz/view.php?id=<?php echo (int)$q['id']; ?>"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                                        রিপোর্ট
                                    </a>
                                    <a href="/admin/quiz/edit.php?id=<?php echo (int)$q['id']; ?>"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-amber-50 text-amber-700 rounded-lg hover:bg-amber-100 transition-colors">
                                        সম্পাদনা
                                    </a>
                                    <form method="POST" action="/admin/quiz/delete.php"
                                          onsubmit="return confirm('এই কুইজটি মুছে ফেলবেন?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int)$q['id']; ?>">
                                        <button type="submit"
                                                class="px-3 py-1.5 text-xs font-medium bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors">
                                            মুছুন
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
                <p class="text-sm text-[#64748b]">মোট <strong><?php echo $total; ?></strong> টি কুইজ</p>
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

<script>
function copyUrl(path) {
    const url = window.location.origin + path;
    navigator.clipboard.writeText(url).then(() => {
        // Simple toast
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-6 right-6 z-50 bg-[#059669] text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-lg transition-all';
        toast.textContent = '✓ লিংক কপি হয়েছে!';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    });
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
