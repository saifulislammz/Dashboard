<?php
// views/quiz/result.php
$a = $attempt ?? [];
$bd = $breakdown ?? [];

$correct = (int) ($a['correct_answers'] ?? 0);
$totalQuestions = (int) ($a['total_questions'] ?? 0);

$totalMarks = (float) (!empty($a['total_marks']) && $a['total_marks'] > 0 ? $a['total_marks'] : $totalQuestions);
$obtainedMarks = (float) ($a['obtained_marks'] ?? 0);
$passMarks = (float) ($a['pass_marks'] ?? 0);

$hasMarks = ($totalMarks > 0);
$scorePct = $totalMarks > 0 ? round(($obtainedMarks / $totalMarks) * 100, 1) : 0;
$isPassed = ($passMarks > 0) ? ($obtainedMarks >= $passMarks) : null;
$isExam = ($a['quiz_type'] ?? 'quiz') === 'exam';
$autoSubmitted = !empty($a['auto_submitted']);
$hasAdminVoice = !empty($a['admin_voice_file_path']);
$customFields = is_array($a['custom_fields_data'] ?? null) ? $a['custom_fields_data'] : [];

function fmtScore($num) {
    return (floor($num) == $num) ? (string) (int) $num : number_format($num, 1);
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Your Result — <?php echo htmlspecialchars($a['quiz_title'] ?? 'Rahe Nazat Institute', ENT_QUOTES, 'UTF-8'); ?></title>
    <!-- Base Stylesheets -->
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/universal.css">
    <script src="https://cdn.tailwindcss.com" nonce="<?php echo csp_nonce(); ?>"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <style nonce="<?php echo csp_nonce(); ?>">
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7f9;
        }

        .font-arabic {
            font-family: 'Amiri', serif;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-up {
            animation: slideUp .5s ease-out both;
        }
    </style>
</head>

<body class="min-h-screen py-10 px-4 flex items-center justify-center">

    <div class="w-full max-w-xl">

        <!-- Result Card -->
        <div class="bg-white rounded-[32px] shadow-sm border border-gray-100 slide-up relative pt-14 pb-8 px-6 sm:px-8 text-center mt-8">

            <!-- Top Badge Icon -->
            <div class="absolute -top-10 left-1/2 -translate-x-1/2">
                <div class="w-20 h-20 <?php echo ($isPassed === false) ? 'bg-rose-500 shadow-[0_8px_30px_rgba(244,63,94,0.35)]' : 'bg-[#10b981] shadow-[0_8px_30px_rgba(16,185,129,0.35)]'; ?> rounded-full flex items-center justify-center border-4 border-[#f5f7f9]">
                    <?php if ($isPassed === false): ?>
                        <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    <?php else: ?>
                        <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quiz / Exam Title & Type -->
            <div class="mb-4">
                <div class="inline-flex items-center gap-2 mb-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider <?php echo $isExam ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'; ?>">
                        <?php echo $isExam ? 'Exam' : 'Quiz'; ?>
                    </span>
                    <?php if (!empty($a['attempt_number']) && (int) $a['attempt_number'] > 0): ?>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                            Attempt #<?php echo (int) $a['attempt_number']; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">
                    <?php echo htmlspecialchars($a['quiz_title'] ?? 'Assessment', ENT_QUOTES, 'UTF-8'); ?>
                </h1>
                <p class="text-sm font-medium text-gray-500 mt-1">
                    Candidate: <strong class="text-gray-800"><?php echo htmlspecialchars($a['participant_name'] ?? 'Candidate', ENT_QUOTES, 'UTF-8'); ?></strong>
                </p>
            </div>

            <!-- Auto-submitted banner -->
            <?php if ($autoSubmitted): ?>
                <div class="mb-5 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-semibold flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Time limit expired. Answers submitted automatically (Auto-submitted)</span>
                </div>
            <?php endif; ?>

            <!-- Candidate Custom Fields Data (if any) -->
            <?php if (!empty($customFields)): ?>
                <div class="mb-6 flex flex-wrap items-center justify-center gap-2">
                    <?php foreach ($customFields as $k => $v): ?>
                        <?php if ($v !== '' && $v !== null): ?>
                            <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 px-3 py-1 rounded-lg text-xs font-medium">
                                <span class="text-gray-400 capitalize"><?php echo htmlspecialchars($k, ENT_QUOTES, 'UTF-8'); ?>:</span>
                                <strong class="text-gray-800"><?php echo htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Large Score Display -->
            <div class="mb-3">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Score</p>
                <div class="flex justify-center items-baseline gap-2 font-black tracking-tight <?php echo ($isPassed === false) ? 'text-rose-600' : 'text-[#0f5132]'; ?>"
                    style="font-size: 3.25rem; line-height: 1;">
                    <span><?php echo fmtScore($obtainedMarks); ?></span>
                    <span class="text-2xl text-gray-300 font-bold">/</span>
                    <span class="text-2xl text-gray-400 font-bold"><?php echo fmtScore($totalMarks); ?></span>
                </div>
                <p class="text-sm font-semibold text-gray-500 mt-1"><?php echo $scorePct; ?>%</p>
            </div>

            <!-- Pass / Fail Badge -->
            <?php if ($passMarks > 0): ?>
                <div class="mb-6">
                    <?php if ($isPassed): ?>
                        <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-1.5 rounded-full text-sm font-bold">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            PASSED • Pass Mark: <?php echo fmtScore($passMarks); ?>
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 bg-rose-50 text-rose-700 border border-rose-200 px-4 py-1.5 rounded-full text-sm font-bold">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            NOT PASSED • Pass Mark: <?php echo fmtScore($passMarks); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Breakdown by Question Category -->
            <div class="mb-8 text-left">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 text-center">Section Breakdown</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                    <?php
                    $knownTypes = [
                        'letter'          => 'Letter Recognition',
                        'pronunciation'   => 'Pronunciation',
                        'mcq'             => 'MCQ Questions',
                        'multiple_choice' => 'Multiple Choice',
                        'voice'           => 'Voice Recitation',
                    ];

                    foreach ($knownTypes as $tKey => $tTitle):
                        $bRow = $bd[$tKey] ?? null;
                        if (!$bRow && $tKey !== 'voice') continue;
                        if ($tKey === 'voice' && !$bRow && empty($a['voice_submitted'])) continue;

                        $bTotal = (int) ($bRow['total'] ?? ($tKey === 'voice' && !empty($a['voice_submitted']) ? 1 : 0));
                        $bCorrect = (int) ($bRow['correct'] ?? 0);
                        $bMarksAwarded = (float) ($bRow['marks_awarded'] ?? ($a['voice_marks'] ?? 0));
                        $bTotalMarks = (float) ($bRow['total_marks'] ?? 0);
                    ?>
                        <div class="bg-gray-50 border border-gray-100 rounded-xl p-3 flex flex-col justify-between">
                            <span class="text-gray-500 text-[11px] font-semibold truncate mb-1"><?php echo $tTitle; ?></span>
                            <div>
                                <div class="text-base font-extrabold text-gray-800">
                                    <?php if ($tKey === 'voice'): ?>
                                        <?php echo !empty($a['voice_submitted']) ? 'Submitted' : 'None'; ?>
                                    <?php else: ?>
                                        <?php echo $bCorrect; ?> / <?php echo $bTotal; ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($bTotalMarks > 0 || $bMarksAwarded > 0): ?>
                                    <div class="text-[11px] font-semibold text-emerald-600 mt-0.5">
                                        <?php echo fmtScore($bMarksAwarded); ?> / <?php echo fmtScore($bTotalMarks); ?> Marks
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Teacher's Voice Review Feedback Section -->
            <?php if ($hasAdminVoice): ?>
                <div class="mb-8 p-5 bg-emerald-50/60 border border-emerald-200 rounded-2xl text-left">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5-3c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z" />
                            </svg>
                        </span>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 leading-tight">Teacher's Audio Review</h4>
                            <p class="text-xs text-gray-500">Listen to the teacher's personalized feedback and pronunciation check</p>
                        </div>
                    </div>

                    <!-- Audio Player -->
                    <div class="bg-white p-2.5 rounded-xl border border-emerald-100 shadow-sm mb-3">
                        <audio controls class="w-full h-9" src="/quiz/listen_review.php?t=<?php echo urlencode($a['session_token'] ?? ''); ?>"></audio>
                    </div>

                    <?php if (!empty($a['voice_note'])): ?>
                        <div class="bg-white/80 p-3 rounded-xl border border-emerald-100 text-xs text-gray-700">
                            <strong class="text-emerald-900 block mb-0.5">Teacher's Note:</strong>
                            <p class="whitespace-pre-wrap"><?php echo htmlspecialchars($a['voice_note'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($a['voice_marks'] !== null): ?>
                        <div class="mt-2 text-right">
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-800 bg-emerald-100 px-2.5 py-1 rounded-lg">
                                Awarded Voice Score: <?php echo fmtScore($a['voice_marks']); ?> Marks
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif (!empty($a['voice_submitted'])): ?>
                <div class="mb-8 p-4 bg-blue-50 border border-blue-200 rounded-2xl text-left flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h4 class="text-xs font-bold text-blue-900">Voice Recording Submitted</h4>
                        <p class="text-xs text-blue-700 mt-0.5">
                            Your recitation/audio has been submitted successfully. Once the teacher completes the evaluation, you will be able to listen to the audio feedback and marks on this page.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action buttons -->
            <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                <button type="button" id="copyResultBtn"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 text-xs font-bold transition-colors inline-flex items-center justify-center gap-2 cursor-pointer active:scale-95 shadow-2xs">
                    <svg class="copy-res-icon w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                    </svg>
                    <span class="copy-res-text">Copy Result Link</span>
                </button>

                <a href="/quiz/play.php?id=<?php echo (int) ($a['quiz_id'] ?? 0); ?>"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-[#059669] hover:bg-[#047857] text-white text-xs font-bold transition-colors inline-flex items-center justify-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Retake Assessment</span>
                </a>
            </div>

        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-xs font-semibold text-gray-400 tracking-wider uppercase">
                Rahe Nazat Institute &copy; <?php echo date('Y'); ?>
            </p>
        </div>

    </div>

    <!-- Nonced Script for Result Page Interactions -->
    <script nonce="<?php echo csp_nonce(); ?>">
        document.addEventListener('DOMContentLoaded', function () {
            const copyBtn = document.getElementById('copyResultBtn');
            if (copyBtn) {
                copyBtn.addEventListener('click', function () {
                    const url = window.location.href;
                    const textSpan = copyBtn.querySelector('.copy-res-text');
                    const iconSvg = copyBtn.querySelector('.copy-res-icon');
                    const origText = textSpan ? textSpan.textContent : 'Copy Result Link';

                    const onCopySuccess = () => {
                        if (textSpan) textSpan.textContent = 'Copied!';
                        if (iconSvg) {
                            iconSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />';
                            iconSvg.classList.remove('text-gray-500');
                            iconSvg.classList.add('text-emerald-600');
                        }
                        copyBtn.classList.add('border-emerald-500', 'bg-emerald-50', 'text-emerald-700');

                        const toast = document.createElement('div');
                        toast.className = 'fixed bottom-6 right-6 z-50 flex items-center gap-2 bg-[#059669] text-white text-xs font-semibold px-4 py-2.5 rounded-xl shadow-xl border border-emerald-500 transition-all';
                        toast.innerHTML = `
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Result link copied to clipboard!</span>
                        `;
                        document.body.appendChild(toast);

                        setTimeout(() => {
                            if (textSpan) textSpan.textContent = origText;
                            if (iconSvg) {
                                iconSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />';
                                iconSvg.classList.add('text-gray-500');
                                iconSvg.classList.remove('text-emerald-600');
                            }
                            copyBtn.classList.remove('border-emerald-500', 'bg-emerald-50', 'text-emerald-700');
                            if (toast.parentNode) {
                                toast.style.opacity = '0';
                                toast.style.transition = 'opacity 0.3s ease';
                                setTimeout(() => toast.remove(), 300);
                            }
                        }, 2000);
                    };

                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(url).then(onCopySuccess).catch(() => {
                            fallbackCopy(url, onCopySuccess);
                        });
                    } else {
                        fallbackCopy(url, onCopySuccess);
                    }
                });
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
                    window.prompt('Copy link:', text);
                }
            }
        });
    </script>
</body>

</html>
