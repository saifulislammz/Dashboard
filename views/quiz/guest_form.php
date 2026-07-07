<!DOCTYPE html>
<html lang="bn" dir="ltr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo htmlspecialchars($quiz['title'] ?? 'কুইজ', ENT_QUOTES, 'UTF-8'); ?> — Rahen Azat Institute</title>
    <meta name="description" content="আরবি বর্ণমালা কুইজে অংশগ্রহণ করুন। নাম ও WhatsApp নম্বর দিয়ে শুরু করুন।"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .arabic { font-family: 'Amiri', serif; direction: rtl; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-in-up { animation: fadeInUp .5s ease both; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#f0fdf4] via-white to-[#ecfeff] flex items-center justify-center p-4">

    <div class="w-full max-w-md fade-in-up">

        <!-- Card -->
        <div class="bg-white/80 backdrop-blur-sm border border-white shadow-2xl shadow-emerald-100/50 rounded-3xl overflow-hidden">

            <!-- Top accent -->
            <div class="h-1.5 bg-gradient-to-r from-[#059669] via-[#10b981] to-[#34d399]"></div>

            <!-- Header -->
            <div class="px-8 pt-8 pb-6 text-center">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#059669] to-[#10b981] flex items-center justify-center mx-auto mb-4 shadow-lg shadow-emerald-200">
                    <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-[#0f172a] tracking-tight">
                    <?php echo htmlspecialchars($quiz['title'] ?? 'আরবি কুইজ', ENT_QUOTES, 'UTF-8'); ?>
                </h1>
                <?php if (!empty($quiz['description'])): ?>
                <p class="mt-2 text-sm text-[#64748b] leading-relaxed">
                    <?php echo htmlspecialchars($quiz['description'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <?php endif; ?>
                <div class="mt-4 flex items-center justify-center gap-4 text-xs text-[#64748b]">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-[#059669]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        MCQ + ভয়েস
                    </span>
                    <span class="text-gray-300">|</span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-[#059669]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        সর্বোচ্চ ১ মিনিট
                    </span>
                </div>
            </div>

            <!-- Error -->
            <?php if (!empty($error)): ?>
            <div class="mx-8 mb-4 flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-2xl text-red-700 text-sm">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="/quiz/play.php" class="px-8 pb-8 space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>"/>
                <input type="hidden" name="quiz_id" value="<?php echo (int)($quiz['id'] ?? 0); ?>"/>

                <!-- Name -->
                <div class="space-y-1.5">
                    <label class="text-sm font-semibold text-[#374151]">
                        পূর্ণ নাম <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="participant_name" id="participant_name"
                           required maxlength="150"
                           placeholder="আপনার পূর্ণ নাম লিখুন"
                           value="<?php echo htmlspecialchars($_POST['participant_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           class="w-full px-4 py-3 border border-gray-200 rounded-2xl text-sm font-medium text-[#1e293b]
                                  placeholder-gray-400 bg-white
                                  focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669]
                                  transition-all"/>
                </div>

                <!-- Gender -->
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-[#374151]">
                        লিঙ্গ <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="gender" value="male" required
                                   <?php echo ($_POST['gender'] ?? '') === 'male' ? 'checked' : ''; ?>
                                   class="sr-only peer"/>
                            <div class="flex items-center justify-center gap-2 px-4 py-3 border-2 border-gray-200
                                        rounded-2xl text-sm font-semibold text-[#475569]
                                        peer-checked:border-[#059669] peer-checked:bg-[#ecfdf5] peer-checked:text-[#059669]
                                        hover:border-gray-300 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                পুরুষ
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="gender" value="female" required
                                   <?php echo ($_POST['gender'] ?? '') === 'female' ? 'checked' : ''; ?>
                                   class="sr-only peer"/>
                            <div class="flex items-center justify-center gap-2 px-4 py-3 border-2 border-gray-200
                                        rounded-2xl text-sm font-semibold text-[#475569]
                                        peer-checked:border-[#059669] peer-checked:bg-[#ecfdf5] peer-checked:text-[#059669]
                                        hover:border-gray-300 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                মহিলা
                            </div>
                        </label>
                    </div>
                </div>

                <!-- WhatsApp -->
                <div class="space-y-1.5">
                    <label class="text-sm font-semibold text-[#374151]">
                        WhatsApp নম্বর <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-[#059669]">
                            📱
                        </span>
                        <input type="tel" name="whatsapp_number" id="whatsapp_number"
                               required maxlength="20"
                               placeholder="+8801XXXXXXXXX"
                               value="<?php echo htmlspecialchars($_POST['whatsapp_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               pattern="^\+?[0-9]{10,15}$"
                               class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-2xl text-sm font-medium text-[#1e293b]
                                      placeholder-gray-400 bg-white
                                      focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669]
                                      transition-all"/>
                    </div>
                    <p class="text-xs text-[#94a3b8]">এই নম্বরে শুধুমাত্র একবার অংশগ্রহণ করা যাবে।</p>
                </div>

                <!-- Email (optional) -->
                <div class="space-y-1.5">
                    <label class="text-sm font-semibold text-[#374151]">
                        ইমেইল <span class="text-[#94a3b8] font-normal text-xs">(ঐচ্ছিক)</span>
                    </label>
                    <input type="email" name="email" id="email"
                           maxlength="150"
                           placeholder="example@email.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           class="w-full px-4 py-3 border border-gray-200 rounded-2xl text-sm font-medium text-[#1e293b]
                                  placeholder-gray-400 bg-white
                                  focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669]
                                  transition-all"/>
                </div>

                <!-- Submit -->
                <button type="submit" id="startBtn"
                        class="w-full py-3.5 bg-gradient-to-r from-[#059669] to-[#10b981]
                               hover:from-[#047857] hover:to-[#059669]
                               text-white text-base font-bold rounded-2xl
                               shadow-lg shadow-emerald-200/60
                               transition-all duration-200
                               focus:outline-none focus:ring-4 focus:ring-[#059669]/30
                               active:scale-[0.98]">
                    কুইজ শুরু করুন →
                </button>
            </form>
        </div>

        <!-- Footer note -->
        <p class="text-center text-xs text-[#94a3b8] mt-4">
            Rahen Azat Institute · Arabic Learning Program
        </p>
    </div>

</body>
</html>
