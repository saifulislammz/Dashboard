<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

// --- Unpack summary ---
$totalSessions  = (int)   ($summary['total_sessions']  ?? 0);
$studentPresent = (int)   ($summary['student_present'] ?? 0);
$studentAbsent  = (int)   ($summary['student_absent']  ?? 0);
$teacherPresent = (int)   ($summary['teacher_present'] ?? 0);
$teacherAbsent  = (int)   ($summary['teacher_absent']  ?? 0);
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                    <?= htmlspecialchars($classroom['class_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    <?= htmlspecialchars($classroom['class_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    <span class="text-gray-300 mx-1">&middot;</span>
                    Attendance Report
                </p>
            </div>
            <a href="/admin/attendance/overview.php"
               class="inline-flex items-center gap-2 bg-white py-2 px-4 border border-gray-200 rounded-lg shadow-sm text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Overview
            </a>
        </div>

        <!-- Summary Stats Row -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

            <!-- Total Sessions -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                <div class="flex-shrink-0 h-12 w-12 rounded-xl bg-blue-50 flex items-center justify-center">
                    <svg class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-extrabold text-gray-800"><?= $totalSessions ?></p>
                    <p class="text-xs text-gray-400 mt-0.5 uppercase tracking-wide font-medium">Total Sessions</p>
                </div>
            </div>

            <!-- Student Stats -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </span>
                    <p class="text-sm font-semibold text-gray-700">
                        <?= htmlspecialchars($classroom['student_name'] ?? 'Student', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-green-50 rounded-xl p-3 text-center">
                        <p class="text-2xl font-bold text-green-700"><?= $studentPresent ?></p>
                        <p class="text-xs text-green-500 mt-0.5 font-medium">Present</p>
                    </div>
                    <div class="bg-red-50 rounded-xl p-3 text-center">
                        <p class="text-2xl font-bold text-red-600"><?= $studentAbsent ?></p>
                        <p class="text-xs text-red-400 mt-0.5 font-medium">Absent</p>
                    </div>
                </div>
            </div>

            <!-- Teacher Stats -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-violet-100">
                        <svg class="h-4 w-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </span>
                    <p class="text-sm font-semibold text-gray-700">
                        <?= htmlspecialchars($classroom['teacher_name'] ?? 'Teacher', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-green-50 rounded-xl p-3 text-center">
                        <p class="text-2xl font-bold text-green-700"><?= $teacherPresent ?></p>
                        <p class="text-xs text-green-500 mt-0.5 font-medium">Present</p>
                    </div>
                    <div class="bg-red-50 rounded-xl p-3 text-center">
                        <p class="text-2xl font-bold text-red-600"><?= $teacherAbsent ?></p>
                        <p class="text-xs text-red-400 mt-0.5 font-medium">Absent</p>
                    </div>
                </div>
            </div>

        </div><!-- /stats row -->

        <!-- Two-column Session List -->
        <?php if (empty($sessions)): ?>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-10 text-center text-gray-400 text-sm">
                No past sessions recorded yet for this classroom.
            </div>
        <?php else: ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <!-- LEFT: Student Attendance -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">
                            <?= htmlspecialchars($classroom['student_name'] ?? 'Student', ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                        <p class="text-xs text-gray-400">Student Attendance</p>
                    </div>
                </div>

                <ul class="divide-y divide-gray-50">
                    <?php foreach ($sessions as $s):
                        $isStudentPresent = (bool) $s['student_present'];
                    ?>
                    <li class="px-5 py-3.5 flex items-center gap-3 hover:bg-gray-50 transition-colors">
                        <!-- Status icon -->
                        <div class="flex-shrink-0">
                            <?php if ($isStudentPresent): ?>
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                                    <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-red-100">
                                    <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Session info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">
                                <?= htmlspecialchars($s['topic'] ?? 'Untitled Session', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                <?= htmlspecialchars(date('d M Y', strtotime($s['session_date'])), ENT_QUOTES, 'UTF-8') ?>
                                &middot;
                                <?= htmlspecialchars(date('h:i A', strtotime($s['start_time'])), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>

                        <!-- Badge -->
                        <div class="flex-shrink-0">
                            <?php if ($isStudentPresent): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                    Present
                                    <?php if (!empty($s['student_join_time'])): ?>
                                        <span class="ml-1 font-normal text-green-500">
                                            <?= htmlspecialchars(date('h:i A', strtotime($s['student_join_time'])), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-600">
                                    Absent
                                </span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div><!-- /student col -->

            <!-- RIGHT: Teacher Attendance -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-violet-100">
                        <svg class="h-4 w-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">
                            <?= htmlspecialchars($classroom['teacher_name'] ?? 'Teacher', ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                        <p class="text-xs text-gray-400">Teacher Attendance</p>
                    </div>
                </div>

                <ul class="divide-y divide-gray-50">
                    <?php foreach ($sessions as $s):
                        $isTeacherPresent = (bool) $s['teacher_present'];
                    ?>
                    <li class="px-5 py-3.5 flex items-center gap-3 hover:bg-gray-50 transition-colors">
                        <!-- Status icon -->
                        <div class="flex-shrink-0">
                            <?php if ($isTeacherPresent): ?>
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                                    <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-red-100">
                                    <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Session info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">
                                <?= htmlspecialchars($s['topic'] ?? 'Untitled Session', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                <?= htmlspecialchars(date('d M Y', strtotime($s['session_date'])), ENT_QUOTES, 'UTF-8') ?>
                                &middot;
                                <?= htmlspecialchars(date('h:i A', strtotime($s['start_time'])), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>

                        <!-- Badge -->
                        <div class="flex-shrink-0">
                            <?php if ($isTeacherPresent): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                    Present
                                    <?php if (!empty($s['teacher_join_time'])): ?>
                                        <span class="ml-1 font-normal text-green-500">
                                            <?= htmlspecialchars(date('h:i A', strtotime($s['teacher_join_time'])), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-600">
                                    Absent
                                </span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div><!-- /teacher col -->

        </div><!-- /two-column -->

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="flex items-center justify-between">
            <p class="text-xs text-gray-500">
                Page <?= $page ?> of <?= $pages ?> (<?= $totalSessions ?> sessions)
            </p>
            <div class="flex gap-1">
                <?php for ($i = 1; $i <= $pages; $i++):
                    $qs = http_build_query(['classroom_id' => $classroom['id'], 'page' => $i]);
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

        <?php endif; ?><!-- /sessions -->

    </div><!-- /max-w -->
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
