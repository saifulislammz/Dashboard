<?php
// views/quiz/result.php
$a = $attempt ?? [];
$bd = $breakdown ?? [];
$correct = (int) ($a['correct_answers'] ?? 0);
$total = (int) ($a['total_questions'] ?? 0);

// Format numbers as strings
function formatNum($num)
{
    return (string) $num;
}

$bnCorrect = formatNum($correct);
$bnTotal = formatNum($total);
$attemptIdBn = formatNum($a['id'] ?? 1);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Your Result — Rahe nazat Institute</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7f9;
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
            animation: slideUp .6s ease-out both;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-[420px]">

        <!-- Result Card -->
        <!-- Removed overflow-hidden so the absolute badge isn't cut off, added mt-12 for spacing -->
        <div
            class="bg-white rounded-[32px] shadow-sm border border-gray-100 slide-up relative pt-16 pb-8 px-6 text-center mt-12">

            <!-- Top Badge Icon -->
            <div class="absolute -top-12 left-1/2 -translate-x-1/2">
                <div
                    class="w-24 h-24 bg-[#10b981] rounded-full flex items-center justify-center shadow-[0_8px_30px_rgba(16,185,129,0.3)] border-4 border-[#f5f7f9]">
                    <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>

            <div class="mb-4">
                <h2 class="text-2xl font-bold text-gray-800 mb-1">Congratulations!</h2>
                <p class="text-sm font-semibold text-gray-400">Your Score</p>
            </div>

            <!-- Large Score text -->
            <div class="mb-4 flex justify-center items-baseline gap-2 text-[#0f5132] font-black tracking-tight"
                style="font-size: 3.5rem; line-height: 1;">
                <span><?php echo $bnCorrect; ?></span>
                <span class="text-3xl text-gray-300 font-bold">/</span>
                <span class="text-3xl text-gray-400 font-bold"><?php echo $bnTotal; ?></span>
            </div>

            <!-- Participant Rank Pill -->
            <div
                class="inline-flex items-center bg-[#eefcf2] text-[#0f5132] px-5 py-2 rounded-full text-sm font-bold mb-8 border border-[#d1f4e0]">
                Participant #<?php echo $attemptIdBn; ?>
            </div>

            <!-- Breakdown Row -->
            <div class="grid grid-cols-3 gap-3">

                <!-- Letter Card -->
                <div class="bg-gray-50 border border-gray-100 rounded-2xl py-3 px-2 flex flex-col items-center">
                    <span class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Letter</span>
                    <span class="text-base font-extrabold text-gray-800">
                        <?php echo formatNum((int) ($bd['letter']['correct'] ?? 0)); ?>/<?php echo formatNum((int) ($bd['letter']['total'] ?? 0)); ?>
                    </span>
                </div>

                <!-- Pronunciation Card -->
                <div class="bg-gray-50 border border-gray-100 rounded-2xl py-3 px-2 flex flex-col items-center">
                    <span class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Pronunciation</span>
                    <span class="text-base font-extrabold text-gray-800">
                        <?php echo formatNum((int) ($bd['pronunciation']['correct'] ?? 0)); ?>/<?php echo formatNum((int) ($bd['pronunciation']['total'] ?? 0)); ?>
                    </span>
                </div>

                <!-- Voice/Word Card -->
                <div class="bg-gray-50 border border-gray-100 rounded-2xl py-3 px-2 flex flex-col items-center">
                    <span class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Meaning</span>
                    <span class="text-base font-extrabold text-gray-800">
                        <!-- Defaulting to voice or 1/1 if submitted for UI fidelity -->
                        <?php echo ($a['voice_submitted'] ?? false) ? '1/1' : '0/0'; ?>
                    </span>
                </div>

            </div>

        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-xs font-semibold text-gray-400 tracking-wider uppercase">
                Rahe nazat Institute
            </p>
        </div>

    </div>

</body>

</html>