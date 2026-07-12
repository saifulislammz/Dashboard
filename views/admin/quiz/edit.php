<?php
/**
 * Admin Quiz Edit View
 * Vanilla JS version
 */
$activeMenu = 'quiz_list';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$quizData = $quiz ?? [];
$questions = $quizData['questions'] ?? [];

// Build initial state from DB
$initialQuestions = [];
foreach ($questions as $idx => $q) {
    $opts = [];
    foreach ($q['options'] ?? [] as $o) {
        $opts[] = ['text' => $o['option_text'], 'is_correct' => (bool) (int) $o['is_correct']];
    }
    $initialQuestions[] = [
        'uid' => $idx,
        'type' => $q['type'],
        'question_text' => $q['question_text'],
        'options' => $opts,
    ];
}
$initialJson = json_encode($initialQuestions, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
    <div class="max-w-[900px] mx-auto px-6 py-8 space-y-6">

        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="/admin/quiz/index.php"
                class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-[#1e293b]">Edit Quiz</h1>
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

            <!-- Quiz Info -->
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 space-y-5 mb-6">
                <h2 class="text-base font-semibold text-[#1e293b]">Quiz Information</h2>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-[#374151]">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required maxlength="255"
                        value="<?php echo htmlspecialchars($quizData['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669]" />
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-[#374151]">Description (Optional)</label>
                    <textarea name="description" rows="3" maxlength="5000"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669] resize-none"><?php echo htmlspecialchars($quizData['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-[#374151]">Status</label>
                    <select name="status"
                        class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669]">
                        <option value="active" <?php echo ($quizData['status'] ?? '') === 'active' ? 'selected' : ''; ?>>
                            Active</option>
                        <option value="inactive" <?php echo ($quizData['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Questions -->
            <div class="space-y-4 mb-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-[#1e293b]">Questions</h2>
                    <span class="text-sm text-[#64748b]" id="questionCountText">0 Questions</span>
                </div>

                <div id="questionsContainer"></div>

                <!-- Add Question Buttons -->
                <div class="flex flex-wrap gap-3">
                    <button type="button" id="addLetterBtn"
                        class="flex items-center gap-2 px-4 py-2.5 border-2 border-dashed border-[#059669]/40 text-[#059669] text-sm font-semibold rounded-xl hover:border-[#059669] hover:bg-[#ecfdf5] transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Alphabet Question
                    </button>
                    <button type="button" id="addPronunciationBtn"
                        class="flex items-center gap-2 px-4 py-2.5 border-2 border-dashed border-green-400/50 text-green-600 text-sm font-semibold rounded-xl hover:border-green-500 hover:bg-green-50 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Pronunciation Question
                    </button>
                    <button type="button" id="addVoiceBtn"
                        class="flex items-center gap-2 px-4 py-2.5 border-2 border-dashed border-red-400/50 text-red-600 text-sm font-semibold rounded-xl hover:border-red-500 hover:bg-red-50 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Voice Question
                    </button>
                </div>

                <p id="noQuestionsMsg" class="text-sm text-yellow-600 font-medium">
                    Add at least one question.
                </p>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3 pb-8">
                <a href="/admin/quiz/index.php"
                    class="px-6 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" id="submitBtn" disabled
                    class="px-6 py-2.5 bg-[#059669] hover:bg-[#047857] disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</main>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">

<script>
    window.QUIZ_INITIAL_DATA = <?php echo isset($initialJson) ? $initialJson : '[]'; ?>;
</script>
<script src="/js/admin/quiz-builder.js?v=<?php echo time(); ?>"></script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
