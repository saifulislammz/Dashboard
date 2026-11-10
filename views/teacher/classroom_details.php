<?php
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar_teacher.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Classroom Details</h1>
            <a href="/teacher/classrooms.php" class="text-sm font-semibold text-primary hover:text-primary/80 transition-colors">
                &larr; Back to My Classes
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
            <!-- Header Section with Banner -->
            <div class="h-28 bg-gradient-to-r from-emerald-600 via-primary to-teal-600"></div>
            
            <div class="px-6 py-8 sm:p-8 -mt-12">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono font-bold bg-gray-100 text-gray-700 px-2 py-0.5 rounded">
                                <?= htmlspecialchars($classroom['class_code']) ?>
                            </span>
                            <?php if ($classroom['status'] === 'Active'): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Active</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Inactive</span>
                            <?php endif; ?>
                            <?php if (!empty($classroom['students']) && count($classroom['students']) > 1): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    Group Classroom (<?= count($classroom['students']) ?> Students)
                                </span>
                            <?php endif; ?>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mt-2"><?= htmlspecialchars($classroom['class_name']) ?></h2>
                        <p class="text-sm text-gray-500 mt-0.5"><?= htmlspecialchars($classroom['class_title']) ?></p>
                    </div>

                    <a href="/teacher/sessions.php?classroom_id=<?= (int)$classroom['id'] ?>" class="inline-flex justify-center items-center py-2.5 px-5 border border-transparent shadow-xs text-sm font-bold rounded-lg text-white bg-primary hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors flex-shrink-0">
                        <svg class="mr-2 -ml-1 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        View Live Sessions
                    </a>
                </div>

                <!-- Assigned Teachers Section -->
                <div class="mt-8">
                    <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                        Assigned Teachers (<?= count($classroom['teachers'] ?? []) ?: 1 ?>)
                    </h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <?php if (!empty($classroom['teachers'])): ?>
                            <?php foreach ($classroom['teachers'] as $t): ?>
                                <div class="p-3.5 rounded-xl border border-gray-200 bg-gray-50 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                                            <?= strtoupper(substr($t['username'] ?? 'T', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($t['username']) ?></p>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($t['email']) ?></p>
                                        </div>
                                    </div>
                                    <?php if (!empty($t['is_primary'])): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Primary Host
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-200 text-gray-700">
                                            Co-Teacher
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-3.5 rounded-xl border border-gray-200 bg-gray-50 flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                                    <?= strtoupper(substr($classroom['teacher_name'] ?? 'T', 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($classroom['teacher_name'] ?? 'Unassigned') ?></p>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($classroom['teacher_email'] ?? '') ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Enrolled Students Section -->
                <div class="mt-8 pt-6 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            Enrolled Students (<?= count($classroom['students'] ?? []) ?: (!empty($classroom['student_name']) ? 1 : 0) ?>)
                        </h3>
                    </div>

                    <?php if (!empty($classroom['students'])): ?>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-2xs">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Enrolled At</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    <?php foreach ($classroom['students'] as $s): ?>
                                        <tr class="hover:bg-gray-50/70 transition-colors">
                                            <td class="px-4 py-3.5 whitespace-nowrap">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                                        <?= strtoupper(substr($s['username'] ?? 'S', 0, 1)) ?>
                                                    </div>
                                                    <span class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($s['username']) ?></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3.5 whitespace-nowrap text-xs text-gray-500">
                                                <?= htmlspecialchars($s['email']) ?>
                                            </td>
                                            <td class="px-4 py-3.5 whitespace-nowrap text-xs text-gray-500">
                                                <?= !empty($s['enrolled_at']) ? htmlspecialchars(date('d M Y, h:i A', strtotime($s['enrolled_at']))) : '—' ?>
                                            </td>
                                            <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                                    <?= htmlspecialchars(ucfirst($s['status'] ?? 'Active')) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif (!empty($classroom['student_name'])): ?>
                        <div class="p-3.5 rounded-xl border border-gray-200 bg-gray-50 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">
                                <?= strtoupper(substr($classroom['student_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($classroom['student_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($classroom['student_email'] ?? '') ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-400 italic">No students assigned to this classroom yet.</p>
                    <?php endif; ?>
                </div>

                <!-- Class Resources & Materials Section for Teachers -->
                <div class="mt-8 pt-6 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <i class="ph ph-folder-open text-emerald-600 text-lg"></i>
                            Class Resources & Materials (<?= count($resources ?? []) ?>)
                        </h3>
                        <a href="/teacher/resources/create.php" class="inline-flex items-center px-3 py-1.5 bg-primary hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-xs transition-colors">
                            <i class="ph ph-plus mr-1"></i> Distribute Resource
                        </a>
                    </div>

                    <?php if (empty($resources)): ?>
                        <div class="p-6 bg-gray-50 rounded-xl text-center border border-gray-100">
                            <p class="text-xs text-gray-400">No resources or materials distributed to this classroom yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php foreach ($resources as $res): ?>
                                <div class="p-4 rounded-xl border border-gray-200 bg-white hover:border-primary/50 transition-colors flex items-center justify-between shadow-2xs">
                                    <div class="min-w-0 pr-3">
                                        <p class="text-sm font-bold text-gray-900 truncate"><?= htmlspecialchars($res['title']) ?></p>
                                        <div class="flex items-center gap-2 text-[11px] text-gray-400 mt-0.5">
                                            <span class="capitalize font-medium text-emerald-700"><?= htmlspecialchars($res['resource_type']) ?></span>
                                            <span>• <?= date('d M Y', strtotime($res['created_at'])) ?></span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 flex items-center gap-2">
                                        <?php if (!empty($res['file_path'])): ?>
                                            <a href="/resource/download.php?id=<?= (int)$res['id'] ?>" class="p-1.5 text-gray-500 hover:text-primary transition-colors" title="Download">
                                                <i class="ph ph-download-simple text-base"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($res['resource_type'] === 'assignment'): ?>
                                            <a href="/teacher/resources/submissions.php?id=<?= (int)$res['id'] ?>" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors">
                                                Review
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <p class="text-xs text-gray-400 italic">
                        Note: Teachers can view classroom details and conduct live sessions. Classroom enrollments are managed by the administrator.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
