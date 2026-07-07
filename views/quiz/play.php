<!DOCTYPE html>
<html lang="bn" dir="ltr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo htmlspecialchars($quiz['title'] ?? 'কুইজ', ENT_QUOTES, 'UTF-8'); ?> — Rahen Azat Institute</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .arabic { font-family: 'Amiri', serif; }
        @keyframes fadeScale {
            from { opacity: 0; transform: scale(0.96); }
            to   { opacity: 1; transform: scale(1); }
        }
        .question-enter { animation: fadeScale .3s ease both; }
        @keyframes pulse-ring {
            0%   { transform: scale(1);   opacity: .8; }
            100% { transform: scale(1.5); opacity: 0;  }
        }
        .record-pulse::before {
            content: '';
            position: absolute; inset: -4px;
            border-radius: 9999px;
            background: #ef4444;
            animation: pulse-ring 1s ease-out infinite;
        }
        .waveform-bar {
            width: 4px; border-radius: 999px;
            background: #ef4444;
            animation: wave var(--d, .8s) ease-in-out infinite alternate;
        }
        @keyframes wave {
            from { height: 6px;  }
            to   { height: var(--h, 24px); }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#f0fdf4] via-white to-[#ecfeff]">

<?php
// PHP → JSON for Alpine.js (safe encoding)
$questionsJson  = json_encode($questions,         JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
$attemptToken   = htmlspecialchars($attempt['session_token'], ENT_QUOTES, 'UTF-8');
$participantName = htmlspecialchars($attempt['participant_name'], ENT_QUOTES, 'UTF-8');
?>

<div class="min-h-screen flex flex-col"
     x-data="quizPlayer()"
     x-init="init()">

    <!-- Top progress bar -->
    <div class="fixed top-0 left-0 right-0 z-50">
        <div class="h-1 bg-gray-200">
            <div class="h-1 bg-gradient-to-r from-[#059669] to-[#10b981] transition-all duration-500"
                 :style="`width: ${((currentIndex) / questions.length) * 100}%`"></div>
        </div>
    </div>

    <!-- Header -->
    <header class="pt-4 px-4 pb-2 flex items-center justify-between max-w-2xl mx-auto w-full">
        <div>
            <p class="text-xs font-medium text-[#64748b]"><?php echo htmlspecialchars($quiz['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="text-xs text-[#94a3b8] mt-0.5">👋 <?php echo $participantName; ?></p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-[#059669] bg-[#ecfdf5] px-3 py-1.5 rounded-full"
                  x-text="`${currentIndex + 1} / ${questions.length}`"></span>
        </div>
    </header>

    <!-- Main -->
    <main class="flex-1 flex items-center justify-center px-4 py-6">
    <div class="w-full max-w-2xl">

        <!-- MCQ SECTION -->
        <div x-show="!isVoiceStep && !quizDone" class="question-enter" x-cloak>
            <div class="bg-white/90 backdrop-blur-sm border border-white shadow-2xl shadow-emerald-100/40 rounded-3xl overflow-hidden">

                <!-- Question badge -->
                <div class="px-8 pt-8 pb-2">
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full"
                          :class="{
                              'bg-emerald-100 text-emerald-700': currentQuestion?.type === 'letter',
                              'bg-blue-100 text-blue-700': currentQuestion?.type === 'pronunciation'
                          }">
                        <span x-show="currentQuestion?.type === 'letter'">📖 বর্ণমালা</span>
                        <span x-show="currentQuestion?.type === 'pronunciation'">🔤 উচ্চারণ</span>
                    </span>
                </div>

                <!-- Question text (Arabic) -->
                <div class="px-8 py-6 text-center">
                    <div class="arabic text-6xl leading-relaxed text-[#0f172a] font-bold"
                         x-text="currentQuestion?.question_text"
                         dir="rtl"></div>
                    <p class="mt-3 text-sm text-[#64748b]">
                        <span x-show="currentQuestion?.type === 'letter'">নিচের অক্ষরটি কী?</span>
                        <span x-show="currentQuestion?.type === 'pronunciation'">এর সঠিক উচ্চারণ কোনটি?</span>
                    </p>
                </div>

                <!-- Options grid -->
                <div class="px-8 pb-8 grid grid-cols-2 gap-3">
                    <template x-for="opt in currentQuestion?.options ?? []" :key="opt.id">
                        <button
                            @click="selectOption(opt.id)"
                            :disabled="answered || submitting"
                            class="relative group py-4 px-5 rounded-2xl border-2 text-sm font-semibold transition-all duration-300 text-left"
                            :class="{
                                'border-gray-200 bg-white hover:border-[#059669] hover:bg-[#ecfdf5] text-[#374151]': !answered,
                                'border-[#059669] bg-[#ecfdf5] text-[#059669] scale-[1.02]': answered && opt.id === correctOptionId,
                                'border-red-300 bg-red-50 text-red-600': answered && opt.id === selectedOption && opt.id !== correctOptionId,
                                'border-gray-100 bg-[#f8fafc] text-[#94a3b8]': answered && opt.id !== correctOptionId && opt.id !== selectedOption,
                                'cursor-not-allowed': answered || submitting,
                                'cursor-pointer': !answered && !submitting
                            }">
                            <!-- Result icon -->
                            <span x-show="answered && opt.id === correctOptionId"
                                  class="absolute right-3 top-3 w-5 h-5 bg-[#059669] rounded-full flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span x-show="answered && opt.id === selectedOption && opt.id !== correctOptionId"
                                  class="absolute right-3 top-3 w-5 h-5 bg-red-400 rounded-full flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                            </span>
                            <span x-text="opt.option_text"></span>
                        </button>
                    </template>
                </div>

                <!-- Auto-advance indicator -->
                <div x-show="answered" x-cloak
                     class="mx-8 mb-8 flex items-center justify-center gap-2 text-xs text-[#64748b]">
                    <svg class="w-4 h-4 animate-spin text-[#059669]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    পরের প্রশ্নে যাচ্ছে...
                </div>
            </div>
        </div>

        <!-- VOICE RECORDING SECTION -->
        <div x-show="isVoiceStep && !quizDone" x-cloak class="question-enter">
            <div class="bg-white/90 backdrop-blur-sm border border-white shadow-2xl shadow-purple-100/40 rounded-3xl overflow-hidden">

                <div class="px-8 pt-8 pb-4 text-center">
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 bg-purple-100 text-purple-700 rounded-full">
                        🎙️ ভয়েস রেকর্ডিং
                    </span>
                    <h2 class="mt-4 text-base font-bold text-[#1e293b]">নিচের অনুচ্ছেদটি পড়ুন ও রেকর্ড করুন</h2>
                </div>

                <!-- Arabic paragraph -->
                <div class="mx-8 mb-6 p-6 bg-[#fafafa] border border-gray-100 rounded-2xl text-right leading-loose"
                     dir="rtl">
                    <p class="arabic text-2xl text-[#0f172a] leading-[2.5]"
                       x-text="currentQuestion?.question_text"></p>
                </div>

                <!-- Timer -->
                <div class="px-8 mb-5">
                    <div class="flex items-center justify-between text-sm font-medium text-[#64748b] mb-2">
                        <span>রেকর্ডিং সময়</span>
                        <span :class="recordingSeconds >= 50 ? 'text-red-500 font-bold' : 'text-[#059669]'"
                              x-text="`${recordingSeconds}s / 60s`"></span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-2 rounded-full transition-all duration-1000"
                             :class="recordingSeconds >= 50 ? 'bg-red-400' : 'bg-[#059669]'"
                             :style="`width: ${(recordingSeconds/60)*100}%`"></div>
                    </div>
                </div>

                <!-- Waveform (visible during recording) -->
                <div x-show="isRecording" x-cloak class="flex items-end justify-center gap-1.5 h-10 px-8 mb-4">
                    <template x-for="h in [14,20,28,22,18,32,26,18,24,30,16,28]">
                        <div class="waveform-bar flex-shrink-0"
                             :style="`--h: ${h}px; --d: ${0.4 + Math.random() * 0.6}s; animation-delay: ${Math.random() * 0.4}s`">
                        </div>
                    </template>
                </div>

                <!-- Controls -->
                <div class="px-8 pb-8 space-y-4">
                    <!-- Record / Stop -->
                    <div class="flex justify-center">
                        <button x-show="!isRecording && !audioBlob" @click="startRecording()"
                                class="relative flex items-center gap-3 px-8 py-4 bg-red-500 hover:bg-red-600 text-white font-bold rounded-2xl shadow-lg shadow-red-200 transition-all active:scale-95">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>
                            রেকর্ড শুরু করুন
                        </button>

                        <button x-show="isRecording" @click="stopRecording()"
                                class="relative record-pulse flex items-center gap-3 px-8 py-4 bg-red-500 text-white font-bold rounded-2xl shadow-lg shadow-red-200 transition-all">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9 10h6v4H9z"/></svg>
                            রেকর্ড থামান
                        </button>
                    </div>

                    <!-- Recorded preview + submit -->
                    <div x-show="audioBlob && !isRecording" x-cloak class="space-y-3">
                        <audio :src="audioUrl" controls class="w-full h-10 rounded-xl" preload="auto"></audio>

                        <div class="flex gap-3">
                            <button @click="resetRecording()"
                                    class="flex-1 py-3 border-2 border-gray-200 text-[#475569] text-sm font-semibold rounded-2xl hover:border-red-300 hover:text-red-500 hover:bg-red-50 transition-all">
                                আবার রেকর্ড করুন
                            </button>
                            <button @click="submitVoice()"
                                    :disabled="uploading"
                                    class="flex-1 py-3 bg-gradient-to-r from-[#059669] to-[#10b981] text-white text-sm font-bold rounded-2xl shadow-md shadow-emerald-200 disabled:opacity-60 transition-all active:scale-[0.98]">
                                <span x-show="!uploading">✓ জমা দিন</span>
                                <span x-show="uploading" class="flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                                    আপলোড হচ্ছে...
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Error -->
                    <p x-show="voiceError" x-text="voiceError"
                       class="text-center text-sm text-red-600 font-medium"></p>
                </div>
            </div>
        </div>

        <!-- DONE / UPLOADING STATE -->
        <div x-show="quizDone" x-cloak class="text-center py-12">
            <div class="w-16 h-16 bg-[#ecfdf5] rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-[#059669] animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-lg font-bold text-[#1e293b]">সম্পন্ন হয়েছে!</p>
            <p class="text-sm text-[#64748b] mt-1">রেজাল্ট পেজে নিয়ে যাওয়া হচ্ছে...</p>
        </div>

    </div>
    </main>
</div>

<script>
const QUIZ_TOKEN   = '<?php echo $attemptToken; ?>';
const CSRF_TOKEN   = '<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>';
const ALL_QUESTIONS = <?php echo $questionsJson; ?>;

function quizPlayer() {
    return {
        questions:       ALL_QUESTIONS,
        currentIndex:    0,
        selectedOption:  null,
        answered:        false,
        submitting:      false,
        correctOptionId: null,
        quizDone:        false,

        // Voice
        mediaRecorder:   null,
        isRecording:     false,
        audioBlob:       null,
        audioUrl:        null,
        recordingSeconds: 0,
        timerInterval:   null,
        uploading:       false,
        voiceError:      '',

        get currentQuestion() {
            return this.questions[this.currentIndex] ?? null;
        },
        get isVoiceStep() {
            return this.currentQuestion?.type === 'voice';
        },

        init() {
            // skip to first unanswered
        },

        async selectOption(optionId) {
            if (this.answered || this.submitting) return;
            this.submitting     = true;
            this.selectedOption = optionId;

            try {
                const res = await fetch('/quiz/submit_answer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token:  CSRF_TOKEN,
                        token:       QUIZ_TOKEN,
                        question_id: this.currentQuestion.id,
                        option_id:   optionId
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.correctOptionId = data.correct_option_id;
                    this.answered        = true;
                    // Auto-advance after 1.5s
                    setTimeout(() => this.nextQuestion(), 1500);
                }
            } catch(e) {
                console.error(e);
            } finally {
                this.submitting = false;
            }
        },

        nextQuestion() {
            this.answered       = false;
            this.selectedOption = null;
            this.correctOptionId = null;
            this.currentIndex++;
            // reached voice step or end?
        },

        // ── VOICE RECORDING ──────────────────────────────────
        async startRecording() {
            this.voiceError = '';
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                const chunks = [];
                this.mediaRecorder = new MediaRecorder(stream);
                this.mediaRecorder.ondataavailable = e => { if (e.data.size > 0) chunks.push(e.data); };
                this.mediaRecorder.onstop = () => {
                    this.audioBlob = new Blob(chunks, { type: 'audio/webm' });
                    this.audioUrl  = URL.createObjectURL(this.audioBlob);
                    stream.getTracks().forEach(t => t.stop());
                };
                this.mediaRecorder.start(250);
                this.isRecording = true;
                this.recordingSeconds = 0;

                // Countdown timer — auto stop at 60s
                this.timerInterval = setInterval(() => {
                    this.recordingSeconds++;
                    if (this.recordingSeconds >= 60) this.stopRecording();
                }, 1000);
            } catch(e) {
                this.voiceError = 'মাইক্রোফোন অ্যাক্সেস দিন এবং আবার চেষ্টা করুন।';
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
            this.audioBlob = null;
            if (this.audioUrl) URL.revokeObjectURL(this.audioUrl);
            this.audioUrl = null;
            this.recordingSeconds = 0;
            this.voiceError = '';
        },

        async submitVoice() {
            if (!this.audioBlob || this.uploading) return;
            this.uploading = true;
            this.voiceError = '';

            const form = new FormData();
            form.append('csrf_token', CSRF_TOKEN);
            form.append('token',      QUIZ_TOKEN);
            form.append('voice_file', this.audioBlob, 'voice.webm');

            try {
                const res  = await fetch('/quiz/upload_voice.php', { method: 'POST', body: form });
                const data = await res.json();
                if (data.success) {
                    this.quizDone = true;
                    setTimeout(() => { window.location.href = data.redirect; }, 1000);
                } else {
                    this.voiceError = data.message || 'আপলোড ব্যর্থ। আবার চেষ্টা করুন।';
                }
            } catch(e) {
                this.voiceError = 'নেটওয়ার্ক সমস্যা। পুনরায় চেষ্টা করুন।';
            } finally {
                this.uploading = false;
            }
        }
    };
}
</script>
</body>
</html>
