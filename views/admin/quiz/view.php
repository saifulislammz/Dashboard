<?php
/**
 * Admin Quiz & Exam Report View
 * Real-time stats, participant evaluation, in-browser voice review recording & one-click WhatsApp dispatch.
 */
$activeMenu = 'quiz_view';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$quiz = $data['quiz'] ?? [];
$stats = $data['stats'] ?? [];
$attempts = $data['attempts'] ?? [];
$total = $data['total'] ?? 0;
$currentPage = $data['current_page'] ?? 1;
$lastPage = $data['last_page'] ?? 1;
$quizId = (int) ($quiz['id'] ?? 0);

$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
);
$protocol = $isHttps ? 'https' : 'http';
$publicUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/quiz/play.php?id=' . $quizId;

function rPageUrl(int $p): string
{
    $params = $_GET;
    $params['page'] = $p;
    return '/admin/quiz/view.php?' . http_build_query($params);
}
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]" x-data="quizReportPage()">
    <div class="max-w-[1440px] mx-auto px-6 py-8 space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="/admin/quiz/index.php"
                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl font-bold text-[#1e293b]">
                            <?php echo htmlspecialchars($quiz['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold <?php echo ($quiz['type'] ?? 'quiz') === 'exam' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'; ?>">
                            <?php echo strtoupper($quiz['type'] ?? 'quiz'); ?>
                        </span>
                    </div>
                    <p class="text-sm text-[#64748b] mt-0.5">
                        <?php if ((int)($quiz['duration_minutes'] ?? 0) > 0): ?>
                            Duration: <?php echo (int)$quiz['duration_minutes']; ?> mins |
                        <?php endif; ?>
                        Total Marks: <?php echo number_format((float)($quiz['total_marks'] ?? 0), 2); ?> | 
                        Attempt Limit: <?php echo (int)($quiz['attempt_limit'] ?? 1) > 0 ? (int)$quiz['attempt_limit'] . ' time(s)' : 'Unlimited'; ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="/admin/quiz/edit.php?id=<?php echo $quizId; ?>"
                    class="px-4 py-2 text-sm font-semibold bg-yellow-50 text-yellow-700 rounded-xl hover:bg-yellow-100 transition-colors">
                    Edit Quiz/Exam
                </a>
            </div>
        </div>

        <!-- Toast notifications -->
        <?php if (isset($created) && $created): ?>
            <div class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm font-medium">
                Quiz/Exam created successfully!
            </div>
        <?php endif; ?>

        <!-- Public URL Copy Card -->
        <div class="flex items-center gap-3 p-4 bg-white border border-gray-100 rounded-2xl shadow-sm">
            <svg class="w-5 h-5 text-[#059669] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            <span class="text-sm text-[#64748b] font-medium flex-shrink-0">Student Link:</span>
            <code
                class="flex-1 text-sm text-[#1e293b] font-mono bg-[#f8fafc] px-3 py-1.5 rounded-lg border border-gray-100 truncate"
                id="quizPublicUrl"><?php echo htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8'); ?></code>
            <button type="button" id="copyPublicUrlBtn"
                class="flex-shrink-0 flex items-center gap-1.5 px-4 py-1.5 bg-[#059669] text-white text-xs font-semibold rounded-lg hover:bg-[#047857] transition-all cursor-pointer active:scale-95 whitespace-nowrap shadow-xs">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span>Copy Link</span>
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-8 h-8 rounded-lg bg-[#ecfdf5] text-[#059669] flex items-center justify-center font-bold text-xs">P</span>
                    <span class="text-xs font-semibold text-[#64748b]">Total Candidates</span>
                </div>
                <p class="text-2xl font-bold text-[#1e293b]">
                    <?php echo number_format((int) ($stats['total_participants'] ?? 0)); ?>
                </p>
            </div>

            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">✓</span>
                    <span class="text-xs font-semibold text-[#64748b]">Completed</span>
                </div>
                <p class="text-2xl font-bold text-emerald-600">
                    <?php echo number_format((int) ($stats['completed_count'] ?? 0)); ?>
                </p>
                <p class="text-[11px] text-gray-400 mt-0.5"><?php echo $stats['completion_pct'] ?? 0; ?>% finished</p>
            </div>

            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs">⏱</span>
                    <span class="text-xs font-semibold text-[#64748b]">Auto-submitted</span>
                </div>
                <p class="text-2xl font-bold text-amber-600">
                    <?php echo number_format((int) ($stats['auto_submitted_count'] ?? 0)); ?>
                </p>
                <p class="text-[11px] text-gray-400 mt-0.5">Time ended</p>
            </div>

            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">★</span>
                    <span class="text-xs font-semibold text-[#64748b]">Average Score</span>
                </div>
                <p class="text-2xl font-bold text-blue-600">
                    <?php echo number_format((float) ($stats['avg_obtained_marks'] ?? 0), 2); ?>
                </p>
                <p class="text-[11px] text-gray-400 mt-0.5">Max: <?php echo number_format((float)($stats['highest_obtained_marks'] ?? 0), 2); ?></p>
            </div>

            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs">🎙</span>
                    <span class="text-xs font-semibold text-[#64748b]">Unreviewed Voice</span>
                </div>
                <p class="text-2xl font-bold text-red-600">
                    <?php echo number_format((int) ($stats['unreviewed_count'] ?? 0)); ?>
                </p>
                <p class="text-[11px] text-gray-400 mt-0.5">Need review</p>
            </div>
        </div>

        <!-- Participant Table -->
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-[#1e293b]">Candidate Submissions & Grading</h2>
                <span class="text-xs text-gray-500">Total Candidates: <?php echo $total; ?></span>
            </div>

            <?php if (empty($attempts)): ?>
                <div class="text-center py-12 text-[#64748b] text-sm">
                    <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    No candidates have participated yet.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-[#f8fafc] border-b border-gray-100 text-[#64748b]">
                                <th class="px-4 py-3 text-left font-semibold">#</th>
                                <th class="px-4 py-3 text-left font-semibold">Candidate</th>
                                <th class="px-4 py-3 text-left font-semibold">WhatsApp</th>
                                <th class="px-4 py-3 text-center font-semibold">Status / Time</th>
                                <th class="px-4 py-3 text-center font-semibold">Score / Marks</th>
                                <th class="px-4 py-3 text-center font-semibold">Student Voice</th>
                                <th class="px-4 py-3 text-left font-semibold">Admin Audio Review & WhatsApp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($attempts as $i => $a): ?>
                                <tr class="hover:bg-[#f8fafc] transition-colors" id="attempt-row-<?php echo (int) $a['id']; ?>">
                                    <td class="px-4 py-3 text-[#94a3b8] font-medium">
                                        <?php echo (($currentPage - 1) * 20) + $i + 1; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-[#1e293b]">
                                                <?php echo htmlspecialchars($a['participant_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">
                                                #<?php echo (int) ($a['attempt_number'] ?? 1); ?>
                                            </span>
                                        </div>
                                        <div class="text-xs text-[#64748b] flex items-center gap-2 mt-0.5">
                                            <span><?php echo $a['gender'] === 'male' ? 'Male' : 'Female'; ?></span>
                                            <?php if (!empty($a['custom_fields_data']) && is_array($a['custom_fields_data'])): ?>
                                                <?php foreach ($a['custom_fields_data'] as $k => $v): ?>
                                                    <span class="text-gray-300">|</span>
                                                    <span class="font-mono text-gray-700 bg-gray-50 px-1 rounded"><?php echo htmlspecialchars($k . ': ' . $v, ENT_QUOTES, 'UTF-8'); ?></span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-[#1e293b]">
                                            <?php echo htmlspecialchars($a['whatsapp_number'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                        <?php if (!empty($a['email'])): ?>
                                            <div class="text-xs text-[#64748b]">
                                                <?php echo htmlspecialchars($a['email'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($a['auto_submitted']): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                                Auto-submitted
                                            </span>
                                        <?php elseif ($a['status'] === 'completed' || $a['status'] === 'voice_submitted'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-green-100 text-green-700">
                                                Completed
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600">
                                                In Progress
                                            </span>
                                        <?php endif; ?>
                                        <div class="text-[11px] text-[#94a3b8] mt-1">
                                            <?php echo date('d M H:i', strtotime($a['started_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="font-black text-base text-[#059669]">
                                            <?php echo number_format((float) ($a['obtained_marks'] ?? 0), 2); ?>
                                            <span class="text-xs font-normal text-gray-400">/ <?php echo number_format((float) ($a['total_marks'] > 0 ? $a['total_marks'] : $a['total_questions']), 2); ?></span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            <?php echo (int) $a['correct_answers']; ?>/<?php echo (int) $a['total_questions']; ?> Correct
                                            (<?php echo $a['score_pct'] ?? 0; ?>%)
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($a['voice_submitted']): ?>
                                            <audio controls src="/admin/quiz/serve_voice.php?a=<?php echo (int) $a['id']; ?>"
                                                class="h-8 max-w-[140px] mx-auto" preload="none">
                                            </audio>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400 italic">No voice answer</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-2">
                                            <!-- Admin Voice Preview if recorded -->
                                            <?php if (!empty($a['admin_voice_file_path'])): ?>
                                                <div class="flex items-center gap-2 bg-[#ecfdf5] border border-[#d1fae5] p-1.5 rounded-xl">
                                                    <audio controls src="/admin/quiz/serve_admin_voice.php?a=<?php echo (int) $a['id']; ?>"
                                                        class="h-7 max-w-[140px]" preload="none"></audio>
                                                    <?php if ($a['voice_marks'] !== null): ?>
                                                        <span class="text-xs font-bold text-emerald-700 bg-white px-2 py-0.5 rounded border border-emerald-200">
                                                            +<?php echo number_format((float) $a['voice_marks'], 2); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Actions: Record Voice & WhatsApp -->
                                            <div class="flex items-center gap-2">
                                                <button type="button"
                                                    @click="openReviewModal(<?php echo htmlspecialchars(json_encode([
                                                        'id' => (int) $a['id'],
                                                        'name' => $a['participant_name'],
                                                        'whatsapp' => $a['whatsapp_number'],
                                                        'voice_marks' => $a['voice_marks'],
                                                        'voice_note' => $a['voice_note'],
                                                    ]), ENT_QUOTES, 'UTF-8'); ?>)"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 transition-colors shadow-sm">
                                                    <svg class="w-3.5 h-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                                    </svg>
                                                    <?php echo !empty($a['admin_voice_file_path']) ? 'Re-record Review' : 'Record Review'; ?>
                                                </button>

                                                <button type="button"
                                                    @click="sendWhatsApp(<?php echo (int) $a['id']; ?>)"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-[#25D366] hover:bg-[#20ba59] text-white transition-colors shadow-sm">
                                                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.072.043.419-.101.824z"/>
                                                    </svg>
                                                    WhatsApp
                                                </button>
                                            </div>
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
                        <p class="text-sm text-[#64748b]">Total <strong><?php echo $total; ?></strong> candidates</p>
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

    <!-- ADMIN VOICE REVIEW RECORDER MODAL -->
    <div x-show="modalOpen" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-gray-100"
            @click.away="if (!isRecording && !isUploading) modalOpen = false">

            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Teacher Voice Review & Evaluation</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Candidate: <strong class="text-gray-700" x-text="activeAttempt?.name"></strong>
                        (<span x-text="activeAttempt?.whatsapp"></span>)
                    </p>
                </div>
                <button type="button" @click="if (!isRecording && !isUploading) modalOpen = false"
                    class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 rounded-lg">
                    ✕
                </button>
            </div>

            <!-- Voice Recording Box -->
            <div class="bg-[#f8fafc] border border-gray-200 rounded-2xl p-6 text-center space-y-4">
                <!-- Recording pulse icon -->
                <div class="relative inline-flex items-center justify-center">
                    <button type="button" @click="toggleRecording()"
                        :disabled="isUploading"
                        class="w-20 h-20 rounded-full flex items-center justify-center transition-all shadow-lg focus:outline-none"
                        :class="isRecording ? 'bg-red-500 text-white animate-pulse' : 'bg-[#059669] text-white hover:bg-[#047857]'">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </button>
                </div>

                <!-- Timer / Status text -->
                <div>
                    <p class="text-lg font-mono font-bold text-gray-700" x-text="formatTime(recordingSeconds)"></p>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="isRecording ? 'Recording voice... (Max 2 mins)' : (audioBlob ? 'Recording complete. Listen below.' : 'Click the button above to record')"></p>
                </div>

                <!-- Audio Preview Player -->
                <div x-show="audioUrl" class="pt-2">
                    <audio :src="audioUrl" controls class="w-full h-9 rounded-lg"></audio>
                </div>

                <div x-show="audioUrl && !isRecording" class="pt-1">
                    <button type="button" @click="resetRecording()"
                        class="text-xs font-semibold text-red-600 hover:underline">
                        Cancel and record again
                    </button>
                </div>
            </div>

            <!-- Voice Marks & Notes Form -->
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-700">Voice Marks</label>
                        <input type="number" step="0.5" min="0" max="100" x-model="voiceMarks"
                            placeholder="e.g. 4.5"
                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm font-bold text-[#059669] focus:outline-none focus:ring-1 focus:ring-[#059669]">
                        <p class="text-[11px] text-gray-400">Optional (added to total marks)</p>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-700">Feedback Note</label>
                        <textarea rows="2" x-model="voiceNote"
                            placeholder="e.g. Well done, good pronunciation..."
                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-1 focus:ring-[#059669] resize-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- Error Notice -->
            <p x-show="errorMessage" x-text="errorMessage" class="text-xs text-red-600 font-medium"></p>

            <!-- Modal Footer Buttons -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                <button type="button" @click="modalOpen = false" :disabled="isUploading"
                    class="px-5 py-2.5 border border-gray-200 text-gray-600 text-xs font-bold rounded-xl hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" @click="saveAndShareReview()" :disabled="!audioBlob || isUploading || isRecording"
                    class="px-5 py-2.5 bg-[#059669] hover:bg-[#047857] disabled:opacity-50 text-white text-xs font-bold rounded-xl transition-colors shadow-sm flex items-center gap-1.5">
                    <span x-show="!isUploading">Save & Prepare WhatsApp Link</span>
                    <span x-show="isUploading">Saving...</span>
                </button>
            </div>
        </div>
    </div>
</main>

<script nonce="<?php echo csp_nonce(); ?>">
    const CSRF_TOKEN = '<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>';

    function quizReportPage() {
        return {
            modalOpen: false,
            activeAttempt: null,
            isRecording: false,
            isUploading: false,
            recordingSeconds: 0,
            timerInterval: null,
            mediaRecorder: null,
            audioBlob: null,
            audioUrl: null,
            voiceMarks: '',
            voiceNote: '',
            errorMessage: '',

            openReviewModal(attempt) {
                this.activeAttempt = attempt;
                this.voiceMarks = attempt.voice_marks || '';
                this.voiceNote = attempt.voice_note || '';
                this.resetRecording();
                this.modalOpen = true;
            },

            async toggleRecording() {
                if (this.isRecording) {
                    this.stopRecording();
                } else {
                    this.startRecording();
                }
            },

            async startRecording() {
                this.errorMessage = '';
                this.audioBlob = null;
                this.audioUrl = null;
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    const chunks = [];
                    this.mediaRecorder = new MediaRecorder(stream);
                    this.mediaRecorder.ondataavailable = e => { if (e.data.size > 0) chunks.push(e.data); };
                    this.mediaRecorder.onstop = () => {
                        this.audioBlob = new Blob(chunks, { type: 'audio/webm' });
                        this.audioUrl = URL.createObjectURL(this.audioBlob);
                        stream.getTracks().forEach(t => t.stop());
                    };
                    this.mediaRecorder.start(250);
                    this.isRecording = true;
                    this.recordingSeconds = 0;

                    this.timerInterval = setInterval(() => {
                        this.recordingSeconds++;
                        if (this.recordingSeconds >= 120) {
                            this.stopRecording();
                        }
                    }, 1000);
                } catch (e) {
                    this.errorMessage = 'Please allow microphone access permission and try again.';
                }
            },

            stopRecording() {
                if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                    this.mediaRecorder.stop();
                }
                clearInterval(this.timerInterval);
                this.isRecording = false;
            },

            resetRecording() {
                this.stopRecording();
                this.audioBlob = null;
                if (this.audioUrl) URL.revokeObjectURL(this.audioUrl);
                this.audioUrl = null;
                this.recordingSeconds = 0;
                this.errorMessage = '';
            },

            formatTime(sec) {
                const m = Math.floor(sec / 60).toString().padStart(2, '0');
                const s = (sec % 60).toString().padStart(2, '0');
                return `${m}:${s}`;
            },

            async saveAndShareReview() {
                if (!this.audioBlob || !this.activeAttempt) return;
                this.isUploading = true;
                this.errorMessage = '';

                const form = new FormData();
                form.append('csrf_token', CSRF_TOKEN);
                form.append('attempt_id', this.activeAttempt.id);
                form.append('voice_marks', this.voiceMarks);
                form.append('voice_note', this.voiceNote);
                form.append('voice_file', this.audioBlob, 'review_voice.webm');

                try {
                    const res = await fetch('/admin/quiz/upload_admin_voice.php', {
                        method: 'POST',
                        body: form
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.modalOpen = false;
                        if (data.whatsapp_url) {
                            window.open(data.whatsapp_url, '_blank');
                        }
                        window.location.reload();
                    } else {
                        this.errorMessage = data.message || 'Failed to save review.';
                    }
                } catch (e) {
                    this.errorMessage = 'Network error. Please try again.';
                } finally {
                    this.isUploading = false;
                }
            },

            async sendWhatsApp(attemptId) {
                try {
                    const res = await fetch(`/admin/quiz/get_whatsapp_url.php?a=${attemptId}`);
                    const data = await res.json();
                    if (data.success && data.whatsapp_url) {
                        window.open(data.whatsapp_url, '_blank');
                    } else {
                        alert(data.message || 'Could not generate WhatsApp link.');
                    }
                } catch (e) {
                    alert('Network error.');
                }
            }
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        const copyPublicBtn = document.getElementById('copyPublicUrlBtn');
        if (copyPublicBtn) {
            copyPublicBtn.addEventListener('click', function (e) {
                e.preventDefault();
                copyPublicUrl(copyPublicBtn);
            });
        }
    });

    function copyPublicUrl(btn) {
        const urlElement = document.getElementById('quizPublicUrl');
        const url = urlElement ? urlElement.textContent.trim() : window.location.href;

        const onCopySuccess = () => {
            if (btn) {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = `
                    <svg class="w-3.5 h-3.5 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Copied!</span>
                `;
                setTimeout(() => { btn.innerHTML = originalHtml; }, 2000);
            }
            const existing = document.getElementById('viewCopyToast');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.id = 'viewCopyToast';
            toast.className = 'fixed bottom-6 right-6 z-50 flex items-center gap-2 bg-[#059669] text-white text-xs font-semibold px-4 py-2.5 rounded-xl shadow-xl border border-emerald-500 transition-all';
            toast.innerHTML = `
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span>Link copied to clipboard!</span>
            `;
            document.body.appendChild(toast);
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.style.opacity = '0';
                    toast.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => toast.remove(), 300);
                }
            }, 2200);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(onCopySuccess).catch(() => {
                fallbackCopy(url, onCopySuccess);
            });
        } else {
            fallbackCopy(url, onCopySuccess);
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
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
