<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Classroom Resources & Distribution</h1>
                <p class="mt-1 text-sm text-gray-500">Bulk upload and distribute files, videos, assignments, and quizzes to Group Classrooms.</p>
            </div>
            <a href="/admin/resources/create.php" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-semibold rounded-xl shadow-xs text-white bg-primary hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all">
                <i class="ph ph-plus-circle text-lg mr-2"></i>
                Distribute New Resource
            </a>
        </div>

        <?php if (!empty($flashSuccess)): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <i class="ph ph-check-circle text-lg text-emerald-600"></i>
                    <span><?= htmlspecialchars($flashSuccess) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($flashError)): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <i class="ph ph-warning-circle text-lg text-red-600"></i>
                    <span><?= htmlspecialchars($flashError) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters & Search -->
        <div class="bg-white shadow-xs border border-gray-100 rounded-2xl p-5">
            <form action="/admin/resources/index.php" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Search</label>
                    <div class="relative">
                        <i class="ph ph-magnifying-glass absolute left-3 top-3 text-gray-400"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search by title or description..." class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Resource Type</label>
                    <select name="type" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Types</option>
                        <option value="document" <?= ($type ?? '') === 'document' ? 'selected' : '' ?>>Document / File</option>
                        <option value="video" <?= ($type ?? '') === 'video' ? 'selected' : '' ?>>Video Lecture</option>
                        <option value="assignment" <?= ($type ?? '') === 'assignment' ? 'selected' : '' ?>>Assignment</option>
                        <option value="quiz" <?= ($type ?? '') === 'quiz' ? 'selected' : '' ?>>Quiz Exam</option>
                        <option value="link" <?= ($type ?? '') === 'link' ? 'selected' : '' ?>>Web Link</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Group Classroom</label>
                    <select name="classroom_id" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Classrooms</option>
                        <?php foreach ($classrooms as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= ((int)($classroomId ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['class_name']) ?> (<?= htmlspecialchars($c['class_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 py-2 px-4 bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold rounded-xl transition-colors">
                        Filter
                    </button>
                    <a href="/admin/resources/index.php" class="py-2 px-3 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-medium rounded-xl transition-colors">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Data Table Container -->
        <div class="bg-white shadow-xs border border-gray-100 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/70">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Resource</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Audience</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Distributed Classrooms</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Submissions</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Created By</th>
                            <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <?php if (empty($resources)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="ph ph-folder-open text-4xl text-gray-300 mb-2"></i>
                                        <p class="text-sm font-semibold text-gray-700">No resources found</p>
                                        <p class="text-xs text-gray-400 mt-1">Click "Distribute New Resource" to share materials with group classrooms.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($resources as $res): ?>
                                <tr class="hover:bg-gray-50/60 transition-colors">
                                    <!-- Title & Meta -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-start gap-3">
                                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 
                                                <?php if ($res['resource_type'] === 'document'): ?>bg-blue-50 text-blue-600
                                                <?php elseif ($res['resource_type'] === 'video'): ?>bg-red-50 text-red-600
                                                <?php elseif ($res['resource_type'] === 'assignment'): ?>bg-amber-50 text-amber-600
                                                <?php elseif ($res['resource_type'] === 'quiz'): ?>bg-purple-50 text-purple-600
                                                <?php else: ?>bg-teal-50 text-teal-600<?php endif; ?>">
                                                
                                                <?php if ($res['resource_type'] === 'document'): ?>
                                                    <i class="ph ph-file-text text-xl"></i>
                                                <?php elseif ($res['resource_type'] === 'video'): ?>
                                                    <i class="ph ph-video-camera text-xl"></i>
                                                <?php elseif ($res['resource_type'] === 'assignment'): ?>
                                                    <i class="ph ph-clipboard-text text-xl"></i>
                                                <?php elseif ($res['resource_type'] === 'quiz'): ?>
                                                    <i class="ph ph-question text-xl"></i>
                                                <?php else: ?>
                                                    <i class="ph ph-link text-xl"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="min-w-0">
                                                <a href="/admin/resources/view.php?id=<?= (int)$res['id'] ?>" class="text-sm font-bold text-gray-900 hover:text-primary transition-colors block truncate max-w-xs">
                                                    <?= htmlspecialchars($res['title']) ?>
                                                </a>
                                                <div class="flex items-center gap-2 text-xs text-gray-400 mt-0.5">
                                                    <span><?= date('d M Y, h:i A', strtotime($res['created_at'])) ?></span>
                                                    <?php if (!empty($res['due_date'])): ?>
                                                        <span class="text-amber-600 font-medium">• Due: <?= date('d M Y', strtotime($res['due_date'])) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Type -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                            <?php if ($res['resource_type'] === 'document'): ?>bg-blue-100 text-blue-800
                                            <?php elseif ($res['resource_type'] === 'video'): ?>bg-red-100 text-red-800
                                            <?php elseif ($res['resource_type'] === 'assignment'): ?>bg-amber-100 text-amber-800
                                            <?php elseif ($res['resource_type'] === 'quiz'): ?>bg-purple-100 text-purple-800
                                            <?php else: ?>bg-teal-100 text-teal-800<?php endif; ?>">
                                            <?= ucfirst($res['resource_type']) ?>
                                        </span>
                                    </td>

                                    <!-- Audience -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs font-medium text-gray-600">
                                        <?php if ($res['target_audience'] === 'all'): ?>
                                            <span class="inline-flex items-center gap-1 text-gray-700">
                                                <i class="ph ph-users text-sm"></i> All Members
                                            </span>
                                        <?php elseif ($res['target_audience'] === 'students'): ?>
                                            <span class="inline-flex items-center gap-1 text-emerald-700">
                                                <i class="ph ph-student text-sm"></i> Students Only
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 text-indigo-700">
                                                <i class="ph ph-chalkboard-teacher text-sm"></i> Teachers Only
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Distributed Classrooms -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <i class="ph ph-chalkboard text-xs mr-1"></i>
                                            <?= (int)$res['distribution_count'] ?> Classroom(s)
                                        </span>
                                    </td>

                                    <!-- Submissions (if assignment) -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        <?php if ($res['resource_type'] === 'assignment'): ?>
                                            <a href="/admin/resources/view.php?id=<?= (int)$res['id'] ?>#submissions" class="font-bold text-primary hover:underline">
                                                <?= (int)$res['submission_count'] ?> Submitted
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Created By -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                                        <span class="font-medium text-gray-900"><?= htmlspecialchars($res['creator_username'] ?? 'Admin') ?></span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <?php if (!empty($res['file_path'])): ?>
                                                <a href="/resource/download.php?id=<?= (int)$res['id'] ?>" class="p-1.5 text-gray-500 hover:text-primary transition-colors" title="Download File">
                                                    <i class="ph ph-download-simple text-lg"></i>
                                                </a>
                                            <?php elseif (!empty($res['external_url'])): ?>
                                                <a href="<?= htmlspecialchars($res['external_url']) ?>" target="_blank" rel="noopener noreferrer" class="p-1.5 text-gray-500 hover:text-blue-600 transition-colors" title="Open Link">
                                                    <i class="ph ph-arrow-square-out text-lg"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="/admin/resources/view.php?id=<?= (int)$res['id'] ?>" class="p-1.5 text-gray-500 hover:text-gray-900 transition-colors" title="View Details">
                                                <i class="ph ph-eye text-lg"></i>
                                            </a>

                                            <a href="/admin/resources/edit.php?id=<?= (int)$res['id'] ?>" class="p-1.5 text-gray-500 hover:text-amber-600 transition-colors" title="Edit Resource">
                                                <i class="ph ph-pencil-simple text-lg"></i>
                                            </a>

                                            <form action="/admin/resources/delete.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this resource? It will be removed from all assigned classrooms.');">
                                                <input type="hidden" name="csrf_token" value="<?= function_exists('getCsrfToken') ? getCsrfToken() : '' ?>">
                                                <input type="hidden" name="id" value="<?= (int)$res['id'] ?>">
                                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                                    <i class="ph ph-trash text-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (($totalPages ?? 1) > 1): ?>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                    <div>Showing page <?= $currentPage ?> of <?= $totalPages ?> (Total: <?= $totalItems ?> resources)</div>
                    <div class="flex gap-1">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="/admin/resources/index.php?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&type=<?= urlencode($type ?? '') ?>&classroom_id=<?= (int)($classroomId ?? 0) ?>" 
                               class="px-3 py-1.5 rounded-lg border <?= $i === $currentPage ? 'bg-primary text-white border-primary font-bold' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-100' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

