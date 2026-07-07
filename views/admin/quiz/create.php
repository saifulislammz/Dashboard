<?php
/**
 * Admin Quiz Create View
 * Dynamic form with Alpine.js for adding letter/pronunciation/voice questions.
 */
$activeMenu = 'quiz_create';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';
?>

<main class="flex-1 overflow-y-auto bg-[#fafafa]">
<div class="max-w-[900px] mx-auto px-6 py-8 space-y-6" x-data="quizBuilder()">

    <!-- Page Header -->
    <div class="flex items-center gap-4">
        <a href="/admin/quiz/index.php" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-[#1e293b]">নতুন কুইজ তৈরি</h1>
            <p class="text-sm text-[#64748b] mt-0.5">আরবি বর্ণমালা, উচ্চারণ ও ভয়েস প্রশ্ন যোগ করুন।</p>
        </div>
    </div>

    <!-- Error -->
    <?php if (!empty($error)): ?>
    <div class="flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="/admin/quiz/create.php" id="quizForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">

        <!-- Quiz Info -->
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 space-y-5 mb-6">
            <h2 class="text-base font-semibold text-[#1e293b]">কুইজের তথ্য</h2>

            <div class="space-y-1">
                <label class="text-sm font-medium text-[#374151]">শিরোনাম <span class="text-red-500">*</span></label>
                <input type="text" name="title" required maxlength="255"
                       value="<?php echo htmlspecialchars($input['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="যেমন: আরবি বর্ণমালা কুইজ — পর্ব ১"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669]"/>
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium text-[#374151]">বিবরণ (ঐচ্ছিক)</label>
                <textarea name="description" rows="3" maxlength="5000"
                          placeholder="কুইজ সম্পর্কে সংক্ষিপ্ত বিবরণ..."
                          class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669] resize-none"><?php echo htmlspecialchars($input['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium text-[#374151]">স্ট্যাটাস</label>
                <select name="status" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669]">
                    <option value="active">● সক্রিয়</option>
                    <option value="inactive">○ নিষ্ক্রিয়</option>
                </select>
            </div>
        </div>

        <!-- Questions -->
        <div class="space-y-4 mb-6">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-[#1e293b]">প্রশ্নসমূহ</h2>
                <span class="text-sm text-[#64748b]" x-text="`${questions.length} টি প্রশ্ন`"></span>
            </div>

            <!-- Question list -->
            <template x-for="(q, idx) in questions" :key="q.uid">
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                    <!-- Question Header -->
                    <div class="flex items-center justify-between px-5 py-3 bg-[#f8fafc] border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 flex items-center justify-center bg-[#059669] text-white text-xs font-bold rounded-lg" x-text="idx + 1"></span>
                            <select :name="`questions[${idx}][type]`" x-model="q.type"
                                    class="text-sm font-semibold border border-gray-200 rounded-lg px-3 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#059669]/30">
                                <option value="letter">বর্ণমালা (Letter)</option>
                                <option value="pronunciation">উচ্চারণ (Pronunciation)</option>
                                <option value="voice">ভয়েস রেকর্ডিং</option>
                            </select>
                        </div>
                        <button type="button" @click="removeQuestion(idx)"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-5 space-y-4">
                        <!-- Arabic text -->
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-[#374151]">
                                <span x-show="q.type === 'voice'">আরবি প্যারাগ্রাফ</span>
                                <span x-show="q.type === 'letter'">আরবি অক্ষর</span>
                                <span x-show="q.type === 'pronunciation'">আরবি শব্দ/বাক্য</span>
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea :name="`questions[${idx}][question_text]`"
                                      x-model="q.question_text"
                                      :rows="q.type === 'voice' ? 4 : 2"
                                      required
                                      placeholder="আরবি লিখুন (RTL)..."
                                      dir="rtl"
                                      class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-lg focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669] resize-none font-arabic text-right"
                                      style="font-family: 'Amiri', 'Noto Naskh Arabic', serif;"></textarea>
                        </div>

                        <!-- MCQ Options (letter & pronunciation only) -->
                        <div x-show="q.type !== 'voice'" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-medium text-[#374151]">উত্তরের অপশন <span class="text-red-500">*</span></label>
                                <span class="text-xs text-[#64748b]">একটি সঠিক চিহ্নিত করুন</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <template x-for="(opt, oi) in q.options" :key="oi">
                                    <div class="flex items-center gap-2.5 p-3 border border-gray-200 rounded-xl"
                                         :class="opt.is_correct ? 'border-[#059669] bg-[#ecfdf5]' : 'bg-[#f8fafc]'">
                                        <input type="radio"
                                               :name="`questions[${idx}][correct]`"
                                               :value="oi"
                                               :checked="opt.is_correct"
                                               @change="setCorrect(idx, oi)"
                                               class="w-4 h-4 text-[#059669] focus:ring-[#059669]"/>
                                        <input type="text"
                                               :name="`questions[${idx}][options][${oi}][text]`"
                                               x-model="opt.text"
                                               :placeholder="`অপশন ${oi + 1}`"
                                               required
                                               class="flex-1 bg-transparent text-sm font-medium text-[#1e293b] focus:outline-none placeholder-gray-300"/>
                                        <input type="hidden"
                                               :name="`questions[${idx}][options][${oi}][is_correct]`"
                                               :value="opt.is_correct ? 1 : 0"/>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Voice info -->
                        <div x-show="q.type === 'voice'"
                             class="flex items-center gap-2 text-xs text-[#64748b] bg-[#f0fdf4] px-4 py-2.5 rounded-xl border border-[#d1fae5]">
                            <svg class="w-4 h-4 text-[#059669] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                            ইউজার এই প্যারাগ্রাফটি পড়ে সর্বোচ্চ ১ মিনিট ভয়েস রেকর্ড করবে।
                        </div>
                    </div>
                </div>
            </template>

            <!-- Add Question Buttons -->
            <div class="flex flex-wrap gap-3">
                <button type="button" @click="addQuestion('letter')"
                        class="flex items-center gap-2 px-4 py-2.5 border-2 border-dashed border-[#059669]/40 text-[#059669] text-sm font-semibold rounded-xl hover:border-[#059669] hover:bg-[#ecfdf5] transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    বর্ণমালা প্রশ্ন
                </button>
                <button type="button" @click="addQuestion('pronunciation')"
                        class="flex items-center gap-2 px-4 py-2.5 border-2 border-dashed border-blue-400/50 text-blue-600 text-sm font-semibold rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    উচ্চারণ প্রশ্ন
                </button>
                <button type="button" @click="addQuestion('voice')"
                        class="flex items-center gap-2 px-4 py-2.5 border-2 border-dashed border-purple-400/50 text-purple-600 text-sm font-semibold rounded-xl hover:border-purple-500 hover:bg-purple-50 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    ভয়েস প্রশ্ন
                </button>
            </div>

            <!-- No questions warning -->
            <p x-show="questions.length === 0" class="text-sm text-amber-600 font-medium">
                ⚠ কমপক্ষে একটি প্রশ্ন যোগ করুন।
            </p>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-3 pb-8">
            <a href="/admin/quiz/index.php"
               class="px-6 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                বাতিল
            </a>
            <button type="submit" :disabled="questions.length === 0"
                    class="px-6 py-2.5 bg-[#059669] hover:bg-[#047857] disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                কুইজ সংরক্ষণ করুন
            </button>
        </div>
    </form>
</div>
</main>

<!-- Google Fonts: Amiri (Arabic) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

<script>
function quizBuilder() {
    return {
        questions: [],
        uid: 0,

        addQuestion(type) {
            this.questions.push({
                uid: this.uid++,
                type: type,
                question_text: '',
                options: type !== 'voice' ? [
                    { text: '', is_correct: true  },
                    { text: '', is_correct: false },
                    { text: '', is_correct: false },
                    { text: '', is_correct: false },
                ] : []
            });
        },

        removeQuestion(idx) {
            if (confirm('এই প্রশ্নটি মুছে ফেলবেন?')) {
                this.questions.splice(idx, 1);
            }
        },

        setCorrect(qIdx, optIdx) {
            this.questions[qIdx].options.forEach((opt, i) => {
                opt.is_correct = i === optIdx;
            });
        }
    };
}
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
