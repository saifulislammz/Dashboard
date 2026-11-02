<?php
// views/quiz/play.php
$questionsJson = json_encode($questions ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
$attemptToken = htmlspecialchars($attempt['session_token'] ?? '', ENT_QUOTES, 'UTF-8');
$quizType = htmlspecialchars($quiz['type'] ?? 'quiz', ENT_QUOTES, 'UTF-8');
$durationMinutes = (int) ($quiz['duration_minutes'] ?? 0);
$remainingSeconds = isset($attempt['remaining_seconds']) ? max(0, (int) $attempt['remaining_seconds']) : ($durationMinutes * 60);
$hasDuration = ($durationMinutes > 0);
$participantName = htmlspecialchars($attempt['participant_name'] ?? 'Candidate', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($quiz['title'] ?? 'Assessment', ENT_QUOTES, 'UTF-8'); ?> — Rahe Nazat Institute</title>
    <!-- Base Stylesheets -->
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/universal.css">
    <script src="https://cdn.tailwindcss.com" nonce="<?php echo csp_nonce(); ?>"></script>
    <script defer src="/js/alpine.min.js" nonce="<?php echo csp_nonce(); ?>"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Amiri:wght@400;700&display=swap"
        rel="stylesheet">
    <style nonce="<?php echo csp_nonce(); ?>">
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7f9;
        }

        .font-arabic {
            font-family: 'Amiri', serif;
        }

        .waveform-bar {
            width: 4px;
            border-radius: 999px;
            background: #d1d5db;
            transition: background 0.3s;
        }

        .recording .waveform-bar {
            background: #10b981;
            animation: wave var(--d, .8s) ease-in-out infinite alternate;
        }

        @keyframes wave {
            from {
                height: 4px;
            }

            to {
                height: var(--h, 24px);
            }
        }

        @keyframes pulse-ring {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
            }

            70% {
                box-shadow: 0 0 0 15px rgba(16, 185, 129, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        .recording-pulse {
            animation: pulse-ring 2s infinite;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-4xl" x-data="quizPlayer()" x-init="init()">

        <!-- Top Header: Quiz Title, Candidate & Countdown Timer -->
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 bg-white px-6 py-3.5 rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wider <?php echo $quizType === 'exam' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'; ?>">
                    <?php echo $quizType === 'exam' ? 'Exam' : 'Quiz'; ?>
                </span>
                <div>
                    <h1 class="text-sm font-bold text-gray-800 leading-tight">
                        <?php echo htmlspecialchars($quiz['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </h1>
                    <p class="text-xs text-gray-500 font-medium">Candidate: <?php echo $participantName; ?></p>
                </div>
            </div>

            <!-- Real-time Countdown Timer (Only if duration is configured) -->
            <template x-if="hasDuration">
                <div class="flex items-center gap-2 px-3.5 py-1.5 rounded-xl border font-mono font-bold text-sm tracking-wide transition-colors"
                    :class="timerClass">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-text="formattedTime"></span>
                    <span class="text-[11px] font-sans font-semibold uppercase opacity-75">Left</span>
                </div>
            </template>
        </div>

        <!-- Quiz / Exam Card -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden relative transition-all"
            style="min-height: 520px;">

            <!-- Main Question Flow -->
            <template x-if="!quizDone && !autoSubmitting">
                <div class="p-8 sm:p-12 flex flex-col h-full justify-between" style="min-height: 520px;">

                    <!-- Question Header & Counter -->
                    <div class="flex justify-between items-start mb-8 gap-4">
                        <div>
                            <h2 class="text-gray-500 font-semibold text-[15px]">
                                <span x-show="currentQuestion?.type === 'letter'">What is the letter below?</span>
                                <span x-show="currentQuestion?.type === 'pronunciation'">Which is the correct pronunciation?</span>
                                <span x-show="currentQuestion?.type === 'mcq' || currentQuestion?.type === 'multiple_choice'">Choose the correct answer:</span>
                                <span x-show="currentQuestion?.type === 'voice'">Read the passage below and record your voice:</span>
                            </h2>
                            <template x-if="currentQuestion?.marks && parseFloat(currentQuestion.marks) > 0">
                                <span class="inline-flex items-center mt-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                    <span x-text="`${currentQuestion.marks} Marks`"></span>
                                </span>
                            </template>
                        </div>
                        <div class="bg-[#f1f5f9] text-[#64748b] text-[13px] font-bold px-4 py-1.5 rounded-full flex-shrink-0">
                            <span x-text="`${currentIndex + 1}/${questions.length}`"></span>
                        </div>
                    </div>

                    <!-- Question Body Area -->
                    <div class="flex-1 flex flex-col justify-center">

                        <!-- 1. LETTER QUESTION -->
                        <template x-if="currentQuestion?.type === 'letter'">
                            <div class="w-full">
                                <div class="bg-[#eefcf2] border border-[#d1f4e0] rounded-3xl w-40 h-40 flex items-center justify-center mx-auto mb-10 shadow-sm">
                                    <span class="font-arabic text-6xl text-[#0f5132] leading-none"
                                        x-text="currentQuestion.question_text"></span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl mx-auto">
                                    <template x-for="(opt, idx) in currentQuestion.options" :key="opt.id">
                                        <button @click="selectOption(opt.id)" :disabled="answered || submitting"
                                            class="relative py-4 px-6 rounded-xl border border-gray-100 font-semibold text-[15px] text-[#374151] hover:border-[#10b981] hover:bg-[#f8fafc] transition-all flex items-center justify-center min-h-[64px]"
                                            :class="{
                                                'border-[#10b981] bg-[#eefcf2] text-[#0f5132] font-bold ring-2 ring-[#10b981]/20': answered && opt.id === correctOptionId,
                                                'border-red-200 bg-red-50 text-red-600': answered && opt.id === selectedOption && opt.id !== correctOptionId,
                                                'opacity-60 cursor-not-allowed': answered && opt.id !== correctOptionId && opt.id !== selectedOption
                                            }">
                                            <span x-text="opt.option_text"></span>
                                            <svg x-show="answered && opt.id === correctOptionId"
                                                class="absolute right-5 w-5 h-5 text-[#10b981]" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- 2. PRONUNCIATION QUESTION -->
                        <template x-if="currentQuestion?.type === 'pronunciation'">
                            <div class="w-full">
                                <div class="bg-[#eefcf2] border border-[#d1f4e0] rounded-3xl w-full py-10 flex items-center justify-center mb-10 max-w-3xl mx-auto shadow-sm">
                                    <span class="font-arabic text-[4.5rem] text-[#0f5132] leading-none"
                                        x-text="currentQuestion.question_text"></span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-3xl mx-auto">
                                    <template x-for="opt in currentQuestion.options" :key="opt.id">
                                        <button @click="selectOption(opt.id)" :disabled="answered || submitting"
                                            class="relative py-4 px-6 rounded-xl border border-gray-100 font-semibold text-[15px] text-[#374151] hover:border-[#10b981] hover:bg-[#f8fafc] transition-all flex items-center min-h-[64px] text-left"
                                            :class="{
                                                'border-2 border-[#10b981] bg-[#eefcf2] text-[#0f5132] font-bold': answered && opt.id === correctOptionId,
                                                'border-red-200 bg-red-50 text-red-600': answered && opt.id === selectedOption && opt.id !== correctOptionId,
                                                'opacity-60 cursor-not-allowed': answered && opt.id !== correctOptionId && opt.id !== selectedOption
                                            }">
                                            <span x-text="opt.option_text" class="flex-1"></span>
                                            <svg x-show="answered && opt.id === correctOptionId"
                                                class="w-5 h-5 text-[#10b981] ml-3 flex-shrink-0" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- 3. GENERAL MCQ & MULTIPLE CHOICE -->
                        <template x-if="currentQuestion?.type === 'mcq' || currentQuestion?.type === 'multiple_choice'">
                            <div class="w-full max-w-3xl mx-auto">
                                <div class="bg-gray-50 border border-gray-200/80 rounded-2xl p-6 sm:p-8 mb-8 text-center shadow-inner">
                                    <h3 class="text-xl sm:text-2xl font-bold text-gray-800 leading-relaxed whitespace-pre-wrap"
                                        x-text="currentQuestion.question_text"></h3>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <template x-for="(opt, idx) in currentQuestion.options" :key="opt.id">
                                        <button @click="selectOption(opt.id)" :disabled="answered || submitting"
                                            class="relative py-4 px-5 rounded-xl border border-gray-200 font-medium text-[15px] text-gray-800 hover:border-[#10b981] hover:bg-[#f8fafc] transition-all flex items-center min-h-[60px] text-left gap-3"
                                            :class="{
                                                'border-2 border-[#10b981] bg-[#eefcf2] text-[#0f5132] font-bold shadow-sm': answered && opt.id === correctOptionId,
                                                'border-red-200 bg-red-50 text-red-600': answered && opt.id === selectedOption && opt.id !== correctOptionId,
                                                'opacity-60 cursor-not-allowed': answered && opt.id !== correctOptionId && opt.id !== selectedOption
                                            }">
                                            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"
                                                :class="{
                                                    'bg-[#10b981] text-white': answered && opt.id === correctOptionId,
                                                    'bg-red-500 text-white': answered && opt.id === selectedOption && opt.id !== correctOptionId,
                                                    'bg-gray-200 text-gray-600': !(answered && (opt.id === correctOptionId || opt.id === selectedOption))
                                                }"
                                                x-text="String.fromCharCode(65 + idx)"></span>
                                            <span x-text="opt.option_text" class="flex-1"></span>
                                            <svg x-show="answered && opt.id === correctOptionId"
                                                class="w-5 h-5 text-[#10b981] flex-shrink-0" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- 4. VOICE QUESTION -->
                        <template x-if="currentQuestion?.type === 'voice'">
                            <div class="w-full flex flex-col items-center">
                                <div class="bg-[#eefcf2] border border-[#d1f4e0] rounded-3xl w-full p-8 sm:p-10 mb-8 max-w-4xl mx-auto text-center shadow-sm">
                                    <p class="font-arabic text-3xl sm:text-4xl text-[#0f5132] leading-[2.2]"
                                        x-text="currentQuestion.question_text" dir="rtl"></p>
                                </div>

                                <p class="text-[#94a3b8] text-[15px] font-medium mb-6">
                                    Record your recitation/answer (Max 1 minute)
                                </p>

                                <div class="flex items-center justify-center gap-6 mb-4 w-full"
                                    :class="{'recording': isRecording}">
                                    <!-- Waveform visualizer left -->
                                    <div class="flex items-center gap-2 h-10">
                                        <template x-for="h in [10, 16, 24, 14, 20, 12, 18, 22]">
                                            <div class="waveform-bar"
                                                :style="`--h: ${h}px; --d: ${0.3 + Math.random() * 0.5}s; animation-delay: ${Math.random() * 0.5}s; height: 4px`">
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Mic Button -->
                                    <button @click="toggleRecording()"
                                        class="w-[72px] h-[72px] rounded-full bg-[#10b981] text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105 active:scale-95"
                                        :class="{'recording-pulse bg-red-500': isRecording}">
                                        <svg x-show="!isRecording" class="w-8 h-8" fill="currentColor"
                                            viewBox="0 0 24 24">
                                            <path
                                                d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5-3c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z" />
                                        </svg>
                                        <svg x-show="isRecording" class="w-8 h-8" fill="currentColor"
                                            viewBox="0 0 24 24">
                                            <path d="M6 6h12v12H6z" />
                                        </svg>
                                    </button>

                                    <!-- Waveform visualizer right -->
                                    <div class="flex items-center gap-2 h-10">
                                        <template x-for="h in [22, 18, 12, 20, 14, 24, 16, 10]">
                                            <div class="waveform-bar"
                                                :style="`--h: ${h}px; --d: ${0.3 + Math.random() * 0.5}s; animation-delay: ${Math.random() * 0.5}s; height: 4px`">
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="text-[#374151] font-semibold text-[15px] mb-2 font-mono tracking-wider"
                                    x-text="`00:${recordingSeconds.toString().padStart(2, '0')} / 01:00`"></div>

                                <p class="text-[#cbd5e1] text-[13px] font-medium" x-show="!audioBlob && !isRecording">
                                    Click the microphone button to start recording
                                </p>

                                <div x-show="audioBlob && !isRecording"
                                    class="mt-4 flex flex-col items-center gap-3 w-full max-w-sm">
                                    <audio :src="audioUrl" controls class="w-full h-10 rounded-full bg-gray-50"></audio>
                                    <button @click="resetRecording()"
                                        class="text-sm text-[#94a3b8] hover:text-red-500 font-medium underline underline-offset-2">
                                        Record Again
                                    </button>
                                </div>

                                <p class="text-red-500 text-sm mt-3 font-medium" x-show="voiceError"
                                    x-text="voiceError"></p>
                            </div>
                        </template>

                    </div>

                    <!-- Footer Actions -->
                    <div class="mt-10 pt-6 border-t border-gray-100 flex items-center justify-between">
                        <div>
                            <!-- Hint or status indicator -->
                            <span class="text-xs text-gray-400 font-medium" x-show="submitting">Saving answer...</span>
                        </div>

                        <div>
                            <button x-show="answered && currentQuestion?.type !== 'voice'" @click="nextQuestion()"
                                class="bg-[#0f5132] hover:bg-[#0a3622] text-white px-8 py-3 rounded-xl font-semibold text-[15px] transition-all shadow-sm flex items-center gap-2 hover:shadow">
                                <span x-text="currentIndex === questions.length - 1 ? 'Finish' : 'Next Question'"></span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            <button x-show="currentQuestion?.type === 'voice' && audioBlob && !isRecording"
                                @click="submitVoice()" :disabled="uploading"
                                class="bg-[#0f5132] hover:bg-[#0a3622] text-white px-8 py-3 rounded-xl font-semibold text-[15px] transition-all shadow-sm disabled:opacity-70 flex items-center gap-2">
                                <span x-text="uploading ? 'Submitting Recitation...' : 'Submit & Finish'"></span>
                            </button>
                        </div>
                    </div>

                </div>
            </template>

            <!-- Auto-Submit State / Overlay -->
            <template x-if="autoSubmitting">
                <div class="p-16 flex flex-col items-center justify-center h-full text-center" style="min-height: 520px;">
                    <div class="w-20 h-20 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mb-6 animate-pulse">
                        <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Time is up!</h3>
                    <p class="text-gray-500 font-medium max-w-sm mb-4">
                        The allocated exam time has ended. Your answers are being submitted automatically...
                    </p>
                    <div class="inline-flex items-center gap-2 text-xs font-semibold text-gray-400">
                        <svg class="w-4 h-4 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Redirecting to results page...
                    </div>
                </div>
            </template>

            <!-- Regular Done State -->
            <template x-if="quizDone && !autoSubmitting">
                <div class="p-16 flex flex-col items-center justify-center h-full text-center" style="min-height: 520px;">
                    <div class="w-20 h-20 bg-[#eafbf1] rounded-full flex items-center justify-center mb-6">
                        <svg class="w-10 h-10 text-[#10b981]" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Completed!</h3>
                    <p class="text-gray-500 font-medium">Taking you to the result page...</p>
                </div>
            </template>

        </div>
    </div>

    <script nonce="<?php echo csp_nonce(); ?>">
        const QUIZ_TOKEN = '<?php echo $attemptToken; ?>';
        const CSRF_TOKEN = '<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>';
        const ALL_QUESTIONS = <?php echo $questionsJson; ?>;
        const HAS_DURATION = <?php echo $hasDuration ? 'true' : 'false'; ?>;
        const INITIAL_REMAINING = <?php echo (int) $remainingSeconds; ?>;

        function quizPlayer() {
            return {
                questions: ALL_QUESTIONS,
                currentIndex: 0,
                selectedOption: null,
                answered: false,
                submitting: false,
                correctOptionId: null,
                quizDone: false,
                autoSubmitting: false,

                // Timer
                hasDuration: HAS_DURATION,
                remainingSeconds: INITIAL_REMAINING,
                countdownTimer: null,

                // Voice Recording
                mediaRecorder: null,
                isRecording: false,
                audioBlob: null,
                audioUrl: null,
                recordingSeconds: 0,
                voiceTimerInterval: null,
                uploading: false,
                voiceError: '',

                get currentQuestion() {
                    return this.questions[this.currentIndex] ?? null;
                },

                get formattedTime() {
                    const total = Math.max(0, this.remainingSeconds);
                    const hrs = Math.floor(total / 3600);
                    const mins = Math.floor((total % 3600) / 60);
                    const secs = total % 60;
                    if (hrs > 0) {
                        return `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                    }
                    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                },

                get timerClass() {
                    if (this.remainingSeconds <= 60) {
                        return 'bg-red-50 text-red-600 border-red-200 animate-pulse';
                    }
                    if (this.remainingSeconds <= 300) {
                        return 'bg-amber-50 text-amber-700 border-amber-200';
                    }
                    return 'bg-gray-50 text-gray-700 border-gray-200';
                },

                init() {
                    if (this.questions.length === 0) {
                        this.quizDone = true;
                        window.location.href = '/quiz/result.php?t=' + encodeURIComponent(QUIZ_TOKEN);
                        return;
                    }

                    if (this.hasDuration) {
                        if (this.remainingSeconds <= 0) {
                            this.triggerAutoSubmit();
                        } else {
                            this.startCountdown();
                        }
                    }

                    // Prevent accidental navigation during active attempt
                    window.addEventListener('beforeunload', (e) => {
                        if (!this.quizDone && !this.autoSubmitting) {
                            e.preventDefault();
                            e.returnValue = '';
                        }
                    });
                },

                startCountdown() {
                    this.countdownTimer = setInterval(() => {
                        if (this.remainingSeconds > 0) {
                            this.remainingSeconds--;
                            if (this.remainingSeconds <= 0) {
                                clearInterval(this.countdownTimer);
                                this.triggerAutoSubmit();
                            }
                        }
                    }, 1000);
                },

                async triggerAutoSubmit() {
                    if (this.autoSubmitting) return;
                    this.autoSubmitting = true;
                    if (this.countdownTimer) clearInterval(this.countdownTimer);
                    if (this.isRecording) this.stopRecording();

                    try {
                        const res = await fetch('/quiz/auto_submit.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                csrf_token: CSRF_TOKEN,
                                token: QUIZ_TOKEN
                            })
                        });
                        const data = await res.json();
                        setTimeout(() => {
                            window.location.href = data.redirect || ('/quiz/result.php?t=' + encodeURIComponent(QUIZ_TOKEN));
                        }, 800);
                    } catch (e) {
                        window.location.href = '/quiz/result.php?t=' + encodeURIComponent(QUIZ_TOKEN);
                    }
                },

                async selectOption(optionId) {
                    if (this.answered || this.submitting || this.autoSubmitting) return;
                    this.submitting = true;
                    this.selectedOption = optionId;

                    try {
                        const res = await fetch('/quiz/submit_answer.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                csrf_token: CSRF_TOKEN,
                                token: QUIZ_TOKEN,
                                question_id: this.currentQuestion.id,
                                option_id: optionId
                            })
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.correctOptionId = data.correct_option_id;
                            this.answered = true;
                        } else if (data.message && data.message.toLowerCase().includes('expired')) {
                            this.triggerAutoSubmit();
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.submitting = false;
                    }
                },

                nextQuestion() {
                    this.answered = false;
                    this.selectedOption = null;
                    this.correctOptionId = null;
                    this.currentIndex++;
                    if (this.currentIndex >= this.questions.length) {
                        this.quizDone = true;
                        if (this.countdownTimer) clearInterval(this.countdownTimer);
                        window.location.href = '/quiz/result.php?t=' + encodeURIComponent(QUIZ_TOKEN);
                    }
                },

                // ── VOICE RECORDING ──────────────────────────────────
                async toggleRecording() {
                    if (this.isRecording) {
                        this.stopRecording();
                    } else {
                        this.startRecording();
                    }
                },

                async startRecording() {
                    this.voiceError = '';
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

                        this.voiceTimerInterval = setInterval(() => {
                            this.recordingSeconds++;
                            if (this.recordingSeconds >= 60) this.stopRecording();
                        }, 1000);
                    } catch (e) {
                        this.voiceError = 'Please allow microphone access and try again.';
                    }
                },

                stopRecording() {
                    if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                        this.mediaRecorder.stop();
                    }
                    if (this.voiceTimerInterval) clearInterval(this.voiceTimerInterval);
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
                    if (!this.audioBlob || this.uploading || this.autoSubmitting) return;
                    this.uploading = true;
                    this.voiceError = '';

                    const form = new FormData();
                    form.append('csrf_token', CSRF_TOKEN);
                    form.append('token', QUIZ_TOKEN);
                    form.append('voice_file', this.audioBlob, 'voice.webm');

                    try {
                        const res = await fetch('/quiz/upload_voice.php', { method: 'POST', body: form });
                        const data = await res.json();
                        if (data.success) {
                            this.quizDone = true;
                            if (this.countdownTimer) clearInterval(this.countdownTimer);
                            setTimeout(() => { window.location.href = data.redirect; }, 400);
                        } else {
                            this.voiceError = data.message || 'Upload failed. Please try again.';
                        }
                    } catch (e) {
                        this.voiceError = 'Network error. Please try again.';
                    } finally {
                        this.uploading = false;
                    }
                }
            };
        }
    </script>
</body>

</html>
