<?php
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar_teacher.php';
require_once __DIR__ . '/../../src/config/attendance_config.php';

use App\Config\AttendanceConfig;

$totalSessions = (int)   ($summary['total_sessions']             ?? 0);
$present       = (int)   ($summary['sessions_with_attendance']   ?? 0);
$absent        = (int)   ($summary['sessions_without_attendance'] ?? 0);
$percentage    = (float) ($summary['percentage']                 ?? 0.0);

$ringColor  = 'text-green-500';
$bgColor    = 'bg-green-50';
$badgeColor = 'bg-green-100 text-green-700';
if ($percentage < AttendanceConfig::THRESHOLD_AVERAGE) {
    $ringColor  = 'text-red-500';
    $bgColor    = 'bg-red-50';
    $badgeColor = 'bg-red-100 text-red-700';
} elseif ($percentage < AttendanceConfig::THRESHOLD_GOOD) {
    $ringColor  = 'text-yellow-500';
    $bgColor    = 'bg-yellow-50';
    $badgeColor = 'bg-yellow-100 text-yellow-700';
}

$borderColor = str_contains($bgColor, 'green') ? 'green' : (str_contains($bgColor, 'yellow') ? 'yellow' : 'red');
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Attendance Report</h1>
                <p class="text-sm text-gray-500 mt-1">
                    <?= htmlspecialchars($classroom['class_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    <span class="text-gray-300 mx-1">&middot;</span>
                    <?= htmlspecialchars($classroom['class_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <a href="/teacher/classrooms.php"
               class="inline-flex items-center gap-2 bg-white py-2 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back
            </a>
        </div>

        <!-- Summary Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 <?= $bgColor ?> border-l-4 border-l-<?= $borderColor ?>-400">
            <div class="flex flex-col sm:flex-row items-center gap-6">

                <!-- Big Percentage Circle -->
                <div class="flex-shrink-0 flex flex-col items-center justify-center">
                    <div class="relative h-28 w-28">
                        <svg class="h-28 w-28 -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.9" fill="none"
                                    stroke="currentColor"
                                    stroke-width="3"
                                    stroke-dasharray="<?= min(100, $percentage) ?>, 100"
                                    stroke-linecap="round"
                                    class="<?= $ringColor ?> transition-all duration-700"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-extrabold text-gray-800"><?= $percentage ?>%</span>
                            <span class="text-xs text-gray-400">Rate</span>
                        </div>
                    </div>
                    <span class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $badgeColor ?>">
                        <?= $percentage >= AttendanceConfig::THRESHOLD_GOOD ? 'Good Attendance' : ($percentage >= AttendanceConfig::THRESHOLD_AVERAGE ? 'Average' : 'Low Attendance') ?>
                    </span>
                </div>

                <!-- Stats -->
                <div class="flex-1 grid grid-cols-3 gap-4 text-center">
                    <div class="bg-white rounded-xl p-4 shadow-sm">
                        <p class="text-3xl font-bold text-gray-800"><?= $totalSessions ?></p>
                        <p class="text-xs text-gray-400 mt-1 uppercase tracking-wide font-medium">Total</p>
                    </div>
                    <div class="bg-green-50 rounded-xl p-4 shadow-sm">
                        <p class="text-3xl font-bold text-green-700"><?= $present ?></p>
                        <p class="text-xs text-green-500 mt-1 uppercase tracking-wide font-medium">Attended</p>
                    </div>
                    <div class="bg-red-50 rounded-xl p-4 shadow-sm">
                        <p class="text-3xl font-bold text-red-600"><?= $absent ?></p>
                        <p class="text-xs text-red-400 mt-1 uppercase tracking-wide font-medium">Missed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Session History -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Session History</h2>
            </div>

            <?php if (empty($sessions)): ?>
                <div class="p-10 text-center text-gray-400 text-sm">No past sessions yet.</div>
            <?php else: ?>
                <ul class="divide-y divide-gray-50">
                    <?php foreach ($sessions as $s):
                        $presentCount = (int) $s['present_count'];
                        $hasAttendance = $presentCount > 0;
                    ?>
                    <li class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50 transition-colors">

                        <!-- Status indicator -->
                        <div class="flex-shrink-0">
                            <?php if ($hasAttendance): ?>
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-green-100">
                                    <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-red-100">
                                    <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Session info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">
                                <?= htmlspecialchars($s['topic'] ?? 'Untitled Session', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                <?= htmlspecialchars(date('d M Y', strtotime($s['session_date'])), ENT_QUOTES, 'UTF-8') ?>
                                &middot;
                                <?= htmlspecialchars(date('h:i A', strtotime($s['start_time'])), ENT_QUOTES, 'UTF-8') ?>
                                &ndash;
                                <?= htmlspecialchars(date('h:i A', strtotime($s['end_time'])), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>

                        <!-- Badge -->
                        <div class="flex-shrink-0">
                            <?php if ($hasAttendance): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                    <?= $presentCount ?> Present
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-600">
                                    No Attendance
                                </span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Pagination -->
                <?php if ($pages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">Page <?= $page ?> of <?= $pages ?></p>
                    <div class="flex gap-1">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <a href="?classroom_id=<?= $classroom['id'] ?>&page=<?= $i ?>"
                               class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-xs font-medium transition-colors
                                      <?= $i === $page ? 'bg-primary text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
