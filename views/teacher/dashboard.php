<?php
$pageTitle = 'Teacher Dashboard';
$activeMenu = 'dashboard';
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar_teacher.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <!-- Welcome Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-xs">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Teacher Dashboard</h1>
                <p class="text-sm text-gray-500 mt-1">Manage your assigned classrooms, view enrolled students, and conduct live sessions.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/teacher/classrooms.php" class="inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors shadow-2xs">
                    View All Classes
                </a>
            </div>
        </div>

        <!-- Section: Upcoming Live Sessions -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <h2 class="text-lg font-bold text-gray-900">Upcoming Live Sessions</h2>
                </div>
                <a href="/teacher/sessions.php" class="text-xs font-semibold text-primary hover:underline">
                    View Schedule &rarr;
                </a>
            </div>

            <?php if (empty($upcomingSessions)): ?>
                <div class="bg-white rounded-xl border border-gray-200 p-8 text-center shadow-2xs">
                    <svg class="mx-auto h-10 w-10 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <p class="text-sm font-medium text-gray-600">No live sessions scheduled for today</p>
                    <p class="text-xs text-gray-400 mt-0.5">Scheduled group class sessions will automatically appear here.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($upcomingSessions as $sess): ?>
                        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-xs hover:border-primary/50 transition-all flex flex-col justify-between group">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-gray-100 text-gray-700">
                                        <?= htmlspecialchars($sess['provider'] === 'google_meet' ? 'Google Meet' : 'Zoom') ?>
                                    </span>
                                    <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                                        <?= htmlspecialchars(date('d M, Y', strtotime($sess['session_date']))) ?>
                                    </span>
                                </div>
                                <h3 class="text-base font-bold text-gray-900 group-hover:text-primary transition-colors truncate" title="<?= htmlspecialchars($sess['class_name']) ?>">
                                    <?= htmlspecialchars($sess['class_name']) ?>
                                </h3>
                                <p class="text-xs text-gray-500 mt-1 flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <?= htmlspecialchars(date('h:i A', strtotime($sess['start_time']))) ?> - <?= htmlspecialchars(date('h:i A', strtotime($sess['end_time']))) ?>
                                </p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                                <a href="/session/join.php?id=<?= (int)$sess['id'] ?>" target="_blank" class="w-full inline-flex justify-center items-center gap-1.5 py-2 px-4 rounded-lg text-xs font-bold text-white bg-primary hover:bg-emerald-700 transition-colors shadow-2xs">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Join Live Session
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section: My Assigned Classrooms -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900">My Classrooms</h2>
                <a href="/teacher/classrooms.php" class="text-xs font-semibold text-primary hover:underline">
                    View All (<?= count($classrooms) ?>) &rarr;
                </a>
            </div>

            <?php if (empty($classrooms)): ?>
                <div class="bg-white rounded-xl border border-gray-200 p-8 text-center shadow-2xs">
                    <p class="text-sm font-medium text-gray-600">No classrooms assigned yet.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($classrooms as $class): ?>
                        <a href="/teacher/classroom_details.php?id=<?= $class['id'] ?>" class="block bg-white rounded-xl border border-gray-200 p-5 shadow-xs hover:border-primary/50 hover:shadow-md transition-all group">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 font-mono tracking-wider uppercase"><?= htmlspecialchars($class['class_code']) ?></span>
                                    <h3 class="text-base font-bold text-gray-900 group-hover:text-primary transition-colors truncate max-w-[200px]" title="<?= htmlspecialchars($class['class_name']) ?>">
                                        <?= htmlspecialchars($class['class_name']) ?>
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5 truncate max-w-[200px]"><?= htmlspecialchars($class['class_title']) ?></p>
                                </div>
                                <?php if ($class['status'] === 'Active'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">Active</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-800">Inactive</span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-600">
                                <div class="flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span class="font-bold text-gray-900"><?= (int)($class['student_count'] ?? 1) ?></span> Student(s) Enrolled
                                </div>
                                <span class="font-medium text-primary group-hover:underline">Details &rarr;</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section: Notice Board -->
        <?php if (!empty($notices)): ?>
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900">Notice Board</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($notices as $notice): ?>
                        <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden flex flex-col">
                            <div class="p-6 flex-1">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <h3 class="text-base font-bold text-gray-900"><?php echo e($notice['title']); ?></h3>
                                    <?php if (($notice['target_type'] ?? '') === 'all'): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 shrink-0">
                                            <i class="ph ph-broadcast"></i> Public
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-gray-600 text-xs whitespace-pre-line leading-relaxed"><?php echo e($notice['content']); ?></p>
                                
                                <?php if (!empty($notice['attachments'])): ?>
                                    <div class="mt-4 pt-4 border-t border-gray-100">
                                        <h4 class="text-xs font-semibold text-gray-700 mb-2">Attachments:</h4>
                                        <div class="space-y-1.5">
                                            <?php foreach ($notice['attachments'] as $attachment): ?>
                                                <a href="/download_attachment.php?id=<?php echo $attachment['id']; ?>" class="flex items-center p-2 text-xs text-primary bg-primary/5 hover:bg-primary/10 rounded-lg border border-primary/20 transition-colors">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                    <span class="truncate"><?php echo e($attachment['file_name']); ?></span>
                                                    <span class="ml-auto text-[10px] text-gray-500"><?php echo round($attachment['file_size'] / 1024, 2); ?> KB</span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="bg-gray-50 px-6 py-2.5 border-t border-gray-100 text-[11px] text-gray-400">
                                Published: <?php echo date('d F Y', strtotime($notice['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
