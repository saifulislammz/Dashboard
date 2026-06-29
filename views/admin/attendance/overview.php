<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Attendance Overview</h1>
                <p class="text-sm text-gray-500 mt-1">All sessions with attendance statistics</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <!-- Classroom Filter -->
                <div>
                    <label for="classroom_id" class="block text-xs font-medium text-gray-600 mb-1">Classroom</label>
                    <select id="classroom_id" name="classroom_id"
                            class="block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="0">All Classrooms</option>
                        <?php foreach ($classrooms as $cr): ?>
                            <option value="<?= $cr['id'] ?>"
                                <?= ((int)($_GET['classroom_id'] ?? 0) === (int)$cr['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cr['class_name'] . ' (' . $cr['class_code'] . ')', ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Date From -->
                <div>
                    <label for="date_from" class="block text-xs font-medium text-gray-600 mb-1">From Date</label>
                    <input type="date" id="date_from" name="date_from"
                           value="<?= htmlspecialchars($_GET['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           class="block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
                <!-- Date To -->
                <div>
                    <label for="date_to" class="block text-xs font-medium text-gray-600 mb-1">To Date</label>
                    <input type="date" id="date_to" name="date_to"
                           value="<?= htmlspecialchars($_GET['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           class="block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
                <!-- Actions -->
                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 inline-flex items-center justify-center rounded-lg bg-primary py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-primary/90 transition-colors">
                        <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm0 8a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm0 8a1 1 0 011-1h10a1 1 0 010 2H4a1 1 0 01-1-1z"/>
                        </svg>
                        Filter
                    </button>
                    <a href="/admin/attendance/overview.php"
                       class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Session</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Classroom</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Teacher</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Present</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Absent</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Rate</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-400 text-sm">
                                    No sessions found. Try adjusting the filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row):
                                $present      = (int) $row['present_count'];
                                $totalEnr     = (int) $row['total_enrolled'];
                                $absent       = max(0, $totalEnr - $present);
                                $pct          = $totalEnr > 0 ? round(($present / $totalEnr) * 100) : 0;
                                $barColor     = $pct >= 75 ? 'bg-green-500' : ($pct >= 50 ? 'bg-yellow-400' : 'bg-red-500');
                                $textColor    = $pct >= 75 ? 'text-green-700' : ($pct >= 50 ? 'text-yellow-700' : 'text-red-600');
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-gray-900 truncate max-w-[180px]">
                                        <?= htmlspecialchars($row['topic'] ?? 'Untitled', ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        <?= htmlspecialchars(date('d M Y', strtotime($row['session_date'])), ENT_QUOTES, 'UTF-8') ?>
                                        &middot;
                                        <?= htmlspecialchars(date('h:i A', strtotime($row['start_time'])), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-sm text-gray-700 truncate max-w-[140px]">
                                        <?= htmlspecialchars($row['class_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                    <p class="text-xs text-gray-400"><?= htmlspecialchars($row['class_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600 max-w-[120px] truncate">
                                    <?= htmlspecialchars($row['teacher_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600 max-w-[120px] truncate">
                                    <?= htmlspecialchars($row['student_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                        <?= $present ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
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
                                    <a href="/admin/attendance/session_report.php?session_id=<?= $row['session_id'] ?>"
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
                <p class="text-xs text-gray-500">
                    Showing page <?= $page ?> of <?= $pages ?> (<?= $total ?> sessions)
                </p>
                <div class="flex gap-1">
                    <?php for ($i = 1; $i <= $pages; $i++):
                        $params = array_merge($_GET, ['page' => $i]);
                        $qs     = http_build_query($params);
                    ?>
                        <a href="?<?= $qs ?>"
                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-xs font-medium transition-colors
                                  <?= $i === $page ? 'bg-primary text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </div><!-- /table card -->

    </div><!-- /max-w -->
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
