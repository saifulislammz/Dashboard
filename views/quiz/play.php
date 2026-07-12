<?php
// views/quiz/play.php
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo htmlspecialchars($quiz['title'] ?? 'Quiz', ENT_QUOTES, 'UTF-8'); ?> — Rahen Azat Institute</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="/js/alpine.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f5f7f9; }
        .font-arabic { font-family: 'Amiri', serif; }
        
        .fade-enter-active, .fade-leave-active {
            transition: opacity 0.3s, transform 0.3s;
        }
        .fade-enter-from, .fade-leave-to {
            opacity: 0;
            transform: scale(0.98);
        }

        .waveform-bar {
            width: 4px;
            border-radius: 999px;
            background: #d1d5db; /* gray-300 by default */
            transition: background 0.3s;
        }
        .recording .waveform-bar {
            background: #10b981; /* emerald-500 */
            animation: wave var(--d, .8s) ease-in-out infinite alternate;
        }
        @keyframes wave {
            from { height: 4px;  }
            to   { height: var(--h, 24px); }
        }
        
        /* Pulse for mic button */
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .recording-pulse {
            animation: pulse-ring 2s infinite;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

<?php
$questionsJson  = json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
$attemptToken   = htmlspecialchars($attempt['session_token'], ENT_QUOTES, 'UTF-8');
?>

<div class="w-full max-w-4xl" x-data="quizPlayer()" x-init="init()">

    <!-- Quiz Card -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden relative transition-all" style="min-height: 500px;">
        
        <template x-if="!quizDone">
            <div class="p-8 sm:p-12 flex flex-col h-full">
                
                <!-- Header: Question Title & Counter -->
                <div class="flex justify-between items-start mb-10">
                    <h2 class="text-gray-500 font-semibold text-[15px]">
                        <span x-show="currentQuestion?.type === 'letter'">What is the letter below?</span>
                        <span x-show="currentQuestion?.type === 'pronunciation'">Which is the correct pronunciation?</span>
                        <span x-show="currentQuestion?.type === 'voice'">Read the article below and record your voice</span>
                    </h2>
                    <div class="bg-[#f1f5f9] text-[#64748b] text-[13px] font-bold px-4 py-1.5 rounded-full flex-shrink-0">
                        <span x-text="`${currentIndex + 1}/${questions.length}`"></span>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="flex-1 flex flex-col justify-center">

                    <!-- MCQ QUESTION (LETTER) -->
                    <template x-if="currentQuestion?.type === 'letter'">
                        <div class="w-full">
                            <!-- Large green box for letter -->
                            <div class="bg-[#eefcf2] border border-[#d1f4e0] rounded-3xl w-40 h-40 flex items-center justify-center mx-auto mb-10">
                                <span class="font-arabic text-6xl text-[#0f5132] leading-none" x-text="currentQuestion.question_text"></span>
                            </div>

                            <!-- 2x2 Options Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl mx-auto">
                                <template x-for="opt in currentQuestion.options" :key="opt.id">
                                    <button @click="selectOption(opt.id)"
                                            :disabled="answered"
                                            class="relative py-4 px-6 rounded-xl border border-gray-100 font-semibold text-[15px] text-[#374151] hover:border-[#10b981] hover:bg-[#f8fafc] transition-all flex items-center justify-center min-h-[64px]"
                                            :class="{
                                                'border-[#10b981] bg-[#eefcf2] text-[#0f5132]': answered && opt.id === correctOptionId,
                                                'border-red-200 bg-red-50 text-red-600': answered && opt.id === selectedOption && opt.id !== correctOptionId,
                                                'opacity-60 cursor-not-allowed': answered && opt.id !== correctOptionId && opt.id !== selectedOption
                                            }">
                                        <span x-text="opt.option_text"></span>
                                        <!-- Correct Checkmark -->
                                        <svg x-show="answered && opt.id === correctOptionId" class="absolute right-5 w-5 h-5 text-[#10b981]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- MCQ QUESTION (PRONUNCIATION) -->
                    <template x-if="currentQuestion?.type === 'pronunciation'">
                        <div class="w-full">
                            <!-- Wide green box for word -->
                            <div class="bg-[#eefcf2] border border-[#d1f4e0] rounded-3xl w-full py-12 flex items-center justify-center mb-10 max-w-3xl mx-auto">
                                <span class="font-arabic text-[5rem] text-[#0f5132] leading-none" x-text="currentQuestion.question_text"></span>
                            </div>

                            <!-- 2x2 Options Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-3xl mx-auto">
                                <template x-for="opt in currentQuestion.options" :key="opt.id">
                                    <button @click="selectOption(opt.id)"
                                            :disabled="answered"
                                            class="relative py-4 px-6 rounded-xl border border-gray-100 font-semibold text-[15px] text-[#374151] hover:border-[#10b981] hover:bg-[#f8fafc] transition-all flex items-center min-h-[64px] text-left"
                                            :class="{
                                                'border-2 border-[#10b981] bg-[#eefcf2] text-[#0f5132]': answered && opt.id === correctOptionId,
                                                'border-red-200 bg-red-50 text-red-600': answered && opt.id === selectedOption && opt.id !== correctOptionId,
                                                'opacity-60 cursor-not-allowed': answered && opt.id !== correctOptionId && opt.id !== selectedOption
                                            }">
                                        <span x-text="opt.option_text" class="flex-1"></span>
                                        <!-- Correct Checkmark -->
                                        <svg x-show="answered && opt.id === correctOptionId" class="w-5 h-5 text-[#10b981] ml-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- VOICE QUESTION -->
                    <template x-if="currentQuestion?.type === 'voice'">
                        <div class="w-full flex flex-col items-center">
                            <!-- Wide green box for paragraph -->
                            <div class="bg-[#eefcf2] border border-[#d1f4e0] rounded-3xl w-full p-10 mb-8 max-w-4xl mx-auto text-center">
                                <p class="font-arabic text-4xl text-[#0f5132] leading-[2]" x-text="currentQuestion.question_text" dir="rtl"></p>
                            </div>

                            <!-- Instructions -->
                            <p class="text-[#94a3b8] text-[15px] font-medium mb-8">Record your voice (Max 1 minute)</p>

                            <!-- Voice Recorder UI -->
                            <div class="flex items-center justify-center gap-6 mb-5 w-full" :class="{'recording': isRecording}">
                                
                                <!-- Left visualizer -->
                                <div class="flex items-center gap-2 h-10">
                                    <template x-for="h in [10, 16, 24, 14, 20, 12, 18, 22]">
                                        <div class="waveform-bar" :style="`--h: ${h}px; --d: ${0.3 + Math.random() * 0.5}s; animation-delay: ${Math.random() * 0.5}s; height: 4px`"></div>
                                    </template>
                                </div>

                                <!-- Mic Button -->
                                <button @click="toggleRecording()"
                                        class="w-[72px] h-[72px] rounded-full bg-[#10b981] text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105 active:scale-95"
                                        :class="{'recording-pulse bg-red-500': isRecording}">
                                    
                                    <!-- Mic icon -->
                                    <svg x-show="!isRecording" class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5-3c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                                    </svg>
                                    <!-- Stop icon -->
                                    <svg x-show="isRecording" class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M6 6h12v12H6z"/>
                                    </svg>
                                </button>

                                <!-- Right visualizer -->
                                <div class="flex items-center gap-2 h-10">
                                    <template x-for="h in [22, 18, 12, 20, 14, 24, 16, 10]">
                                        <div class="waveform-bar" :style="`--h: ${h}px; --d: ${0.3 + Math.random() * 0.5}s; animation-delay: ${Math.random() * 0.5}s; height: 4px`"></div>
                                    </template>
                                </div>
                            </div>

                            <!-- Timer -->
                            <div class="text-[#374151] font-semibold text-[15px] mb-2 font-mono tracking-wider"
                                 x-text="`00:${recordingSeconds.toString().padStart(2, '0')} / 01:00`"></div>
                            
                            <p class="text-[#cbd5e1] text-[13px] font-medium" x-show="!audioBlob && !isRecording">Click the microphone to start recording</p>
                            
                            <!-- Audio Preview & Reset -->
                            <div x-show="audioBlob && !isRecording" class="mt-6 flex flex-col items-center gap-4 w-full max-w-sm">
                                <audio :src="audioUrl" controls class="w-full h-10 rounded-full bg-gray-50"></audio>
                                <button @click="resetRecording()" class="text-sm text-[#94a3b8] hover:text-red-500 font-medium underline underline-offset-2">Record Again</button>
                            </div>
                            
                            <p class="text-red-500 text-sm mt-4 font-medium" x-show="voiceError" x-text="voiceError"></p>

                        </div>
                    </template>

                </div>

                <!-- Footer Actions -->
                <div class="mt-12 flex items-center justify-between">
                    <!-- Left: Skip (Removed) -->
                    <div></div>

                    <!-- Right: Next / Submit -->
                    <div>
                        <button x-show="answered && currentQuestion?.type !== 'voice'"
                                @click="nextQuestion()"
                                class="bg-[#0f5132] hover:bg-[#0a3622] text-white px-8 py-3 rounded-xl font-semibold text-[15px] transition-colors shadow-sm">
                            Next
                        </button>
                        
                        <button x-show="currentQuestion?.type === 'voice' && audioBlob && !isRecording"
                                @click="submitVoice()"
                                :disabled="uploading"
                                class="bg-[#0f5132] hover:bg-[#0a3622] text-white px-8 py-3 rounded-xl font-semibold text-[15px] transition-colors shadow-sm disabled:opacity-70 flex items-center gap-2">
                            <span x-text="uploading ? 'Submitting...' : 'Submit'"></span>
                        </button>
                    </div>
                </div>

            </div>
        </template>
        
        <!-- Loading / Done State -->
        <template x-if="quizDone">
            <div class="p-16 flex flex-col items-center justify-center h-full text-center min-h-[500px]">
                <div class="w-20 h-20 bg-[#eafbf1] rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-[#10b981] animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Completed!</h3>
                <p class="text-gray-500 font-medium">Taking you to the result page...</p>
            </div>
        </template>

    </div>
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

        init() {
            if(this.questions.length === 0) {
                this.quizDone = true;
            }
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
            if (this.currentIndex >= this.questions.length) {
                this.quizDone = true;
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
                    this.audioUrl  = URL.createObjectURL(this.audioBlob);
                    stream.getTracks().forEach(t => t.stop());
                };
                this.mediaRecorder.start(250);
                this.isRecording = true;
                this.recordingSeconds = 0;

                this.timerInterval = setInterval(() => {
                    this.recordingSeconds++;
                    if (this.recordingSeconds >= 60) this.stopRecording();
                }, 1000);
            } catch(e) {
                this.voiceError = 'Please allow microphone access and try again.';
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
                    setTimeout(() => { window.location.href = data.redirect; }, 500);
                } else {
                    this.voiceError = data.message || 'Upload failed. Please try again.';
                }
            } catch(e) {
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
