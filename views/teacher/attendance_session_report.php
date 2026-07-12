<?php
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar_teacher.php';

$presentCount  = $report['stats']['present_count']  ?? 0;
$absentCount   = $report['stats']['absent_count']   ?? 0;
$totalEnrolled = $report['stats']['total_enrolled'] ?? 0;
$percentage    = $report['stats']['percentage']     ?? 0.0;
$session       = $report['session'] ?? [];
$present       = $report['present'] ?? [];
$absent        = $report['absent']  ?? [];

$statusColor = 'green';
if ($percentage < 50) $statusColor = 'red';
elseif ($percentage < 75) $statusColor = 'yellow';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Session Attendance Report</h1>
                <p class="text-sm text-gray-500 mt-1">
                    <?= htmlspecialchars($session['class_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    &mdash; <?= htmlspecialchars($session['topic'] ?? 'Session', ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="/teacher/attendance.php?classroom_id=<?= (int)($session['classroom_id'] ?? 0) ?>"
                   class="inline-flex items-center gap-2 bg-white py-2 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to Overview
                </a>
            </div>
        </div>

        <!-- Session Info Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-gray-500 text-xs uppercase font-medium tracking-wide">Date</p>
                    <p class="font-semibold text-gray-800 mt-1">
                        <?= htmlspecialchars(date('d M Y', strtotime($session['session_date'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs uppercase font-medium tracking-wide">Time</p>
                    <p class="font-semibold text-gray-800 mt-1">
                        <?= htmlspecialchars(date('h:i A', strtotime($session['start_time'] ?? '00:00')), ENT_QUOTES, 'UTF-8') ?>
                        &ndash;
                        <?= htmlspecialchars(date('h:i A', strtotime($session['end_time'] ?? '00:00')), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs uppercase font-medium tracking-wide">Teacher</p>
                    <p class="font-semibold text-gray-800 mt-1"><?= htmlspecialchars($session['teacher_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs uppercase font-medium tracking-wide">Student</p>
                    <p class="font-semibold text-gray-800 mt-1"><?= htmlspecialchars($session['student_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <!-- Attendance % -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 col-span-2 sm:col-span-1">
                <p class="text-xs text-gray-500 uppercase font-medium tracking-wide">Attendance Rate</p>
                <p class="mt-2 text-4xl font-extrabold text-<?= $statusColor ?>-600"><?= $percentage ?>%</p>
                <div class="mt-3 w-full bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full bg-<?= $statusColor ?>-500 transition-all" style="width: <?= min(100, $percentage) ?>%"></div>
                </div>
            </div>
            <!-- Total -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500 uppercase font-medium tracking-wide">Total Enrolled</p>
                <p class="mt-2 text-3xl font-bold text-gray-800"><?= $totalEnrolled ?></p>
            </div>
            <!-- Present -->
            <div class="bg-white rounded-xl shadow-sm border border-green-100 p-5">
                <p class="text-xs text-green-600 uppercase font-medium tracking-wide">Present</p>
                <p class="mt-2 text-3xl font-bold text-green-700"><?= $presentCount ?></p>
            </div>
            <!-- Absent -->
            <div class="bg-white rounded-xl shadow-sm border border-red-100 p-5">
                <p class="text-xs text-red-500 uppercase font-medium tracking-wide">Absent</p>
                <p class="mt-2 text-3xl font-bold text-red-600"><?= $absentCount ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Present List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                    <h2 class="text-sm font-semibold text-gray-800">Present (<?= $presentCount ?>)</h2>
                </div>
                <?php if (empty($present)): ?>
                    <div class="p-6 text-center text-gray-400 text-sm">No attendees recorded.</div>
                <?php else: ?>
                    <ul class="divide-y divide-gray-50">
                        <?php foreach ($present as $p): ?>
                            <li class="px-6 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-green-700">
                                            <?= strtoupper(substr($p['username'] ?? 'U', 0, 1)) ?>
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($p['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>

                                    </div>
                                </div>
                                <span class="text-xs text-gray-400 whitespace-nowrap flex-shrink-0">
                                    <?= htmlspecialchars(date('h:i A', strtotime($p['join_time'])), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Absent List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </span>
                    <h2 class="text-sm font-semibold text-gray-800">Absent (<?= $absentCount ?>)</h2>
                </div>
                <?php if (empty($absent)): ?>
                    <div class="p-6 text-center text-gray-400 text-sm">Everyone attended! 🎉</div>
                <?php else: ?>
                    <ul class="divide-y divide-gray-50">
                        <?php foreach ($absent as $a): ?>
                            <li class="px-6 py-3 flex items-center gap-3 hover:bg-gray-50 transition-colors">
                                <div class="h-8 w-8 rounded-full bg-red-50 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-red-400">
                                        <?= strtoupper(substr($a['username'] ?? 'U', 0, 1)) ?>
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-700 truncate"><?= htmlspecialchars($a['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>

                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

        </div><!-- /grid -->
    </div><!-- /max-w -->
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
