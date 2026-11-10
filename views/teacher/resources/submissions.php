<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_teacher.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]">
    <div class="max-w-5xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="/teacher/resources/index.php" class="p-2 bg-white rounded-xl border border-gray-200 text-gray-600 hover:text-gray-900 shadow-xs transition-colors">
                    <i class="ph ph-arrow-left text-lg"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Assignment Submissions</h1>
                    <p class="mt-0.5 text-sm text-gray-500"><?= htmlspecialchars($resource['title']) ?></p>
                </div>
            </div>
            <?php if (!empty($resource['due_date'])): ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                    <i class="ph ph-clock mr-1"></i> Due: <?= date('d M Y, h:i A', strtotime($resource['due_date'])) ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if (!empty($flashSuccess)): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center gap-2 shadow-xs">
                <i class="ph ph-check-circle text-lg text-emerald-600"></i>
                <span class="text-sm font-medium"><?= htmlspecialchars($flashSuccess) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($flashError)): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl flex items-center gap-2 shadow-xs">
                <i class="ph ph-warning-circle text-lg text-red-600"></i>
                <span class="text-sm font-medium"><?= htmlspecialchars($flashError) ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl p-6 shadow-xs border border-gray-100 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h2 class="text-base font-bold text-gray-900">Student Submissions (<?= count($submissions) ?>)</h2>
                <span class="text-xs text-gray-400">Maximum Marks: <?= (float)($resource['max_points'] ?? 100) ?></span>
            </div>

            <?php if (empty($submissions)): ?>
                <div class="text-center py-12 text-gray-400">
                    <i class="ph ph-tray text-4xl mb-2 text-gray-300"></i>
                    <p class="text-sm font-semibold text-gray-700">No submissions received yet</p>
                    <p class="text-xs text-gray-400 mt-1">Student submissions will appear here once submitted.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($submissions as $sub): ?>
                        <div class="py-4 space-y-3" x-data="{ openGradeModal: false }">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($sub['student_name']) ?></p>
                                    <p class="text-xs text-gray-400"><?= htmlspecialchars($sub['student_email']) ?> • Submitted <?= date('d M Y, h:i A', strtotime($sub['submitted_at'])) ?></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <?php if ($sub['status'] === 'graded'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                            Grade: <?= (float)$sub['points_awarded'] ?> / <?= (float)($resource['max_points'] ?? 100) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            Pending Evaluation
                                        </span>
                                    <?php endif; ?>

                                    <button type="button" @click="openGradeModal = !openGradeModal" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-lg transition-colors">
                                        <?= $sub['status'] === 'graded' ? 'Edit Grade' : 'Grade' ?>
                                    </button>
                                </div>
                            </div>

                            <?php if (!empty($sub['notes'])): ?>
                                <div class="p-3 bg-gray-50 rounded-lg text-xs text-gray-700">
                                    <span class="font-bold text-gray-900">Student Answer Notes:</span> <?= nl2br(htmlspecialchars($sub['notes'])) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($sub['file_path'])): ?>
                                <div>
                                    <a href="/resource/download.php?type=submission&id=<?= (int)$sub['id'] ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:underline">
                                        <i class="ph ph-file-arrow-down text-base"></i>
                                        Download Submission File (<?= htmlspecialchars($sub['original_name'] ?: 'File') ?>)
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($sub['teacher_feedback'])): ?>
                                <div class="p-2.5 bg-emerald-50/50 rounded-lg text-xs text-emerald-900 border border-emerald-100">
                                    <span class="font-bold">Your Feedback:</span> <?= htmlspecialchars($sub['teacher_feedback']) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Grade Modal / Form -->
                            <div x-show="openGradeModal" class="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-3 mt-2">
                                <form action="/teacher/resources/submissions.php" method="POST" class="space-y-3">
                                    <input type="hidden" name="csrf_token" value="<?= function_exists('getCsrfToken') ? getCsrfToken() : '' ?>">
                                    <input type="hidden" name="submission_id" value="<?= (int)$sub['id'] ?>">
                                    <input type="hidden" name="resource_id" value="<?= (int)$resource['id'] ?>">

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Points Awarded</label>
                                            <input type="number" step="0.5" name="points_awarded" required value="<?= (float)($sub['points_awarded'] ?? 0) ?>" class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg">
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Feedback to Student</label>
                                            <input type="text" name="teacher_feedback" value="<?= htmlspecialchars($sub['teacher_feedback'] ?? '') ?>" placeholder="e.g. Well done! Detailed answer." class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg">
                                        </div>
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="openGradeModal = false" class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200 rounded-lg">Cancel</button>
                                        <button type="submit" class="px-4 py-1.5 text-xs font-bold text-white bg-primary hover:bg-emerald-700 rounded-lg">Save Grade</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

