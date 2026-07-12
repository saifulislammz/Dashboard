<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Manage Classrooms</h1>
                <p class="mt-1 text-sm text-gray-500">View and manage all classrooms in your institution.</p>
            </div>
            <a href="/admin/classrooms/create.php" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-primary hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Create Classroom
            </a>
        </div>

        <!-- Search Bar Container -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            
            <div class="p-6 border-b border-gray-50">
                <form method="GET" class="flex flex-col sm:flex-row gap-4 max-w-2xl">
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Search by class name, title, teacher or student..." class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-colors text-gray-900" oninput="if(this.value.trim() === '') this.form.submit()">
                    </div>
                    <button type="submit" class="inline-flex justify-center items-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-primary bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        Search
                    </button>
                    <?php if (!empty($_GET['search'])): ?>
                        <a href="/admin/classrooms/index.php" class="inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table View -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-white">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class Code</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class Info</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Teacher</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">
                                <a href="?search=<?= urlencode($_GET['search'] ?? '') ?>&sort=status&order=<?= (($sort ?? '') === 'status' && ($order ?? '') === 'ASC') ? 'DESC' : 'ASC' ?>" class="group inline-flex items-center justify-center space-x-1 text-gray-500 hover:text-gray-900 transition-colors">
                                    <span>Status</span>
                                    <span class="flex flex-col">
                                        <svg class="w-3 h-3 <?= (($sort ?? '') === 'status' && ($order ?? '') === 'ASC') ? 'text-gray-900' : 'text-gray-300 group-hover:text-gray-500' ?> -mb-1" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" fill-rule="evenodd"></path></svg>
                                        <svg class="w-3 h-3 <?= (($sort ?? '') === 'status' && ($order ?? '') === 'DESC') ? 'text-gray-900' : 'text-gray-300 group-hover:text-gray-500' ?>" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" fill-rule="evenodd"></path></svg>
                                    </span>
                                </a>
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-50">
                        <?php if (empty($classrooms)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                    </svg>
                                    <p class="text-base font-medium text-gray-900">No classrooms found</p>
                                    <p class="text-sm text-gray-500 mt-1">Get started by creating a new classroom.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($classrooms as $class): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 rounded-lg bg-green-50 flex items-center justify-center mr-3">
                                            <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900 uppercase"><?= htmlspecialchars($class['class_code']) ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-bold text-gray-900 max-w-[150px] truncate" title="<?= htmlspecialchars($class['class_name']) ?>"><?= htmlspecialchars($class['class_name']) ?></div>
                                    <div class="text-sm text-gray-600 max-w-[150px] truncate" title="<?= htmlspecialchars($class['class_title']) ?>"><?= htmlspecialchars($class['class_title']) ?></div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-green-50 border border-green-200 flex items-center justify-center mr-3">
                                            <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div class="max-w-[120px]">
                                            <div class="text-sm font-semibold text-gray-900 truncate" title="<?= htmlspecialchars($class['teacher_name'] ?? 'Unassigned') ?>"><?= htmlspecialchars($class['teacher_name'] ?? 'Unassigned') ?></div>
                                            <div class="text-xs text-gray-500 truncate" title="<?= htmlspecialchars($class['teacher_email'] ?? '') ?>"><?= htmlspecialchars($class['teacher_email'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-[#dcfce7] border border-[#bbf7d0] flex items-center justify-center mr-3">
                                            <svg class="h-4 w-4 text-[#166534]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div class="max-w-[120px]">
                                            <div class="text-sm font-semibold text-gray-900 truncate" title="<?= htmlspecialchars($class['student_name'] ?? 'Unassigned') ?>"><?= htmlspecialchars($class['student_name'] ?? 'Unassigned') ?></div>
                                            <div class="text-xs text-gray-500 truncate" title="<?= htmlspecialchars($class['student_email'] ?? '') ?>"><?= htmlspecialchars($class['student_email'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <?php if ($class['status'] === 'Active'): ?>
                                        <div class="inline-flex items-center justify-center" title="Active">
                                            <span class="flex h-3 w-3 relative">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="inline-flex items-center justify-center" title="Inactive">
                                            <span class="flex h-3 w-3 relative">
                                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2.5">
                                        <a href="/admin/sessions/index.php?classroom_id=<?= $class['id'] ?>" class="inline-flex items-center justify-center p-2 text-[#10b981] bg-green-50 hover:bg-green-100 hover:text-[#059669] rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1 shadow-sm hover:shadow" title="Manage Sessions">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </a>
                                        <a href="/admin/classrooms/edit.php?id=<?= $class['id'] ?>" class="inline-flex items-center justify-center p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm hover:shadow" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </a>
                                        <form method="POST" action="/admin/classrooms/delete.php?id=<?= $class['id'] ?>" class="inline-flex m-0" onsubmit="return handleConfirm(event, 'Are you sure you want to delete this classroom?');">
                                            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                            <button type="submit" class="inline-flex items-center justify-center p-2 text-red-600 bg-red-50 hover:bg-red-100 hover:text-red-700 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 shadow-sm hover:shadow" title="Delete">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
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
            <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-50 flex items-center justify-between">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-600">
                            Showing page <span class="font-semibold text-gray-900"><?= $currentPage ?></span> of <span class="font-semibold text-gray-900"><?= $totalPages ?></span>
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex gap-2" aria-label="Pagination">
                            <?php if ($currentPage > 1): ?>
                                <a href="?page=<?= $currentPage - 1 ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>&sort=<?= urlencode($sort ?? 'created_at') ?>&order=<?= urlencode($order ?? 'DESC') ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                <a href="?page=<?= $i ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>&sort=<?= urlencode($sort ?? 'created_at') ?>&order=<?= urlencode($order ?? 'DESC') ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-200 bg-white text-sm font-medium rounded-lg <?= $i === $currentPage ? 'text-primary bg-green-50 border-primary' : 'text-gray-700 hover:bg-gray-50 transition-colors' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?page=<?= $currentPage + 1 ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>&sort=<?= urlencode($sort ?? 'created_at') ?>&order=<?= urlencode($order ?? 'DESC') ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                    Next
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

