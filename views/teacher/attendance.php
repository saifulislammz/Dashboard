<?php
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar_teacher.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-5xl mx-auto space-y-6">

        <!-- Page Header -->
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
                Back to Classrooms
            </a>
        </div>

        <!-- Sessions Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Session-wise Attendance</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Session</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Present</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Absent</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Rate</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($sessions)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400 text-sm">
                                    No sessions with attendance data yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sessions as $i => $s):
                                $present   = (int) $s['present_count'];
                                $totalEnr  = 2; // teacher + student per classroom
                                $absent    = max(0, $totalEnr - $present);
                                $pct       = $totalEnr > 0 ? round(($present / $totalEnr) * 100) : 0;
                                $barColor  = $pct >= 75 ? 'bg-green-500' : ($pct >= 50 ? 'bg-yellow-400' : 'bg-red-500');
                                $textColor = $pct >= 75 ? 'text-green-600' : ($pct >= 50 ? 'text-yellow-600' : 'text-red-600');
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-xs text-gray-400 font-mono">
                                    <?= htmlspecialchars($s['session_number'] ?? ($offset + $i + 1), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-sm font-medium text-gray-800 truncate max-w-[200px]">
                                        <?= htmlspecialchars($s['topic'] ?? 'Untitled Session', ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-sm text-gray-700"><?= htmlspecialchars(date('d M Y', strtotime($s['session_date'])), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-gray-400">
                                        <?= htmlspecialchars(date('h:i A', strtotime($s['start_time'])), ENT_QUOTES, 'UTF-8') ?>
                                        &ndash;
                                        <?= htmlspecialchars(date('h:i A', strtotime($s['end_time'])), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                        <?= $present ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-600">
                                        <?= $absent ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-xs font-bold <?= $textColor ?>"><?= $pct ?>%</span>
                                        <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full <?= $barColor ?> rounded-full" style="width: <?= $pct ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <a href="/teacher/attendance.php?action=session&session_id=<?= $s['session_id'] ?>&classroom_id=<?= $classroom['id'] ?>"
                                       class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">
                                        View
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

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
        </div>

    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
