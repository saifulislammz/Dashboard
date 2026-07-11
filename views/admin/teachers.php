<?php
$pageTitle = 'Manage Teachers';
$activeMenu = 'teachers';
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar_admin.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Teachers</h1>
                <p class="text-sm text-gray-500 mt-1">Manage and view all teachers in your institution.</p>
            </div>
            <!-- Create Teacher Button -->
            <button onclick="document.getElementById('createForm').classList.toggle('hidden')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-primary hover:bg-green-700 transition-colors gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Teacher
            </button>
        </div>

        <?php if (!empty($successMessage)): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <p class="text-sm text-green-700"><?php echo e($successMessage); ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($errorMessage)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <p class="text-sm text-red-700"><?php echo e($errorMessage); ?></p>
            </div>
        <?php endif; ?>

        <!-- Create Form -->
        <div id="createForm" class="hidden bg-white shadow-sm border border-gray-100 rounded-xl p-6">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-primary">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Add New Teacher</h2>
                    <p class="text-sm text-gray-500">Fill in the details below to add a new teacher.</p>
                </div>
            </div>
            
            <form action="teachers.php?action=create" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input type="text" name="name" required placeholder="Enter full name" class="pl-10 block w-full border border-gray-200 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-colors">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input type="email" name="email" required placeholder="Enter email address" class="pl-10 block w-full border border-gray-200 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-colors">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="password" name="password" required placeholder="Enter password" class="pl-10 block w-full border border-gray-200 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-colors">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="password" name="confirm_password" required placeholder="Confirm password" class="pl-10 block w-full border border-gray-200 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-colors">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <select name="status" class="pl-10 block w-full border border-gray-200 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-colors appearance-none bg-white">
                                <option value="0">Active</option>
                                <option value="2">Inactive</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end pt-4 border-t border-gray-50">
                    <button type="submit" class="inline-flex items-center gap-2 justify-center py-2.5 px-6 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-primary hover:bg-green-700 focus:outline-none transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        Save Teacher
                    </button>
                </div>
            </form>
        </div>

        <!-- Search -->
        <div class="bg-white p-2 rounded-xl shadow-sm border border-gray-100 flex items-center gap-2">
            <form action="teachers.php" method="GET" class="flex w-full gap-2 items-center">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search by name or email..." class="pl-10 block w-full border-none bg-transparent rounded-lg py-2.5 px-3 focus:ring-0 sm:text-sm text-gray-900 placeholder-gray-400">
                </div>
                <button type="submit" class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg text-primary bg-green-50 hover:bg-green-200 transition-colors">Search</button>
            </form>
        </div>

        <!-- Data Table -->
        <div class="bg-white shadow-sm border border-gray-100 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-[#f8fafc]">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php foreach($teachers as $teacher): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-green-50 flex items-center justify-center text-primary font-bold text-sm uppercase">
                                        <?php echo substr($teacher['name'], 0, 1); ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo e($teacher['name']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo e($teacher['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if($teacher['status'] == 0): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                <!-- Status Toggle -->
                                <form action="teachers.php?action=status" method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
                                    <input type="hidden" name="id" value="<?php echo $teacher['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $teacher['status'] == 0 ? 2 : 0; ?>">
                                    <button type="submit" class="text-primary hover:text-green-700 transition-colors">
                                        <?php echo $teacher['status'] == 0 ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                                <!-- Delete -->
                                <form action="teachers.php?action=delete" method="POST" class="inline" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
                                    <input type="hidden" name="id" value="<?php echo $teacher['id']; ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700 transition-colors">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($teachers)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <p>No teachers found.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if($totalPages > 1): ?>
            <div class="bg-white px-4 py-3 border-t border-gray-100 flex items-center justify-between sm:px-6">
                <div class="flex-1 flex justify-between">
                    <?php if($page > 1): ?>
                        <a href="teachers.php?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">Previous</a>
                    <?php endif; ?>
                    <?php if($page < $totalPages): ?>
                        <a href="teachers.php?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 ml-auto transition-colors">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

