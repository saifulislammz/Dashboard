<?php
/**
 * Admin Quiz & Exam Edit View
 * Unified builder for quizzes and exams with custom fields, scoring, and duration.
 */
$activeMenu = 'quiz_list';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$quizData = $quiz ?? [];
$questions = $quizData['questions'] ?? [];
$customFields = $quizData['custom_fields'] ?? [];

// Build initial questions state from DB
$initialQuestions = [];
foreach ($questions as $idx => $q) {
    $opts = [];
    foreach ($q['options'] ?? [] as $o) {
        $opts[] = ['text' => $o['option_text'], 'is_correct' => (bool) (int) $o['is_correct']];
    }
    $initialQuestions[] = [
        'uid'           => $idx,
        'type'          => $q['type'],
        'question_text' => $q['question_text'],
        'marks'         => (float) ($q['marks'] ?? 1.00),
        'options'       => $opts,
    ];
}
$initialJson = json_encode($initialQuestions, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
$customFieldsJson = json_encode($customFields, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
    <div class="max-w-[960px] mx-auto px-6 py-8 space-y-6">

        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="/admin/quiz/index.php"
                class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-[#1e293b]">Edit Quiz or Exam</h1>
                <p class="text-sm text-[#64748b] mt-0.5">
                    <?php echo htmlspecialchars($quizData['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/quiz/edit.php" id="quizForm">
            <input type="hidden" name="csrf_token"
                value="<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="id" value="<?php echo (int) ($quizData['id'] ?? 0); ?>">

            <!-- General Info Card -->
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 space-y-5 mb-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="text-base font-semibold text-[#1e293b]">1. Basic Information</h2>
                    <span id="examBadge" class="text-xs font-bold px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <?php echo ($quizData['type'] ?? 'quiz') === 'exam' ? 'Exam Mode' : 'Quiz Mode'; ?>
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2 space-y-1">
                        <label class="text-sm font-medium text-[#374151]">Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" required maxlength="255"
                            value="<?php echo htmlspecialchars($quizData['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669]" />
                    </div>

                    <div class="space-y-1">
                        <label class="text-sm font-medium text-[#374151]">Type <span class="text-red-500">*</span></label>
                        <select name="type" id="quizTypeSelect"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white font-medium text-[#1e293b] focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669]">
                            <option value="quiz" <?php echo ($quizData['type'] ?? '') === 'quiz' ? 'selected' : ''; ?>>Quiz (Practice / General Quiz)</option>
                            <option value="exam" <?php echo ($quizData['type'] ?? '') === 'exam' ? 'selected' : ''; ?>>Exam (Formal Examination)</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium text-[#374151]">Description / Instructions (Optional)</label>
                    <textarea name="description" rows="2" maxlength="5000"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669] resize-none"><?php echo htmlspecialchars($quizData['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <!-- Exam Specific Configuration Box -->
                <div id="examSettingsBox" class="p-4 bg-[#f8fafc] border border-gray-200 rounded-xl space-y-4">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Time & Attempt Controls</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-gray-700">Duration (Minutes)</label>
                            <input type="number" name="duration_minutes" min="0" max="600"
                                value="<?php echo (int) ($quizData['duration_minutes'] ?? 0); ?>"
                                placeholder="0 = Unlimited"
                                class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-1 focus:ring-[#059669]">
                            <p class="text-[11px] text-gray-400">0 means no time limit</p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-gray-700">Attempt Limit</label>
                            <input type="number" name="attempt_limit" min="0" max="10"
                                value="<?php echo isset($quizData['attempt_limit']) ? (int) $quizData['attempt_limit'] : 1; ?>"
                                placeholder="1 = Once"
                                class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-1 focus:ring-[#059669]">
                            <p class="text-[11px] text-gray-400">0 means unlimited attempts</p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-gray-700">Pass Marks</label>
                            <input type="number" step="0.5" min="0" name="pass_marks"
                                value="<?php echo (float) ($quizData['pass_marks'] ?? 0.00); ?>"
                                placeholder="0.00"
                                class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-1 focus:ring-[#059669]">
                            <p class="text-[11px] text-gray-400">Optional (pass/fail threshold)</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-[#374151]">Status</label>
                        <select name="status"
                            class="px-4 py-2 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#059669]/30">
                            <option value="active" <?php echo ($quizData['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($quizData['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Custom Registration Fields Card -->
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 space-y-4 mb-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-semibold text-[#1e293b]">2. Custom Registration Fields</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Add custom input fields to collect extra information from candidates (e.g. Roll No, Batch).</p>
                    </div>
                    <button type="button" id="addCustomFieldBtn"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-[#059669] bg-[#ecfdf5] hover:bg-[#d1fae5] rounded-xl transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        + Add Custom Field
                    </button>
                </div>

                <div id="customFieldsContainer"></div>
            </div>

            <!-- Questions -->
            <div class="space-y-4 mb-6">
                <div class="flex items-center justify-between bg-white px-5 py-3.5 border border-gray-100 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-[#1e293b]">3. Questions & Scoring</h2>
                        <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-600 rounded-full" id="questionCountText">0 Questions</span>
                    </div>
                    <div class="text-sm font-bold text-[#059669] bg-[#ecfdf5] px-3.5 py-1 rounded-xl border border-[#d1fae5]">
                        Total Marks: <span id="totalMarksText">0.00 Marks</span>
                    </div>
                </div>

                <div id="questionsContainer"></div>

                <!-- Add Question Buttons -->
                <div class="bg-white p-4 border border-gray-100 rounded-2xl shadow-sm space-y-3">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Select question format to add:</span>
                    <div class="flex flex-wrap gap-2.5">
                        <button type="button" id="addMcqBtn"
                            class="flex items-center gap-1.5 px-4 py-2.5 border-2 border-dashed border-[#059669]/40 text-[#059669] text-xs font-bold rounded-xl hover:border-[#059669] hover:bg-[#ecfdf5] transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            + MCQ (Single Choice)
                        </button>
                        <button type="button" id="addMultiChoiceBtn"
                            class="flex items-center gap-1.5 px-4 py-2.5 border-2 border-dashed border-blue-400/50 text-blue-600 text-xs font-bold rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            + Multiple Choice (Multi Select)
                        </button>
                        <button type="button" id="addLetterBtn"
                            class="flex items-center gap-1.5 px-4 py-2.5 border-2 border-dashed border-emerald-400/50 text-emerald-700 text-xs font-bold rounded-xl hover:border-emerald-500 hover:bg-emerald-50 transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            + Arabic Letter
                        </button>
                        <button type="button" id="addPronunciationBtn"
                            class="flex items-center gap-1.5 px-4 py-2.5 border-2 border-dashed border-teal-400/50 text-teal-700 text-xs font-bold rounded-xl hover:border-teal-500 hover:bg-teal-50 transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            + Pronunciation
                        </button>
                        <button type="button" id="addVoiceBtn"
                            class="flex items-center gap-1.5 px-4 py-2.5 border-2 border-dashed border-red-400/50 text-red-600 text-xs font-bold rounded-xl hover:border-red-500 hover:bg-red-50 transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            + Voice Recording
                        </button>
                    </div>
                </div>

                <p id="noQuestionsMsg" class="text-sm text-yellow-600 font-medium">
                    Please add at least one question.
                </p>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3 pb-8">
                <a href="/admin/quiz/index.php"
                    class="px-6 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" id="submitBtn"
                    class="px-6 py-2.5 bg-[#059669] hover:bg-[#047857] disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</main>

<!-- Google Fonts: Amiri (Arabic) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

<!-- Unified Quiz Builder JS -->
<script nonce="<?php echo csp_nonce(); ?>">
    window.QUIZ_INITIAL_DATA = <?php echo $initialJson; ?>;
    window.QUIZ_CUSTOM_FIELDS = <?php echo $customFieldsJson; ?>;
</script>
<script nonce="<?php echo csp_nonce(); ?>" src="/js/admin/quiz-builder.js?v=<?php echo time(); ?>"></script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
