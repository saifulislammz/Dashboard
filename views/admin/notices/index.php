<?php
$pageTitle = 'Manage Notices';
$activeMenu = 'notices_manage';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manage Notices</h1>
                <p class="mt-1 text-sm text-gray-500">View, edit and manage all published notices.</p>
            </div>
            <a href="create.php" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-primary hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                <svg class="mr-2 -ml-1 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Create Notice
            </a>
        </div>

        <?php if (!empty($successMessage)): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
                <span class="block sm:inline"><?php echo e($successMessage); ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($errorMessage)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
                <span class="block sm:inline"><?php echo e($errorMessage); ?></span>
            </div>
        <?php endif; ?>

        <!-- Search & Data Table Container -->
        <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden">
            
            <!-- Search -->
            <div class="p-6 border-b border-gray-50">
                <form action="index.php" method="GET" class="flex flex-col sm:flex-row gap-3 max-w-lg">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search notices..." class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-colors">
                    </div>
                    <button type="submit" class="inline-flex justify-center items-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-primary bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">Search</button>
                </form>
            </div>

            <!-- Data Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Audience</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created By</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-50">
                        <?php foreach($notices as $notice): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-5 whitespace-nowrap text-sm font-semibold text-gray-900"><?php echo e(mb_strimwidth($notice['title'], 0, 40, '...')); ?></td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm text-gray-600 capitalize"><?php echo e($notice['target_audience']); ?></td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm">
                                <?php if($notice['status'] === 'active'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#dcfce7] text-[#166534]">
                                        <svg class="-ml-1 mr-1.5 h-2 w-2 text-[#166534]" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="-ml-1 mr-1.5 h-2 w-2 text-red-500" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm text-gray-600"><?php echo e($notice['creator_name'] ?? 'Unknown'); ?></td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm text-gray-600"><?php echo date('d M Y', strtotime($notice['created_at'])); ?></td>
                            <td class="px-6 py-5 whitespace-nowrap text-right text-sm font-medium space-x-2.5">
                                <!-- Edit -->
                                <a href="edit.php?id=<?php echo $notice['id']; ?>" class="text-primary hover:text-green-700 transition-colors">Edit</a>
                                <span class="text-gray-200">|</span>
                                
                                <!-- Status Toggle -->
                                <form action="index.php?action=status" method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
                                    <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $notice['status']; ?>">
                                    <button type="submit" class="text-[#0284c7] hover:text-[#0369a1] transition-colors">
                                        <?php echo $notice['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                                <span class="text-gray-200">|</span>
                                
                                <!-- Duplicate -->
                                <form action="index.php?action=duplicate" method="POST" class="inline" onsubmit="return confirm('Duplicate this notice?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
                                    <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
                                    <button type="submit" class="text-[#d97706] hover:text-[#b45309] transition-colors">Duplicate</button>
                                </form>
                                <span class="text-gray-200">|</span>

                                <!-- Delete -->
                                <form action="index.php?action=delete" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this notice?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
                                    <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
                                    <button type="submit" class="text-[#dc2626] hover:text-[#b91c1c] transition-colors">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($notices)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No notices found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-50 flex items-center justify-between">
                <div class="flex-1 flex justify-between">
                    <?php if($page > 1): ?>
                        <a href="index.php?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">Previous</a>
                    <?php endif; ?>
                    <?php if($page < $totalPages): ?>
                        <a href="index.php?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors ml-auto">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

