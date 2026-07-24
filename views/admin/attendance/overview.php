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
                <p class="text-sm text-gray-500 mt-1">All classrooms — view individual attendance reports</p>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sessions</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Classroom</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Teacher</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">
                                    No classrooms found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                            <tr class="hover:bg-gray-50 transition-colors">

                                <!-- Sessions count -->
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                        <?= (int) $row['total_sessions'] ?> session<?= (int) $row['total_sessions'] !== 1 ? 's' : '' ?>
                                    </span>
                                </td>

                                <!-- Classroom -->
                                <td class="px-4 py-4">
                                    <p class="text-sm font-medium text-gray-900 truncate max-w-[180px]">
                                        <?= htmlspecialchars($row['class_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        <?= htmlspecialchars($row['class_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </td>

                                <!-- Teacher -->
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-semibold text-violet-700">
                                            <?= htmlspecialchars(mb_strtoupper(mb_substr($row['teacher_name'] ?? 'T', 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span class="text-sm text-gray-700 truncate max-w-[110px]">
                                            <?= htmlspecialchars($row['teacher_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- Student -->
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700">
                                            <?= htmlspecialchars(mb_strtoupper(mb_substr($row['student_name'] ?? 'S', 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span class="text-sm text-gray-700 truncate max-w-[110px]">
                                            <?= htmlspecialchars($row['student_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- View button -->
                                <td class="px-4 py-4 text-center">
                                    <a href="/admin/attendance/detail.php?classroom_id=<?= (int) $row['classroom_id'] ?>"
                                       class="inline-flex items-center gap-1.5 rounded-lg bg-primary/10 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary hover:text-white transition-colors">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
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
                    Page <?= $page ?> of <?= $pages ?> (<?= $total ?> classrooms)
                </p>
                <div class="flex gap-1">
                    <?php for ($i = 1; $i <= $pages; $i++):
                        $qs = http_build_query(['page' => $i]);
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
