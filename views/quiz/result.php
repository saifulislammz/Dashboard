<!DOCTYPE html>
<html lang="bn" dir="ltr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>আপনার রেজাল্ট — Rahen Azat Institute</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @keyframes countUp {
            from { opacity: 0; transform: scale(0.6); }
            to   { opacity: 1; transform: scale(1); }
        }
        .count-up { animation: countUp .6s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .slide-up { animation: slideUp .5s ease both; }
        .slide-up-d1 { animation-delay: .1s; }
        .slide-up-d2 { animation-delay: .2s; }
        .slide-up-d3 { animation-delay: .3s; }
        .score-ring {
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#f0fdf4] via-white to-[#ecfeff] flex items-center justify-center p-4">

<?php
$a         = $attempt ?? [];
$bd        = $breakdown ?? [];
$correct   = (int) ($a['correct_answers'] ?? 0);
$total     = (int) ($a['total_questions'] ?? 0);
$scorePct  = $total > 0 ? round(($correct / $total) * 100) : 0;

// Score colour
$colour = $scorePct >= 70
    ? ['ring' => '#059669', 'bg' => 'from-emerald-500 to-teal-400', 'badge' => 'bg-emerald-100 text-emerald-700', 'label' => 'অসাধারণ! 🌟']
    : ($scorePct >= 40
        ? ['ring' => '#f59e0b', 'bg' => 'from-amber-400 to-orange-400', 'badge' => 'bg-amber-100 text-amber-700', 'label' => 'ভালো চেষ্টা! 👍']
        : ['ring' => '#ef4444', 'bg' => 'from-red-400 to-rose-500', 'badge' => 'bg-red-100 text-red-700', 'label' => 'আরো অনুশীলন করুন 💪']);

// Circular progress values
$radius       = 52;
$circumference = 2 * M_PI * $radius;
$dashOffset   = $circumference - ($scorePct / 100) * $circumference;
?>

<div class="w-full max-w-md">

    <!-- Result Card -->
    <div class="bg-white/90 backdrop-blur-sm border border-white shadow-2xl shadow-emerald-100/50 rounded-3xl overflow-hidden slide-up">

        <!-- Top gradient accent -->
        <div class="h-1.5 bg-gradient-to-r <?php echo $colour['bg']; ?>"></div>

        <!-- Score section -->
        <div class="px-8 py-8 text-center">
            <p class="text-xs font-bold uppercase tracking-widest text-[#94a3b8] mb-4">কুইজ সম্পন্ন</p>

            <!-- Score circle -->
            <div class="relative w-36 h-36 mx-auto mb-5 count-up">
                <svg class="w-full h-full" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="<?php echo $radius; ?>"
                            fill="none" stroke="#f1f5f9" stroke-width="10"/>
                    <circle cx="60" cy="60" r="<?php echo $radius; ?>"
                            fill="none"
                            stroke="<?php echo $colour['ring']; ?>"
                            stroke-width="10"
                            stroke-linecap="round"
                            class="score-ring"
                            stroke-dasharray="<?php echo number_format($circumference, 2); ?>"
                            stroke-dashoffset="<?php echo number_format($dashOffset, 2); ?>"
                            style="transition: stroke-dashoffset 1s ease;"/>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-4xl font-extrabold text-[#0f172a]"><?php echo $scorePct; ?>%</span>
                    <span class="text-xs font-medium text-[#64748b] mt-0.5"><?php echo "{$correct}/{$total}"; ?></span>
                </div>
            </div>

            <!-- Performance label -->
            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold <?php echo $colour['badge']; ?>">
                <?php echo $colour['label']; ?>
            </span>

            <!-- Participant name -->
            <p class="mt-4 text-base font-semibold text-[#1e293b]">
                <?php echo htmlspecialchars($a['participant_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <p class="text-xs text-[#94a3b8] mt-1">
                <?php echo $a['gender'] === 'male' ? 'পুরুষ' : 'মহিলা'; ?>
                · <?php echo htmlspecialchars($a['whatsapp_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>

        <!-- Breakdown cards -->
        <div class="px-8 pb-6 space-y-3">

            <?php if (!empty($bd['letter'])): ?>
            <div class="flex items-center justify-between bg-emerald-50 border border-emerald-100 rounded-2xl px-5 py-4 slide-up slide-up-d1">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center text-base">📖</div>
                    <div>
                        <p class="text-sm font-semibold text-[#1e293b]">বর্ণমালা (Letter)</p>
                        <p class="text-xs text-[#64748b]"><?php echo (int)$bd['letter']['correct']; ?> / <?php echo (int)$bd['letter']['total']; ?> সঠিক</p>
                    </div>
                </div>
                <span class="text-lg font-extrabold text-emerald-700">
                    <?php
                    $t = (int)$bd['letter']['total'];
                    echo $t > 0 ? round(($bd['letter']['correct']/$t)*100) . '%' : '—';
                    ?>
                </span>
            </div>
            <?php endif; ?>

            <?php if (!empty($bd['pronunciation'])): ?>
            <div class="flex items-center justify-between bg-blue-50 border border-blue-100 rounded-2xl px-5 py-4 slide-up slide-up-d2">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center text-base">🔤</div>
                    <div>
                        <p class="text-sm font-semibold text-[#1e293b]">উচ্চারণ (Pronunciation)</p>
                        <p class="text-xs text-[#64748b]"><?php echo (int)$bd['pronunciation']['correct']; ?> / <?php echo (int)$bd['pronunciation']['total']; ?> সঠিক</p>
                    </div>
                </div>
                <span class="text-lg font-extrabold text-blue-700">
                    <?php
                    $t = (int)$bd['pronunciation']['total'];
                    echo $t > 0 ? round(($bd['pronunciation']['correct']/$t)*100) . '%' : '—';
                    ?>
                </span>
            </div>
            <?php endif; ?>

            <!-- Voice submitted badge -->
            <?php if ($a['voice_submitted'] ?? false): ?>
            <div class="flex items-center gap-3 bg-purple-50 border border-purple-100 rounded-2xl px-5 py-4 slide-up slide-up-d3">
                <div class="w-9 h-9 rounded-xl bg-purple-100 flex items-center justify-center text-base">🎙️</div>
                <div>
                    <p class="text-sm font-semibold text-[#1e293b]">ভয়েস রেকর্ডিং</p>
                    <p class="text-xs text-purple-600 font-medium">✓ সফলভাবে জমা হয়েছে</p>
                </div>
                <svg class="ml-auto w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <?php endif; ?>
        </div>

        <!-- Thank you message -->
        <div class="px-8 pb-8">
            <div class="text-center p-5 bg-gradient-to-br from-[#f0fdf4] to-[#ecfeff] rounded-2xl border border-emerald-100">
                <p class="text-sm font-semibold text-[#374151]">ধন্যবাদ অংশগ্রহণের জন্য! 🙏</p>
                <p class="text-xs text-[#64748b] mt-1 leading-relaxed">
                    আপনার উত্তর ও ভয়েস রেকর্ডিং পর্যালোচনার পরে<br>
                    WhatsApp-এ জানানো হবে।
                </p>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <p class="text-center text-xs text-[#94a3b8] mt-4">
        <?php echo date('d M Y, H:i'); ?> · Rahen Azat Institute
    </p>
</div>

</body>
</html>
