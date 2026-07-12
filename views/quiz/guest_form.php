<?php
// views/quiz/guest_form.php
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo htmlspecialchars($quiz['title'] ?? 'Quiz', ENT_QUOTES, 'UTF-8'); ?> — Rahen Azat Institute</title>
    <meta name="description" content="Participate in the Arabic Alphabet Quiz. Start with your name and WhatsApp number."/>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f5f7f9; }
        .arabic { font-family: 'Amiri', serif; direction: rtl; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-in-up { animation: fadeInUp 0.4s ease-out both; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md fade-in-up">

        <!-- Form Card -->
        <div class="bg-white rounded-[24px] shadow-sm border border-gray-100 overflow-hidden relative">

            <!-- Header -->
            <div class="px-8 pt-10 pb-6 text-center border-b border-gray-50">
                <div class="w-16 h-16 rounded-2xl bg-[#eefcf2] flex items-center justify-center mx-auto mb-4 border border-[#d1f4e0]">
                    <svg class="w-8 h-8 text-[#10b981]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-800 tracking-tight">
                    <?php echo htmlspecialchars($quiz['title'] ?? 'Arabic Quiz', ENT_QUOTES, 'UTF-8'); ?>
                </h1>
                <?php if (!empty($quiz['description'])): ?>
                <p class="mt-2 text-[13px] text-gray-500 leading-relaxed font-medium">
                    <?php echo htmlspecialchars($quiz['description'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <?php endif; ?>
                
                <div class="mt-5 flex items-center justify-center gap-4 text-xs font-semibold text-gray-400">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#10b981]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        MCQ + Voice
                    </span>
                    <span class="text-gray-200">|</span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#10b981]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Max 1 Minute
                    </span>
                </div>
            </div>

            <!-- Error message -->
            <?php if (!empty($error)): ?>
            <div class="mx-8 mt-6 mb-2 flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-100 rounded-xl text-red-600 text-[13px] font-medium">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="/quiz/play.php" class="p-8 pt-6 space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>"/>
                <input type="hidden" name="quiz_id" value="<?php echo (int)($quiz['id'] ?? 0); ?>"/>

                <!-- Name -->
                <div class="space-y-2">
                    <label class="text-[13px] font-bold text-gray-700">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="participant_name" id="participant_name"
                           required maxlength="150"
                           placeholder="Enter your full name"
                           value="<?php echo htmlspecialchars($_POST['participant_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl text-[15px] font-medium text-gray-800
                                  placeholder-gray-300 bg-gray-50 hover:bg-white
                                  focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#10b981]/20 focus:border-[#10b981]
                                  transition-all"/>
                </div>

                <!-- Gender -->
                <div class="space-y-3">
                    <label class="text-[13px] font-bold text-gray-700">
                        Gender <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="gender" value="male" required
                                   <?php echo ($_POST['gender'] ?? '') === 'male' ? 'checked' : ''; ?>
                                   class="sr-only peer"/>
                            <div class="flex items-center justify-center gap-2 px-4 py-3 border border-gray-200
                                        rounded-xl text-[14px] font-semibold text-gray-500 bg-gray-50
                                        peer-checked:border-[#10b981] peer-checked:bg-[#eefcf2] peer-checked:text-[#0f5132]
                                        hover:border-gray-300 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Male
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="gender" value="female" required
                                   <?php echo ($_POST['gender'] ?? '') === 'female' ? 'checked' : ''; ?>
                                   class="sr-only peer"/>
                            <div class="flex items-center justify-center gap-2 px-4 py-3 border border-gray-200
                                        rounded-xl text-[14px] font-semibold text-gray-500 bg-gray-50
                                        peer-checked:border-[#10b981] peer-checked:bg-[#eefcf2] peer-checked:text-[#0f5132]
                                        hover:border-gray-300 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Female
                            </div>
                        </label>
                    </div>
                </div>

                <!-- WhatsApp -->
                <div class="space-y-2">
                    <label class="text-[13px] font-bold text-gray-700">
                        WhatsApp Number <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[15px]">📱</span>
                        <input type="tel" name="whatsapp_number" id="whatsapp_number"
                               required maxlength="20"
                               placeholder="+8801XXXXXXXXX"
                               value="<?php echo htmlspecialchars($_POST['whatsapp_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               pattern="^\+?[0-9]{10,15}$"
                               class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-[15px] font-medium text-gray-800
                                      placeholder-gray-300 bg-gray-50 hover:bg-white
                                      focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#10b981]/20 focus:border-[#10b981]
                                      transition-all"/>
                    </div>
                    <p class="text-[11px] font-medium text-gray-400 pt-1">You can only participate once with this number.</p>
                </div>

                <!-- Email (optional) -->
                <div class="space-y-2">
                    <label class="text-[13px] font-bold text-gray-700">
                        Email <span class="text-gray-400 font-medium text-xs">(Optional)</span>
                    </label>
                    <input type="email" name="email" id="email"
                           maxlength="150"
                           placeholder="example@email.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl text-[15px] font-medium text-gray-800
                                  placeholder-gray-300 bg-gray-50 hover:bg-white
                                  focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#10b981]/20 focus:border-[#10b981]
                                  transition-all"/>
                </div>

                <!-- Submit -->
                <button type="submit" id="startBtn"
                        class="w-full py-4 mt-2 bg-[#0f5132] hover:bg-[#0a3622]
                               text-white text-[15px] font-bold rounded-xl
                               shadow-sm
                               transition-all duration-200
                               focus:outline-none focus:ring-2 focus:ring-[#0f5132]/30
                               active:scale-[0.98]">
                    Start Quiz
                </button>
            </form>
        </div>

        <!-- Footer note -->
        <p class="text-center text-xs font-semibold text-gray-400 mt-6 tracking-wide uppercase">
            Rahen Azat Institute
        </p>
    </div>

</body>
</html>
