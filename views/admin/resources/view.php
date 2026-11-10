<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]">
    <div class="max-w-5xl mx-auto space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="/admin/resources/index.php" class="p-2 bg-white rounded-xl border border-gray-200 text-gray-600 hover:text-gray-900 shadow-xs transition-colors">
                    <i class="ph ph-arrow-left text-lg"></i>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                            <?php if ($resource['resource_type'] === 'document'): ?>bg-blue-100 text-blue-800
                            <?php elseif ($resource['resource_type'] === 'video'): ?>bg-red-100 text-red-800
                            <?php elseif ($resource['resource_type'] === 'assignment'): ?>bg-amber-100 text-amber-800
                            <?php elseif ($resource['resource_type'] === 'quiz'): ?>bg-purple-100 text-purple-800
                            <?php else: ?>bg-teal-100 text-teal-800<?php endif; ?>">
                            <?= ucfirst($resource['resource_type']) ?>
                        </span>
                        <span class="text-xs text-gray-400">Created <?= date('d M Y, h:i A', strtotime($resource['created_at'])) ?> by <?= htmlspecialchars($resource['creator_username'] ?? 'Admin') ?></span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1"><?= htmlspecialchars($resource['title']) ?></h1>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="/admin/resources/edit.php?id=<?= (int)$resource['id'] ?>" class="inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 shadow-xs transition-colors">
                    <i class="ph ph-pencil-simple text-base mr-1.5"></i>
                    Edit
                </a>
                <form action="/admin/resources/delete.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this resource?');">
                    <input type="hidden" name="csrf_token" value="<?= function_exists('getCsrfToken') ? getCsrfToken() : '' ?>">
                    <input type="hidden" name="id" value="<?= (int)$resource['id'] ?>">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-red-200 text-sm font-semibold rounded-xl text-red-700 bg-red-50 hover:bg-red-100 shadow-xs transition-colors">
                        <i class="ph ph-trash text-base mr-1.5"></i>
                        Delete
                    </button>
                </form>
            </div>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left: Resource Info & Attachment -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-xs border border-gray-100 space-y-4">
                    <h2 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                        <i class="ph ph-article text-emerald-600 text-lg"></i>
                        Overview & Content
                    </h2>

                    <?php if (!empty($resource['description'])): ?>
                        <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-line bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                            <?= htmlspecialchars($resource['description']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Action / Attachment Card -->
                    <?php if (!empty($resource['file_path'])): ?>
                        <div class="p-4 bg-emerald-50/40 rounded-xl border border-emerald-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                                    <i class="ph ph-file-arrow-down text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($resource['original_name'] ?: 'Resource File') ?></p>
                                    <p class="text-xs text-gray-500"><?= round(($resource['file_size'] ?? 0) / 1024 / 1024, 2) ?> MB • Secured</p>
                                </div>
                            </div>
                            <a href="/resource/download.php?id=<?= (int)$resource['id'] ?>" class="inline-flex items-center px-4 py-2 bg-primary hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-xs transition-colors">
                                <i class="ph ph-download-simple mr-1 text-sm"></i> Download
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($resource['external_url'])): ?>
                        <div class="p-4 bg-blue-50/40 rounded-xl border border-blue-100 flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold flex-shrink-0">
                                    <i class="ph ph-link text-xl"></i>
                                </div>
                                <div class="truncate">
                                    <p class="text-sm font-bold text-gray-900">External Resource URL</p>
                                    <p class="text-xs text-blue-600 truncate"><?= htmlspecialchars($resource['external_url']) ?></p>
                                </div>
                            </div>
                            <a href="<?= htmlspecialchars($resource['external_url']) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-xs transition-colors flex-shrink-0">
                                <i class="ph ph-arrow-square-out mr-1 text-sm"></i> Open Link
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($resource['resource_type'] === 'quiz' && !empty($resource['quiz_title'])): ?>
                        <div class="p-4 bg-purple-50/40 rounded-xl border border-purple-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-bold">
                                    <i class="ph ph-question text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($resource['quiz_title']) ?></p>
                                    <p class="text-xs text-gray-500">LMS Integrated Examination</p>
                                </div>
                            </div>
                            <a href="/admin/quiz/view.php?id=<?= (int)$resource['quiz_id'] ?>" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-lg shadow-xs transition-colors">
                                View Quiz
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Assignment Submissions (If Resource is Assignment) -->
                <?php if ($resource['resource_type'] === 'assignment'): ?>
                    <div id="submissions" class="bg-white rounded-2xl p-6 shadow-xs border border-gray-100 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <div>
                                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <i class="ph ph-clipboard-text text-amber-600 text-lg"></i>
                                    Student Assignment Submissions
                                </h2>
                                <p class="text-xs text-gray-500 mt-0.5">Total <?= count($submissions) ?> student(s) submitted their work.</p>
                            </div>
                        </div>

                        <?php if (empty($submissions)): ?>
                            <div class="text-center py-8 text-gray-400">
                                <i class="ph ph-tray text-3xl mb-1"></i>
                                <p class="text-sm font-medium">No student submissions received yet.</p>
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
                                                <span class="font-bold text-gray-900">Student Notes:</span> <?= nl2br(htmlspecialchars($sub['notes'])) ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($sub['file_path'])): ?>
                                            <div>
                                                <a href="/resource/download.php?type=submission&id=<?= (int)$sub['id'] ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:underline">
                                                    <i class="ph ph-file-arrow-down text-base"></i>
                                                    Download Submitted File (<?= htmlspecialchars($sub['original_name'] ?: 'File') ?>)
                                                </a>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($sub['teacher_feedback'])): ?>
                                            <div class="p-2.5 bg-emerald-50/50 rounded-lg text-xs text-emerald-900 border border-emerald-100">
                                                <span class="font-bold">Feedback:</span> <?= htmlspecialchars($sub['teacher_feedback']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Grade Modal / Form -->
                                        <div x-show="openGradeModal" class="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-3 mt-2">
                                            <form action="/admin/resources/grade.php" method="POST" class="space-y-3">
                                                <input type="hidden" name="csrf_token" value="<?= function_exists('getCsrfToken') ? getCsrfToken() : '' ?>">
                                                <input type="hidden" name="submission_id" value="<?= (int)$sub['id'] ?>">
                                                <input type="hidden" name="resource_id" value="<?= (int)$resource['id'] ?>">

                                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                    <div>
                                                        <label class="block text-xs font-bold text-gray-700 mb-1">Points Awarded</label>
                                                        <input type="number" step="0.5" name="points_awarded" required value="<?= (float)($sub['points_awarded'] ?? 0) ?>" class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg">
                                                    </div>
                                                    <div class="sm:col-span-2">
                                                        <label class="block text-xs font-bold text-gray-700 mb-1">Teacher Feedback / Remarks</label>
                                                        <input type="text" name="teacher_feedback" value="<?= htmlspecialchars($sub['teacher_feedback'] ?? '') ?>" placeholder="e.g. Excellent work on architecture" class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg">
                                                    </div>
                                                </div>

                                                <div class="flex justify-end gap-2">
                                                    <button type="button" @click="openGradeModal = false" class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200 rounded-lg">Cancel</button>
                                                    <button type="submit" class="px-4 py-1.5 text-xs font-bold text-white bg-primary hover:bg-emerald-700 rounded-lg">Submit Grade</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Target Classrooms & Meta -->
            <div class="space-y-6">
                <!-- Meta Info -->
                <div class="bg-white rounded-2xl p-6 shadow-xs border border-gray-100 space-y-3">
                    <h2 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-2">Resource Settings</h2>
                    
                    <div class="text-xs space-y-2 text-gray-600">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Target Audience:</span>
                            <span class="font-bold text-gray-800"><?= ucfirst($resource['target_audience']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Status:</span>
                            <span class="font-bold text-emerald-600"><?= ucfirst($resource['status']) ?></span>
                        </div>
                        <?php if (!empty($resource['due_date'])): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Due Deadline:</span>
                                <span class="font-bold text-amber-700"><?= date('d M Y, h:i A', strtotime($resource['due_date'])) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($resource['max_points'] !== null): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Max Points:</span>
                                <span class="font-bold text-gray-800"><?= (float)$resource['max_points'] ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Distributed Classrooms List -->
                <div class="bg-white rounded-2xl p-6 shadow-xs border border-gray-100 space-y-3">
                    <h2 class="text-sm font-bold text-gray-900 flex items-center justify-between border-b border-gray-100 pb-2">
                        <span>Distributed Classrooms</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                            <?= count($distributedClassrooms) ?> Groups
                        </span>
                    </h2>

                    <div class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                        <?php if (empty($distributedClassrooms)): ?>
                            <p class="text-xs text-gray-400">No active classroom mappings found.</p>
                        <?php else: ?>
                            <?php foreach ($distributedClassrooms as $dc): ?>
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-gray-900"><?= htmlspecialchars($dc['class_name']) ?></p>
                                        <p class="text-[11px] text-gray-400 font-mono"><?= htmlspecialchars($dc['class_code']) ?></p>
                                    </div>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                        <?= htmlspecialchars($dc['status']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

