<?php
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar_student.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight"><?= htmlspecialchars($pageTitle) ?></h1>
                <?php if ($classroomId > 0): ?>
                    <p class="text-sm text-gray-500 mt-1">Your schedule for this class.</p>
                <?php else: ?>
                    <p class="text-sm text-gray-500 mt-1">Your schedule for all upcoming classes.</p>
                <?php endif; ?>
            </div>
            <?php if ($classroomId > 0): ?>
                <a href="/student/classrooms/view.php?id=<?= $classroomId ?>" class="text-sm font-medium text-primary hover:text-primary/80 transition-colors">
                    &larr; Back to Classroom
                </a>
            <?php endif; ?>
        </div>

        <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <?php if ($classroomId === 0): ?>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class & Teacher</th>
                            <?php endif; ?>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Topic</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($sessions)): ?>
                        <tr>
                            <td colspan="<?= $classroomId === 0 ? '4' : '3' ?>" class="px-6 py-10 text-center text-gray-500">
                                <p class="text-sm">No live sessions found.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($sessions as $session): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars(date('M j, Y', strtotime($session['session_date']))) ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?= htmlspecialchars(date('g:i A', strtotime($session['start_time']))) ?> - <?= htmlspecialchars(date('g:i A', strtotime($session['end_time']))) ?>
                                    </div>
                                </td>
                                <?php if ($classroomId === 0): ?>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium"><?= htmlspecialchars($session['class_name']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($session['teacher_name']) ?></div>
                                    </td>
                                <?php endif; ?>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 truncate max-w-[200px]" title="<?= htmlspecialchars($session['topic'] ?? '') ?>">
                                        <?= htmlspecialchars($session['topic'] ?: 'Session #' . ($session['session_number'] ?? '')) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <?php if ($session['generation_status'] === 'success'): ?>
                                        <a href="/session/join.php?id=<?= $session['id'] ?>" target="_blank" class="inline-flex items-center justify-center py-1.5 px-3 border border-transparent shadow-sm text-xs font-medium rounded text-white bg-primary hover:bg-primary/90 transition-colors" title="Link opens <?= $joinOpenMinutes ?> mins before start">
                                            Join Class
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Scheduled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?= ($page - 1) * $limit + 1 ?></span> to <span class="font-medium"><?= min($page * $limit, $total) ?></span> of <span class="font-medium"><?= $total ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php for ($i = 1; $i <= $pages; $i++): ?>
                                <a href="?<?= $classroomId > 0 ? "classroom_id={$classroomId}&" : '' ?>page=<?= $i ?>" class="<?= $i === $page ? 'z-10 bg-primary/10 border-primary text-primary' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
