<?php
/**
 * Admin Quiz Report View â€” Stats cards + participant table + voice review
 */
$activeMenu = 'quiz_view';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$quiz        = $data['quiz']         ?? [];
$stats       = $data['stats']        ?? [];
$attempts    = $data['attempts']     ?? [];
$total       = $data['total']        ?? 0;
$currentPage = $data['current_page'] ?? 1;
$lastPage    = $data['last_page']    ?? 1;
$quizId      = (int) ($quiz['id'] ?? 0);

$publicUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
           . '://' . $_SERVER['HTTP_HOST'] . '/quiz/play.php?id=' . $quizId;

function rPageUrl(int $p): string {
    $params = $_GET;
    $params['page'] = $p;
    return '/admin/quiz/view.php?' . http_build_query($params);
}
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
<div class="max-w-[1400px] mx-auto px-6 py-8 space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="/admin/quiz/index.php" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-[#1e293b]"><?php echo htmlspecialchars($quiz['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="text-sm text-[#64748b] mt-0.5">Quiz Report and Participant Details</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/quiz/edit.php?id=<?php echo $quizId; ?>"
               class="px-4 py-2 text-sm font-medium bg-yellow-50 text-yellow-700 rounded-xl hover:bg-yellow-100 transition-colors">Edit</a>
        </div>
    </div>

    <!-- Toast notifications -->
    <?php if (isset($created) && $created): ?>
    <div class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm font-medium">
        âœ“ Quiz created successfully!
    </div>
    <?php endif; ?>

    <!-- Public URL Copy -->
    <div class="flex items-center gap-3 p-4 bg-white border border-gray-100 rounded-2xl shadow-sm">
        <svg class="w-5 h-5 text-[#059669] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
        </svg>
        <span class="text-sm text-[#64748b] font-medium flex-shrink-0">Public Link:</span>
        <code class="flex-1 text-sm text-[#1e293b] font-mono bg-[#f8fafc] px-3 py-1.5 rounded-lg border border-gray-100 truncate" id="quizPublicUrl">
            <?php echo htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8'); ?>
        </code>
        <button onclick="copyPublicUrl()"
                class="flex-shrink-0 flex items-center gap-1.5 px-4 py-1.5 bg-[#059669] text-white text-xs font-semibold rounded-lg hover:bg-[#047857] transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            Copy
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-[#ecfdf5] flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#059669]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <span class="text-sm font-medium text-[#64748b]">Total Participants</span>
            </div>
            <p class="text-3xl font-bold text-[#1e293b]"><?php echo number_format((int)($stats['total_participants'] ?? 0)); ?></p>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-[#ecfdf5] flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#059669]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-sm font-medium text-[#64748b]">Completed</span>
            </div>
            <p class="text-3xl font-bold text-[#059669]"><?php echo number_format((int)($stats['completed_count'] ?? 0)); ?></p>
            <p class="text-xs text-[#64748b] mt-1"><?php echo $stats['completion_pct'] ?? 0; ?>%</p>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-sm font-medium text-[#64748b]">Abandoned</span>
            </div>
            <p class="text-3xl font-bold text-red-500"><?php echo number_format((int)($stats['abandoned_count'] ?? 0)); ?></p>
            <p class="text-xs text-[#64748b] mt-1"><?php echo $stats['abandoned_pct'] ?? 0; ?>%</p>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                </div>
                <span class="text-sm font-medium text-[#64748b]">Unreviewed Voice</span>
            </div>
            <p class="text-3xl font-bold text-red-600"><?php echo number_format((int)($stats['unreviewed_count'] ?? 0)); ?></p>
        </div>
    </div>

    <!-- Participant Table -->
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-[#1e293b]">List of Participants</h2>
        </div>

        <?php if (empty($attempts)): ?>
            <div class="text-center py-12 text-[#64748b] text-sm">
                <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                No participants yet.
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-[#f8fafc] border-b border-gray-100">
                        <th class="px-4 py-3 text-left font-semibold text-[#64748b]">#</th>
                        <th class="px-4 py-3 text-left font-semibold text-[#64748b]">Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-[#64748b]">WhatsApp</th>
                        <th class="px-4 py-3 text-left font-semibold text-[#64748b]">Date</th>
                        <th class="px-4 py-3 text-center font-semibold text-[#64748b]">Correct</th>
                        <th class="px-4 py-3 text-center font-semibold text-[#64748b]">Incorrect</th>
                        <th class="px-4 py-3 text-center font-semibold text-[#64748b]">Score</th>
                        <th class="px-4 py-3 text-center font-semibold text-[#64748b]">Voice</th>
                        <th class="px-4 py-3 text-left font-semibold text-[#64748b]">Review Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                <?php foreach ($attempts as $i => $a): ?>
                    <tr class="hover:bg-[#f8fafc] transition-colors" x-data="voiceReview(<?php echo (int)$a['id']; ?>, <?php echo json_encode($a['voice_note'] ?? ''); ?>)">
                        <td class="px-4 py-3 text-[#94a3b8] font-medium"><?php echo (($currentPage-1)*20)+$i+1; ?></td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-[#1e293b]"><?php echo htmlspecialchars($a['participant_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="text-xs text-[#64748b]"><?php echo $a['gender'] === 'male' ? 'Male' : 'Female'; ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-[#1e293b]"><?php echo htmlspecialchars($a['whatsapp_number'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php if (!empty($a['email'])): ?>
                            <div class="text-xs text-[#64748b]"><?php echo htmlspecialchars($a['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-[#64748b] text-xs whitespace-nowrap">
                            <?php echo date('d M Y H:i', strtotime($a['started_at'])); ?>
                        </td>
                        <td class="px-4 py-3 text-center font-bold text-[#059669]"><?php echo (int)$a['correct_answers']; ?></td>
                        <td class="px-4 py-3 text-center font-bold text-red-500"><?php echo max(0, (int)$a['total_questions'] - (int)$a['correct_answers']); ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                                <?php
                                $pct = $a['score_pct'] ?? 0;
                                echo $pct >= 70 ? 'bg-green-100 text-green-700' : ($pct >= 40 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
                                ?>">
                                <?php echo $pct; ?>%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($a['voice_submitted']): ?>
                                <audio controls
                                       src="/admin/quiz/serve_voice.php?a=<?php echo (int)$a['id']; ?>"
                                       class="h-8 max-w-[160px]"
                                       preload="none">
                                </audio>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">â€”</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 min-w-[220px]">
                            <?php if ($a['voice_submitted']): ?>
                            <div class="space-y-2">
                                <textarea x-model="note" rows="2"
                                          placeholder="Write review note..."
                                          class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#059669] resize-none"></textarea>
                                <div class="flex items-center gap-2">
                                    <button @click="saveNote()"
                                            :disabled="saving"
                                            class="px-3 py-1.5 bg-[#059669] text-white text-xs font-semibold rounded-lg hover:bg-[#047857] disabled:opacity-50 transition-colors">
                                        <span x-text="saving ? 'Saving...' : 'Save'"></span>
                                    </button>
                                    <span x-show="saved" x-transition class="text-xs text-[#059669] font-medium">âœ“ Saved</span>
                                    <!-- WhatsApp link -->
                                    <a :href="`https://wa.me/<?php echo htmlspecialchars(preg_replace('/[^0-9]/', '', $a['whatsapp_number']), ENT_QUOTES, 'UTF-8'); ?>?text=${encodeURIComponent(note)}`"
                                       target="_blank"
                                       class="ml-auto px-2.5 py-1.5 bg-green-100 text-green-700 text-xs font-semibold rounded-lg hover:bg-green-200 transition-colors">
                                        WA
                                    </a>
                                </div>
                            </div>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Voice not submitted</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($lastPage > 1): ?>
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
            <p class="text-sm text-[#64748b]">Total <strong><?php echo $total; ?></strong> people</p>
            <div class="flex gap-1">
                <?php for ($p = 1; $p <= $lastPage; $p++): ?>
                    <a href="<?php echo rPageUrl($p); ?>"
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
const CSRF_TOKEN = '<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>';

function voiceReview(attemptId, existingNote) {
    return {
        note: existingNote || '',
        saving: false,
        saved: false,
        async saveNote() {
            this.saving = true;
            this.saved  = false;
            try {
                const r = await fetch('/admin/quiz/review_voice.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: CSRF_TOKEN, attempt_id: attemptId, note: this.note })
                });
                const d = await r.json();
                if (d.success) { this.saved = true; setTimeout(() => this.saved = false, 3000); }
                else alert(d.message || 'Failed to save.');
            } catch(e) { alert('Network error.'); }
            finally { this.saving = false; }
        }
    };
}

function copyPublicUrl() {
    const url = document.getElementById('quizPublicUrl').textContent.trim();
    navigator.clipboard.writeText(url).then(() => {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-6 right-6 z-50 bg-[#059669] text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-lg';
        toast.textContent = 'âœ“ Link copied!';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    });
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

