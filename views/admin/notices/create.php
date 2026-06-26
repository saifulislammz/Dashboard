<?php
$pageTitle = 'Create Notice';
$activeMenu = 'notices_create';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$title = $oldValues['title'] ?? '';
$content = $oldValues['content'] ?? '';
$status = $oldValues['status'] ?? 'active';
// Default to student
$audienceStudent = isset($oldValues['audience_student']) ? $oldValues['audience_student'] : true;
$audienceTeacher = isset($oldValues['audience_teacher']) ? $oldValues['audience_teacher'] : false;
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Create Notice</h1>
                <p class="mt-1 text-sm text-gray-500">Fill in the details below to create a new notice.</p>
            </div>
            <a href="index.php" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
                <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Notices
            </a>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
                <span class="block sm:inline"><?php echo e($errorMessage); ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-sm border border-gray-100 rounded-2xl p-6 sm:p-8">
            <form action="create.php" method="POST" class="space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
                
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700">Notice Title</label>
                    <input type="text" name="title" required value="<?php echo e($title); ?>" placeholder="Enter notice title" class="block w-full border border-gray-300 rounded-lg shadow-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-[#7c3aed] sm:text-sm transition-colors">
                </div>

                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700">Notice Description</label>
                    <textarea name="content" required rows="6" placeholder="Write your notice description here..." class="block w-full border border-gray-300 rounded-lg shadow-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-[#7c3aed] focus:border-[#7c3aed] sm:text-sm transition-colors"><?php echo e($content); ?></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <!-- Target Audience -->
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Target Audience</label>
                        <div class="space-y-3">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="audience_student" value="1" <?php echo $audienceStudent ? 'checked' : ''; ?> class="h-5 w-5 rounded border-gray-300 text-[#7c3aed] focus:ring-[#7c3aed] transition-colors cursor-pointer">
                                <span class="ml-3 text-sm text-gray-700">Students</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="audience_teacher" value="1" <?php echo $audienceTeacher ? 'checked' : ''; ?> class="h-5 w-5 rounded border-gray-300 text-[#7c3aed] focus:ring-[#7c3aed] transition-colors cursor-pointer">
                                <span class="ml-3 text-sm text-gray-700">Teachers</span>
                            </label>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <div class="space-y-3">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="status" value="active" <?php echo $status === 'active' ? 'checked' : ''; ?> class="h-5 w-5 border-gray-300 text-[#7c3aed] focus:ring-[#7c3aed] transition-colors cursor-pointer">
                                <span class="ml-3 text-sm text-gray-700">Active</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="status" value="inactive" <?php echo $status === 'inactive' ? 'checked' : ''; ?> class="h-5 w-5 border-gray-300 text-[#7c3aed] focus:ring-[#7c3aed] transition-colors cursor-pointer">
                                <span class="ml-3 text-sm text-gray-700">Inactive</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end pt-6 border-t border-gray-100 gap-3">
                    <a href="index.php" class="inline-flex justify-center items-center py-2.5 px-5 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors order-2 sm:order-1">Cancel</a>
                    <button type="submit" class="inline-flex justify-center items-center py-2.5 px-5 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-[#7c3aed] hover:bg-[#6d28d9] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7c3aed] transition-colors order-1 sm:order-2">Save Notice</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
