<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_student.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]" x-data="{ submitModalOpen: false, activeResourceId: null, activeResourceTitle: '', activeDueDate: '', activeMaxPoints: '' }">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Study Materials & Classroom Resources</h1>
                <p class="mt-1 text-sm text-gray-500">Access all lecture notes, handouts, video lectures, and assignments distributed to your group classrooms.</p>
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

        <!-- Filters -->
        <div class="bg-white shadow-xs border border-gray-100 rounded-2xl p-5">
            <form action="/student/resources.php" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Search Material</label>
                    <div class="relative">
                        <i class="ph ph-magnifying-glass absolute left-3 top-3 text-gray-400"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search resources..." class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Category</label>
                    <select name="type" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Categories</option>
                        <option value="document" <?= ($type ?? '') === 'document' ? 'selected' : '' ?>>📄 Documents & Handouts</option>
                        <option value="video" <?= ($type ?? '') === 'video' ? 'selected' : '' ?>>🎥 Video Lectures</option>
                        <option value="assignment" <?= ($type ?? '') === 'assignment' ? 'selected' : '' ?>>📝 Assignments</option>
                        <option value="quiz" <?= ($type ?? '') === 'quiz' ? 'selected' : '' ?>>❓ Quizzes</option>
                        <option value="link" <?= ($type ?? '') === 'link' ? 'selected' : '' ?>>🔗 Reference Links</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 py-2 px-4 bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold rounded-xl transition-colors">
                        Filter
                    </button>
                    <a href="/student/resources.php" class="py-2 px-3 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-medium rounded-xl transition-colors">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Resources Grid -->
        <?php if (empty($resources)): ?>
            <div class="bg-white rounded-2xl p-12 text-center border border-gray-100 shadow-xs">
                <i class="ph ph-books text-5xl text-gray-300 mb-3"></i>
                <h3 class="text-base font-bold text-gray-800">No study materials available yet</h3>
                <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">When your teachers distribute class notes, documents, or assignments to your group classroom, they will appear here.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($resources as $res): ?>
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between overflow-hidden">
                        
                        <div class="p-5 space-y-3">
                            <!-- Type Badge & Date -->
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    <?php if ($res['resource_type'] === 'document'): ?>bg-blue-100 text-blue-800
                                    <?php elseif ($res['resource_type'] === 'video'): ?>bg-red-100 text-red-800
                                    <?php elseif ($res['resource_type'] === 'assignment'): ?>bg-amber-100 text-amber-800
                                    <?php elseif ($res['resource_type'] === 'quiz'): ?>bg-purple-100 text-purple-800
                                    <?php else: ?>bg-teal-100 text-teal-800<?php endif; ?>">
                                    <?php if ($res['resource_type'] === 'document'): ?>📄 Document
                                    <?php elseif ($res['resource_type'] === 'video'): ?>🎥 Video
                                    <?php elseif ($res['resource_type'] === 'assignment'): ?>📝 Assignment
                                    <?php elseif ($res['resource_type'] === 'quiz'): ?>❓ Quiz
                                    <?php else: ?>🔗 Link<?php endif; ?>
                                </span>
                                <span class="text-xs text-gray-400"><?= date('d M Y', strtotime($res['created_at'])) ?></span>
                            </div>

                            <!-- Title & Description -->
                            <div>
                                <h3 class="text-base font-bold text-gray-900 line-clamp-1" title="<?= htmlspecialchars($res['title']) ?>">
                                    <?= htmlspecialchars($res['title']) ?>
                                </h3>
                                <?php if (!empty($res['description'])): ?>
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-2"><?= htmlspecialchars($res['description']) ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Assignment Specific Meta -->
                            <?php if ($res['resource_type'] === 'assignment'): ?>
                                <div class="p-3 bg-amber-50/60 rounded-xl border border-amber-100 text-xs space-y-1">
                                    <?php if (!empty($res['due_date'])): 
                                        $isPastDue = time() > strtotime($res['due_date']);
                                    ?>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-500">Deadline:</span>
                                            <span class="font-bold <?= $isPastDue ? 'text-red-600' : 'text-amber-800' ?>">
                                                <?= date('d M Y, h:i A', strtotime($res['due_date'])) ?>
                                                <?= $isPastDue ? ' (Expired)' : '' ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-500">Status:</span>
                                        <?php if (!empty($res['my_submission_id'])): ?>
                                            <?php if ($res['my_submission_status'] === 'graded'): ?>
                                                <span class="font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">
                                                    Graded: <?= (float)$res['my_grade'] ?> / <?= (float)($res['max_points'] ?? 100) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded">Submitted</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="font-bold text-gray-500">Not Submitted</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Action Footer -->
                        <div class="p-4 bg-gray-50/70 border-t border-gray-100 flex items-center justify-between gap-2">
                            <?php if (!empty($res['file_path'])): ?>
                                <a href="/resource/download.php?id=<?= (int)$res['id'] ?>" class="flex-1 inline-flex justify-center items-center py-2 px-3 bg-primary hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors">
                                    <i class="ph ph-download-simple mr-1 text-sm"></i> Download File
                                </a>
                            <?php elseif (!empty($res['external_url'])): ?>
                                <a href="<?= htmlspecialchars($res['external_url']) ?>" target="_blank" rel="noopener noreferrer" class="flex-1 inline-flex justify-center items-center py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors">
                                    <i class="ph ph-arrow-square-out mr-1 text-sm"></i> Open Resource
                                </a>
                            <?php elseif ($res['resource_type'] === 'quiz' && !empty($res['quiz_id'])): ?>
                                <a href="/quiz/play.php?quiz_id=<?= (int)$res['quiz_id'] ?>" class="flex-1 inline-flex justify-center items-center py-2 px-3 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors">
                                    <i class="ph ph-play mr-1 text-sm"></i> Take Quiz
                                </a>
                            <?php endif; ?>

                            <?php if ($res['resource_type'] === 'assignment'): ?>
                                <button type="button" 
                                        @click="
                                            activeResourceId = <?= (int)$res['id'] ?>;
                                            activeResourceTitle = '<?= htmlspecialchars(addslashes($res['title'])) ?>';
                                            activeDueDate = '<?= !empty($res['due_date']) ? date('d M Y, h:i A', strtotime($res['due_date'])) : '' ?>';
                                            activeMaxPoints = '<?= (float)($res['max_points'] ?? 100) ?>';
                                            submitModalOpen = true;
                                        "
                                        class="flex-1 inline-flex justify-center items-center py-2 px-3 border border-amber-600 text-amber-700 hover:bg-amber-50 text-xs font-bold rounded-xl transition-colors">
                                    <i class="ph ph-upload-simple mr-1 text-sm"></i> 
                                    <?= !empty($res['my_submission_id']) ? 'Resubmit Work' : 'Submit Work' ?>
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Student Assignment Submission Modal (Alpine.js) -->
        <div x-show="submitModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-gray-100 space-y-4" @click.away="submitModalOpen = false">
                <div class="flex items-start justify-between border-b border-gray-100 pb-3">
                    <div>
                        <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">Assignment Submission</span>
                        <h3 class="text-base font-bold text-gray-900 mt-0.5" x-text="activeResourceTitle"></h3>
                        <p class="text-xs text-gray-400" x-show="activeDueDate" x-text="'Deadline: ' + activeDueDate"></p>
                    </div>
                    <button type="button" @click="submitModalOpen = false" class="text-gray-400 hover:text-gray-600 p-1">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                <form action="/student/submit_assignment.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= function_exists('getCsrfToken') ? getCsrfToken() : '' ?>">
                    <input type="hidden" name="resource_id" :value="activeResourceId">
                    <input type="hidden" name="redirect_to" value="/student/resources.php">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Answer Notes / Text Submission
                        </label>
                        <textarea name="notes" rows="4" placeholder="Write your answers, links, or submission remarks here..." 
                                  class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Attach Assignment File (Optional)
                        </label>
                        <input type="file" name="submission_file" 
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-gray-200 rounded-xl cursor-pointer">
                        <p class="text-[11px] text-gray-400 mt-1">Supported: PDF, DOC, DOCX, ZIP, TXT, Images (Max 25MB).</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" @click="submitModalOpen = false" class="px-4 py-2 border border-gray-200 text-gray-600 text-xs font-semibold rounded-xl hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-primary hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors">
                            Confirm & Submit Work
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

