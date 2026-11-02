<?php
// views/quiz/guest_form.php
$isExam = ($quiz['type'] ?? 'quiz') === 'exam';
$duration = (int) ($quiz['duration_minutes'] ?? 0);
$totalMarks = (float) ($quiz['total_marks'] ?? 0);
$attemptLimit = (int) ($quiz['attempt_limit'] ?? 1);
$customFields = $quiz['custom_fields'] ?? [];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo htmlspecialchars($quiz['title'] ?? 'Assessment', ENT_QUOTES, 'UTF-8'); ?> — Rahe Nazat Institute</title>
    <meta name="description" content="Participate in the Quiz or Exam. Start with your information and WhatsApp number."/>
    
    <!-- Base Stylesheets -->
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/universal.css">
    
    <!-- Tailwind CSS (with CSP Nonce) -->
    <script src="https://cdn.tailwindcss.com" nonce="<?php echo csp_nonce(); ?>"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    
    <style nonce="<?php echo csp_nonce(); ?>">
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: radial-gradient(1000px circle at 50% 0%, rgba(16, 185, 129, 0.08), transparent 60%),
                        radial-gradient(800px circle at 100% 100%, rgba(5, 150, 105, 0.05), transparent 50%),
                        #f8fafc;
            min-height: 100vh;
        }
        .arabic {
            font-family: 'Amiri', serif;
            direction: rtl;
        }
        @keyframes formEntrance {
            from {
                opacity: 0;
                transform: translateY(18px) scale(0.99);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .animate-entrance {
            animation: formEntrance 0.35s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 text-slate-800 antialiased">

    <div class="w-full max-w-lg animate-entrance my-8">

        <!-- Institute Header Badge -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/90 backdrop-blur-sm border border-emerald-100 shadow-xs mb-3">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-bold text-emerald-800 tracking-wide uppercase">Rahe Nazat Institute</span>
                <span class="text-emerald-300">•</span>
                <span class="text-xs font-medium text-slate-500">Assessment Portal</span>
            </div>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-100 overflow-hidden relative">

            <!-- Card Banner / Quiz Header -->
            <div class="px-6 sm:px-8 pt-8 pb-6 text-center border-b border-slate-100 bg-gradient-to-b from-slate-50/70 to-white">
                
                <!-- Category Icon & Badge -->
                <div class="flex items-center justify-center gap-2 mb-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold <?php echo $isExam ? 'bg-indigo-50 text-indigo-700 border border-indigo-200/80' : 'bg-emerald-50 text-emerald-700 border border-emerald-200/80'; ?>">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <?php echo $isExam ? 'Official Exam' : 'Practice Quiz'; ?>
                    </span>
                </div>

                <!-- Quiz Title -->
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight leading-snug">
                    <?php echo htmlspecialchars($quiz['title'] ?? 'Assessment', ENT_QUOTES, 'UTF-8'); ?>
                </h1>

                <!-- Optional Description -->
                <?php if (!empty($quiz['description'])): ?>
                    <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed font-normal max-w-md mx-auto">
                        <?php echo htmlspecialchars($quiz['description'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                <?php endif; ?>
                
                <!-- Meta Info Badges -->
                <div class="mt-5 flex flex-wrap items-center justify-center gap-2.5 text-xs font-semibold">
                    <?php if ($duration > 0): ?>
                        <span class="inline-flex items-center gap-1.5 text-amber-800 bg-amber-50/90 px-3 py-1.5 rounded-xl border border-amber-200 shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Duration: <strong><?php echo $duration; ?> min</strong></span>
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 text-slate-700 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                            <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Unlimited Duration</span>
                        </span>
                    <?php endif; ?>

                    <?php if ($totalMarks > 0): ?>
                        <span class="inline-flex items-center gap-1.5 text-emerald-800 bg-emerald-50/90 px-3 py-1.5 rounded-xl border border-emerald-200 shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                            <span>Total Marks: <strong><?php echo number_format($totalMarks, 2); ?></strong></span>
                        </span>
                    <?php endif; ?>

                    <?php if ($attemptLimit > 0): ?>
                        <span class="inline-flex items-center gap-1.5 text-slate-700 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                            <span>Max Attempts: <strong><?php echo $attemptLimit; ?></strong></span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Error Banner -->
            <?php if (!empty($error)): ?>
                <div class="mx-6 sm:mx-8 mt-6 mb-2 flex items-start gap-3 p-4 bg-red-50 border border-red-200/90 rounded-2xl text-red-700 text-xs sm:text-sm font-medium">
                    <svg class="w-5 h-5 flex-shrink-0 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="flex-1">
                        <p class="font-bold text-red-800">Registration Notice</p>
                        <p class="mt-0.5 text-red-600"><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form method="POST" action="/quiz/play.php" id="candidateForm" class="p-6 sm:p-8 space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>"/>
                <input type="hidden" name="quiz_id" value="<?php echo (int)($quiz['id'] ?? 0); ?>"/>

                <!-- Full Name Field -->
                <div class="space-y-1.5">
                    <label for="participant_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </span>
                        <input type="text" name="participant_name" id="participant_name"
                               required maxlength="150"
                               placeholder="e.g. Abdullah Muhammad"
                               value="<?php echo htmlspecialchars($_POST['participant_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800
                                      placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500
                                      transition-all duration-150"/>
                    </div>
                </div>

                <!-- Gender Field -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Gender <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="gender" value="male" required
                                   <?php echo ($_POST['gender'] ?? '') === 'male' ? 'checked' : ''; ?>
                                   class="sr-only peer"/>
                            <div class="flex items-center justify-center gap-2 px-4 py-3 border border-slate-200
                                        rounded-xl text-xs sm:text-sm font-bold text-slate-600 bg-slate-50
                                        peer-checked:border-emerald-500 peer-checked:bg-emerald-50/80 peer-checked:text-emerald-800 peer-checked:shadow-xs
                                        hover:bg-slate-100 hover:border-slate-300 transition-all duration-150">
                                <svg class="w-4 h-4 text-slate-400 peer-checked:text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>Male</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="gender" value="female" required
                                   <?php echo ($_POST['gender'] ?? '') === 'female' ? 'checked' : ''; ?>
                                   class="sr-only peer"/>
                            <div class="flex items-center justify-center gap-2 px-4 py-3 border border-slate-200
                                        rounded-xl text-xs sm:text-sm font-bold text-slate-600 bg-slate-50
                                        peer-checked:border-emerald-500 peer-checked:bg-emerald-50/80 peer-checked:text-emerald-800 peer-checked:shadow-xs
                                        hover:bg-slate-100 hover:border-slate-300 transition-all duration-150">
                                <svg class="w-4 h-4 text-slate-400 peer-checked:text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>Female</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- WhatsApp Field -->
                <div class="space-y-1.5">
                    <label for="whatsapp_number" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        WhatsApp Number <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-emerald-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </span>
                        <input type="tel" name="whatsapp_number" id="whatsapp_number"
                               required maxlength="20"
                               placeholder="01XXXXXXXXX or +8801XXXXXXXXX"
                               value="<?php echo htmlspecialchars($_POST['whatsapp_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               pattern="^\+?[0-9]{10,15}$"
                               class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800
                                      placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500
                                      transition-all duration-150"/>
                    </div>
                    <p class="text-[11px] font-medium text-slate-500 flex items-center gap-1.5 pt-0.5">
                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <?php if ($attemptLimit > 0): ?>
                            Maximum <?php echo $attemptLimit; ?> attempt(s) allowed per WhatsApp number.
                        <?php else: ?>
                            Your score and evaluation report will be linked to this number.
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Custom Fields (Dynamic) -->
                <?php if (!empty($customFields) && is_array($customFields)): ?>
                    <?php foreach ($customFields as $cf): ?>
                        <?php 
                        $cfKey = htmlspecialchars($cf['key'] ?? '', ENT_QUOTES, 'UTF-8');
                        $cfLabel = htmlspecialchars($cf['label'] ?? $cfKey, ENT_QUOTES, 'UTF-8');
                        $cfType = ($cf['type'] ?? 'text') === 'number' ? 'number' : 'text';
                        $cfReq = !empty($cf['required']);
                        $oldVal = $_POST['custom_fields'][$cf['key']] ?? '';
                        ?>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                <?php echo $cfLabel; ?> <?php if ($cfReq): ?><span class="text-red-500">*</span><?php endif; ?>
                            </label>
                            <input type="<?php echo $cfType; ?>" name="custom_fields[<?php echo $cfKey; ?>]"
                                   <?php echo $cfReq ? 'required' : ''; ?>
                                   placeholder="Enter <?php echo $cfLabel; ?>"
                                   value="<?php echo htmlspecialchars($oldVal, ENT_QUOTES, 'UTF-8'); ?>"
                                   class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800
                                          placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500
                                          transition-all duration-150"/>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Email (Optional) -->
                <div class="space-y-1.5">
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Email Address <span class="text-slate-400 font-normal normal-case">(Optional)</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <input type="email" name="email" id="email"
                               maxlength="150"
                               placeholder="e.g. name@example.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800
                                      placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500
                                      transition-all duration-150"/>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" id="startBtn"
                            class="w-full py-3.5 px-6 bg-emerald-600 hover:bg-emerald-700
                                   text-white text-sm sm:text-base font-bold rounded-xl
                                   shadow-lg shadow-emerald-600/25
                                   transition-all duration-200
                                   focus:outline-none focus:ring-2 focus:ring-emerald-500/40
                                   active:scale-[0.98] cursor-pointer flex items-center justify-center gap-2">
                        <span><?php echo $isExam ? 'Start Official Exam' : 'Start Practice Quiz'; ?></span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
            </form>

            <!-- Card Bottom Trust Bar -->
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-center gap-2 text-[11px] font-semibold text-slate-400">
                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Encrypted Submission • Rahe Nazat Institute</span>
            </div>
        </div>

        <!-- Footer Note -->
        <p class="text-center text-xs font-semibold text-slate-400 mt-6 tracking-wider uppercase">
            Rahe Nazat Institute &copy; <?php echo date('Y'); ?>
        </p>
    </div>

    <!-- CSP Nonced Submit Button Handler -->
    <script nonce="<?php echo csp_nonce(); ?>">
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('candidateForm');
            const startBtn = document.getElementById('startBtn');
            if (form && startBtn) {
                form.addEventListener('submit', function () {
                    startBtn.disabled = true;
                    startBtn.classList.add('opacity-75', 'cursor-not-allowed');
                    startBtn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Starting Assessment...</span>
                    `;
                });
            }
        });
    </script>
</body>
</html>