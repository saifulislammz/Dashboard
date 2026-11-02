<?php
$pageTitle = 'Admin Dashboard';
$activeMenu = 'dashboard';
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar_admin.php';

$observerData = $observerData ?? ['is_observer' => false, 'assigned_classrooms_count' => 0, 'upcoming_sessions' => [], 'past_sessions' => []];
$isObserver = !empty($observerData['is_observer']) || (function_exists('isSuperAdmin') && isSuperAdmin());
$upcomingSessions = $observerData['upcoming_sessions'] ?? [];
$pastSessions = $observerData['past_sessions'] ?? [];
$assignedCount = (int) ($observerData['assigned_classrooms_count'] ?? 0);
?>

<main class="flex-1 overflow-y-auto p-6 lg:p-10 bg-[#fafafa]">
    <div class="max-w-[1600px] mx-auto space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">Admin Dashboard</h1>
                <p class="text-[15px] text-gray-600 font-medium">Welcome to your management and observation overview.</p>
            </div>
            <?php if ($assignedCount > 0): ?>
                <div class="flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-semibold text-emerald-800"><?= $assignedCount ?> Classroom<?= $assignedCount !== 1 ? 's' : '' ?> Assigned for Observation</span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Analytics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-6">
                    
            <!-- Stat Card 1: Students -->
            <a href="/admin/students.php" class="bg-white rounded-2xl p-6 shadow-xs shadow-gray-200/50 border border-gray-100 flex flex-col justify-between hover:-translate-y-1 hover:shadow-md transition-all duration-300 block group">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center text-green-600">
                        <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">group</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Students</p>
                        <h3 class="text-3xl font-bold text-brandText"><?= htmlspecialchars((string) ($stats['total_students'] ?? 0)) ?></h3>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <p class="text-xs text-gray-500 flex items-center gap-1">Active Students</p>
                    <div class="w-16 h-8 flex items-end">
                        <svg width="100%" height="100%" viewBox="0 0 100 50" preserveAspectRatio="none">
                            <path d="M0,40 Q20,40 30,20 T60,30 T100,5" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Stat Card 2: Teachers -->
            <a href="/admin/teachers.php" class="bg-white rounded-2xl p-6 shadow-xs shadow-gray-200/50 border border-gray-100 flex flex-col justify-between hover:-translate-y-1 hover:shadow-md transition-all duration-300 block group">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                        <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">school</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Teachers</p>
                        <h3 class="text-3xl font-bold text-brandText"><?= htmlspecialchars((string) ($stats['total_teachers'] ?? 0)) ?></h3>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <p class="text-xs text-gray-500 flex items-center gap-1">Active Faculty</p>
                    <div class="w-16 h-8 flex items-end">
                        <svg width="100%" height="100%" viewBox="0 0 100 50" preserveAspectRatio="none">
                            <path d="M0,45 Q20,30 40,40 T70,20 T100,10" fill="none" stroke="#0EA5E9" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Stat Card 3: Classrooms -->
            <a href="/admin/classrooms/index.php" class="bg-white rounded-2xl p-6 shadow-xs shadow-gray-200/50 border border-gray-100 flex flex-col justify-between hover:-translate-y-1 hover:shadow-md transition-all duration-300 block group">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">business</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Classrooms</p>
                        <h3 class="text-3xl font-bold text-brandText"><?= htmlspecialchars((string) ($stats['total_classrooms'] ?? 0)) ?></h3>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <p class="text-xs text-gray-500 flex items-center gap-1">Classroom Hub</p>
                    <div class="w-16 h-8 flex items-end">
                        <svg width="100%" height="100%" viewBox="0 0 100 50" preserveAspectRatio="none">
                            <path d="M0,45 Q20,40 30,25 T60,35 T100,5" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Stat Card 4: Notices -->
            <a href="/admin/notices/index.php" class="bg-white rounded-2xl p-6 shadow-xs shadow-gray-200/50 border border-gray-100 flex flex-col justify-between hover:-translate-y-1 hover:shadow-md transition-all duration-300 block group">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-500">
                        <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">notifications</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Notices</p>
                        <h3 class="text-3xl font-bold text-brandText"><?= htmlspecialchars((string) ($stats['total_notices'] ?? 0)) ?></h3>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <p class="text-xs text-gray-500 flex items-center gap-1">Announcements</p>
                    <div class="w-16 h-8 flex items-end">
                        <svg width="100%" height="100%" viewBox="0 0 100 50" preserveAspectRatio="none">
                            <path d="M0,40 Q30,40 40,25 T70,30 T100,15" fill="none" stroke="#F59E0B" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Stat Card 5: Quizzes -->
            <a href="/admin/quiz/index.php" class="bg-white rounded-2xl p-6 shadow-xs shadow-gray-200/50 border border-gray-100 flex flex-col justify-between hover:-translate-y-1 hover:shadow-md transition-all duration-300 block group">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600">
                        <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">quiz</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Quizzes</p>
                        <h3 class="text-3xl font-bold text-brandText"><?= htmlspecialchars((string) ($stats['total_quizzes'] ?? 0)) ?></h3>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <p class="text-xs text-gray-500 flex items-center gap-1">Arabic Exams</p>
                    <div class="w-16 h-8 flex items-end">
                        <svg width="100%" height="100%" viewBox="0 0 100 50" preserveAspectRatio="none">
                            <path d="M0,45 Q20,40 30,25 T60,35 T100,5" fill="none" stroke="#9333EA" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>
            </a>
        </div>

        <!-- Observer Live Classes & Assigned Monitoring Section -->
        <?php if ($isObserver && $assignedCount > 0): ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="h-8 w-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 tracking-tight">Assigned Live Classes & Observations</h2>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Directly observe live sessions of your assigned classes, inspect student presence, and review historical sessions.</p>
                </div>
                <a href="/admin/classrooms/index.php" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:text-emerald-700 transition-colors">
                    View All Assigned Classrooms &rarr;
                </a>
            </div>

            <?php if (empty($upcomingSessions)): ?>
                <div class="p-8 text-center text-gray-500">
                    <div class="mx-auto h-12 w-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-900">No live sessions scheduled today</p>
                    <p class="text-xs text-gray-400 mt-1">Sessions will automatically appear here once scheduled by the Super Administrator.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 p-6 bg-gray-50/50">
                    <?php foreach ($upcomingSessions as $sess): ?>
                        <?php
                            $isToday = (date('Y-m-d') === $sess['session_date']);
                            $startTime = date('g:i A', strtotime($sess['start_time']));
                            $endTime = date('g:i A', strtotime($sess['end_time']));
                            $dateFormatted = date('M j, Y', strtotime($sess['session_date']));
                        ?>
                        <div class="bg-white rounded-xl border border-gray-200/80 p-5 shadow-xs hover:border-emerald-300 hover:shadow-sm transition-all duration-200 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold <?= $sess['provider'] === 'google_meet' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                                        <?= $sess['provider'] === 'google_meet' ? 'Google Meet' : 'Zoom' ?>
                                    </span>
                                    <?php if ($isToday): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Today
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-500 font-medium"><?= htmlspecialchars($dateFormatted) ?></span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="text-base font-bold text-gray-900 truncate" title="<?= htmlspecialchars($sess['class_name']) ?>">
                                    <?= htmlspecialchars($sess['class_name']) ?>
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5 truncate" title="<?= htmlspecialchars($sess['topic'] ?: $sess['class_title']) ?>">
                                    <?= htmlspecialchars($sess['topic'] ?: $sess['class_title']) ?>
                                </p>

                                <div class="mt-4 pt-3 border-t border-gray-100 grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-gray-400 block text-[10px] uppercase font-medium">Teacher</span>
                                        <span class="text-gray-800 font-semibold truncate block"><?= htmlspecialchars($sess['teacher_name']) ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block text-[10px] uppercase font-medium">Student</span>
                                        <span class="text-gray-800 font-semibold truncate block"><?= htmlspecialchars($sess['student_name']) ?></span>
                                    </div>
                                </div>

                                <div class="mt-3 flex items-center gap-1.5 text-xs text-gray-600">
                                    <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span><?= $startTime ?> - <?= $endTime ?> (<?= htmlspecialchars($sess['timezone']) ?>)</span>
                                </div>
                            </div>

                            <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between gap-2">
                                <a href="/session/join.php?id=<?= (int) $sess['session_id'] ?>" target="_blank" class="flex-1 inline-flex items-center justify-center gap-2 py-2 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    One-Click Join
                                </a>
                                <a href="/admin/attendance/detail.php?classroom_id=<?= (int) $sess['classroom_id'] ?>" class="p-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors" title="View Attendance">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </a>
                                <a href="/admin/sessions/index.php?classroom_id=<?= (int) $sess['classroom_id'] ?>" class="p-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors" title="Session History">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
