<?php
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar_teacher.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-3xl mx-auto space-y-6">

        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Attendance Reports</h1>
            <p class="text-sm text-gray-500 mt-1">Select a classroom to view its attendance report</p>
        </div>

        <?php if (empty($classrooms)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center text-gray-400 text-sm">
                No classrooms assigned to you yet.
            </div>
        <?php else: ?>
            <div class="grid gap-4">
                <?php foreach ($classrooms as $cr): ?>
                <a href="/teacher/attendance.php?classroom_id=<?= (int) $cr['id'] ?>"
                   class="flex items-center justify-between bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-5 hover:border-primary hover:shadow-md transition-all group">
                    <div>
                        <p class="text-sm font-semibold text-gray-800 group-hover:text-primary transition-colors">
                            <?= htmlspecialchars($cr['class_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            <?= htmlspecialchars($cr['class_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($cr['student_name'])): ?>
                                &middot; Student: <?= htmlspecialchars($cr['student_name'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <svg class="h-5 w-5 text-gray-300 group-hover:text-primary transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
